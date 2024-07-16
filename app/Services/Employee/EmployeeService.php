<?php

namespace App\Services\Employee;

use App\Models\Employee\Employee;

use App\Models\Employee\PersonalInfo\Address;
use App\Models\Employee\PersonalInfo\Contact;
use App\Models\Other\UserState;
use App\Models\User;
use App\Events\EmployeeCreated;
use App\Models\Role;
use Exception;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class EmployeeService
{
    public function getAllEmployees()
    {
        $employees = Employee::with('contact', 'address', 'position')
            ->get()
            ->map(function ($employee) {
                $employee->full_name = $employee->getFullNameAttribute();
                $employee->employee_name = $employee->getNameAttribute();
                $employee->photo = $employee->userPhoto();
                unset($employee->contact_id, $employee->address_id, $employee->position_id);
                return $employee;
            })
            ->toArray();

        return $employees;
    }

    public function getEmployeeById($id)
    {
        $employee = Employee::with(['contact', 'address', 'position.unit.direction', 'position.direction'])->findOrFail($id);

        $employeeArray = $employee->toArray();
        $employeeArray['full_name'] = $employee->getFullNameAttribute();
        $employeeArray['employee_name'] = $employee->getNameAttribute();
        $employeeArray['photo'] = $employee->userPhoto() ?: null; // Devolver null si userPhoto es null o vacío
        $employeeArray['email'] = optional($employee->user)->email; // Manejar el caso donde el usuario pueda ser null

        // Manejar posibles nulos en las relaciones
        $employeeArray['contact'] = optional($employee->contact)->toArray();
        $employeeArray['address'] = optional($employee->address)->toArray();
        $employeeArray['position'] = optional($employee->position)->toArray();

        // Obtener unidad y dirección si existen
        $employeeArray['unit'] = optional($employee->position->unit)->toArray();
        $employeeArray['direction'] = optional($employee->position->unit ? $employee->position->unit->direction : $employee->position->direction)->toArray();


        // Eliminar claves innecesarias
        unset($employeeArray['contact_id'], $employeeArray['address_id'], $employeeArray['position_id']);

        return $employeeArray;
    }



    public function createEmployee(Request $request)
    {
        // Iniciar una transacción de base de datos
        return DB::transaction(function () use ($request) {
            try {
                // Crear el registro del empleado con los datos validados del request
                $employeeData = $request->input('employee');
                $employee = new Employee($employeeData);

                // Crear el registro de contacto asociado al empleado
                $contactData = $request->input('employee.contact');
                if ($contactData) {
                    $contact = new Contact($contactData);
                    $contact->save();
                    $employee->contact()->associate($contact);
                }

                // Crear el registro de dirección asociado al empleado
                $addressData = $request->input('employee.address');
                if ($addressData) {
                    $address = new Address($addressData);
                    $address->save();
                    $employee->address()->associate($address);
                }

                // Guardar el empleado
                $employee->save();

                // Generar el nombre de usuario a partir del primer y segundo nombre del empleado
                $userName = $employee->first_name . $employee->last_name;
                $baseUserName = $userName;
                $counter = 1;

                // Verificar si el nombre de usuario ya existe y generar un nombre único
                while (User::where('name', $userName)->exists()) {
                    $userName = $baseUserName . $counter;
                    $counter++;
                }

                // Buscar el estado 'activo' en la tabla 'user_states'
                $activeState = UserState::where('name', 'Activo')->first();

                // Obtener el correo electrónico personal del empleado
                $employeeEmail = $request->input('employee.contact.personal_email');

                // Obtener el rol 'Empleado'
                $employeeRole = Role::where('name', 'Empleado')->first();

                // Generar contras 
                $generatedPassword = Str::random(10);

                // Crear el usuario asociado al empleado
                $user = new User([
                    'name' => $userName,
                    'email' => $employeeEmail,
                    'password' => bcrypt($generatedPassword),
                    'employee_id' => $employee->id,
                    'role_id' => $employeeRole ? $employeeRole->id : null,
                    'user_state_id' => $activeState ? $activeState->id : null,
                ]);
                $user->save();


                // Disparar el evento EmployeeCreated
                 event(new EmployeeCreated($employee, $generatedPassword));

                return $employee;
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        });
    }


    public function updateEmployee($employeeId, $requestData)
    {
        // Buscar al empleado por su ID
        $employee = Employee::findOrFail($employeeId);

        // Iniciar una transacción de base de datos
        return DB::transaction(function () use ($employee, $requestData) {
            try {
                // Actualizar los datos del empleado
                $employee->update($requestData['employee']);

                // Actualizar los datos de contacto si se proporcionan
                if (isset($requestData['employee']['contact'])) {
                    $contactData = $requestData['employee']['contact'];
                    $contact = $employee->contact ?? new Contact();
                    $contact->fill($contactData);
                    $contact->save();
                    $employee->contact()->associate($contact);
                }

                // Actualizar los datos de dirección si se proporcionan
                if (isset($requestData['employee']['address'])) {
                    $addressData = $requestData['employee']['address'];
                    $address = $employee->address ?? new Address();
                    $address->fill($addressData);
                    $address->save();
                    $employee->address()->associate($address);
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            return $employee;
        });
    }

    public function deleteEmployee($id)
    {
        // Iniciar una transacción de base de datos
        return DB::transaction(function () use ($id) {
            try {
                // Buscar al empleado por su ID
                $employee = Employee::findOrFail($id);

                // Eliminar los registros relacionados en la tabla employees
                $employee->formalEducations()->delete();
                $employee->trainings()->delete();
                $employee->workExperiences()->delete();
                $employee->workReferences()->delete();
                $employee->publications()->delete();

                // Eliminar el registro de contacto asociado al empleado, si existe
                if ($employee->contact) {
                    $employee->contact->delete();
                }

                // Eliminar el registro de dirección asociado al empleado, si existe
                if ($employee->address) {
                    $employee->address->delete();
                }

                // Eliminar el usuario asociado al empleado, si existe
                if ($employee->user) {
                    $employee->user->delete();
                }

                $employee->delete();

                return 'Empleado eliminado con éxito';
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        });
    }

}