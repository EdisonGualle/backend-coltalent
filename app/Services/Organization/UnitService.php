<?php

namespace App\Services\Organization;

use App\Models\Employee\Employee;
use App\Models\Organization\Department;
use App\Models\Organization\Unit;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;


class UnitService extends ResponseService
{

    public function getAllUnits(): JsonResponse
    {
        try {
            $units = Unit::with('headEmployee', 'department')
                ->get()
                ->map(function ($unit) {
                    $fullName = null;
                    $userPhoto = null;

                    if ($unit->headEmployee) {
                        $fullName = $unit->headEmployee->getFullNameAttribute();

                        // Cargar la relación user solo si se necesita
                        $user = $unit->headEmployee->user;

                        if ($user) {
                            $userPhoto = $user->photo;
                        }
                    }

                    $unitData = [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'function' => $unit->function,
                        'phone' => $unit->phone,
                        'head_employee_full_name' => $fullName,
                        'head_employee_photo' => $userPhoto,
                        'department' => $unit->department,
                    ];

                    return $unitData;
                });

            return $this->successResponse('Lista de unidades obtenida con éxito', $units, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de unidades: ' . $e->getMessage(), 500);
        }
    }



    public function createUnit(array $data): JsonResponse
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:units,name',
            'function' => 'required|string|max:255',
            'phone' => 'string|max:10',
            'head_employee_id' => 'required|exists:employees,id|unique:units,head_employee_id',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $unit = Unit::create($data);

            // Obtener el empleado jefe de unidad con su información completa
            $headEmployee = Employee::with('user')->find($data['head_employee_id']);
            $fullName = $headEmployee ? $headEmployee->getFullNameAttribute() : null;
            $userPhoto = $headEmployee && $headEmployee->user ? $headEmployee->user->photo : null;

            // Obtener información completa del departamento
            $department = Department::find($data['department_id']);

            $unitData = [
                'id' => $unit->id,
                'name' => $unit->name,
                'function' => $unit->function,
                'phone' => $unit->phone,
                'head_employee_full_name' => $fullName,
                'head_employee_photo' => $userPhoto,
                'department' => $department ? $department->toArray() : null,
            ];

            return $this->successResponse('Unidad creada con éxito', $unitData, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function getUnitById(string $id): JsonResponse
    {
        try {
            $unit = Unit::with('headEmployee.user', 'department')->findOrFail($id);

            // Obtener el nombre completo del empleado
            $fullName = $unit->headEmployee ? $unit->headEmployee->getFullNameAttribute() : null;

            // Obtener la foto del jefe de unidad
            $userPhoto = $unit->headEmployee && $unit->headEmployee->user ? $unit->headEmployee->user->photo : null;

            // Obtener información completa del departamento
            $department = $unit->department ? $unit->department->toArray() : null;

            $unitData = [
                'id' => $unit->id,
                'name' => $unit->name,
                'function' => $unit->function,
                'phone' => $unit->phone,
                'head_employee_full_name' => $fullName,
                'head_employee_photo' => $userPhoto,
                'department' => $department,
            ];

            return $this->successResponse('Detalles de la unidad obtenidos con éxito', $unitData);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unidad no encontrada', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la unidad: ' . $e->getMessage(), 500);
        }
    }
    public function updateUnit(string $id, array $data): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:units,name,' . $unit->id,
            'function' => 'string|max:255',
            'phone' => 'digits:10',
            'head_employee_id' => 'exists:employees,id',
            'department_id' => 'exists:departments,id|unique:units,head_employee_id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $unit->update($data);

            // Obtener el empleado jefe de unidad con su información completa
            $headEmployee = Employee::with('user')->find($data['head_employee_id'] ?? $unit->head_employee_id);
            $fullName = $headEmployee ? $headEmployee->getFullNameAttribute() : null;
            $userPhoto = $headEmployee && $headEmployee->user ? $headEmployee->user->photo : null;

            // Obtener información completa del departamento
            $department = Department::find($data['department_id'] ?? $unit->department_id);

            $unitData = [
                'id' => $unit->id,
                'name' => $unit->name,
                'function' => $unit->function,
                'phone' => $unit->phone,
                'head_employee_full_name' => $fullName,
                'head_employee_photo' => $userPhoto,
                'department' => $department ? $department->toArray() : null,
            ];

            return $this->successResponse('Unidad actualizada con éxito', $unitData, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la unidad: ' . $e->getMessage(), 500);
        }
    }
    public function deleteUnit(string $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        try {
            $unit->delete();
            return $this->successResponse('Unidad eliminada con éxito', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la unidad', 500);
        }
    }
}