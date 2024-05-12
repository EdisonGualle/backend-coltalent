<?php

namespace App\Services\Organization;

use App\Models\Organization\Department;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartmentService extends ResponseService
{
    
    private function formatDepartmentData(Department $department): array
    {
        $headEmployee = optional($department->headEmployeeDepartament);
        $user = optional($headEmployee->user);
    
        return array_merge(
            $department->only('id', 'name', 'function'),
            [
                'head_employee_id' => $headEmployee->id,
                'head_employee_name' => $headEmployee->getNameAttribute(),
                'head_employee_photo' => $user->photo,
            ]
        );
    }
    
    public function getAllDepartments(): JsonResponse
    {
        try {
            $departments = Department::with('headEmployeeDepartament.user')
                ->get()
                ->map(function ($department) {
                    return $this->formatDepartmentData($department);
                });
    
            return $this->successResponse('Lista de departamentos obtenida con éxito', $departments, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de departamentos: ' . $e->getMessage(), 500);
        }
    }
    
    public function createDepartment(array $data): JsonResponse
    {
        $messages = [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una dirección con ese nombre.',
            'function.required' => 'La función es requerida.',
            'function.string' => 'La función debe ser una cadena de texto.',
            'function.max' => 'La función no puede tener más de 255 caracteres.',
            'head_employee_id.required' => 'El ID del jefe del departamento es requerido.',
            'head_employee_id.exists' => 'El empleado especificado no existe.',
            'head_employee_id.unique' => 'Este empleado ya es jefe de otra dirección.',
        ];
    
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:departments,name',
            'function' => 'required|string|max:255',
            'head_employee_id' => 'required|exists:employees,id|unique:departments,head_employee_id',
        ], $messages);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $department = Department::create($data);
    
            // Cargar las relaciones
            $department->load('headEmployeeDepartament.user');
    
            $departmentData = $this->formatDepartmentData($department);
    
            return $this->successResponse('Departamento creado con éxito', $departmentData, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el departamento: ' . $e->getMessage(), 500);
        }
    }
    

    public function getDepartmentById(string $id): JsonResponse
    {
        try {
            $department = Department::with('headEmployeeDepartament.user')->findOrFail($id);
            $departmentData = $this->formatDepartmentData($department);

            return $this->successResponse('Detalles del departamento obtenidos con éxito', $departmentData, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Departamento no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el departamento: ' . $e->getMessage(), 500);
        }
    }

    public function updateDepartment(string $id, array $data): JsonResponse
    {
        $department = Department::findOrFail($id);
    
    
        $messages = [
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una dirección con ese nombre.',
            'function.string' => 'La función debe ser una cadena de texto.',
            'function.max' => 'La función no puede tener más de 255 caracteres.',
            'head_employee_id.exists' => 'El empleado especificado no existe.',
            'head_employee_id.unique' => 'Este empleado ya es jefe de otra dirección.',
        ];
    
        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:departments,name,' . $department->id,
            'function' => 'string|max:255',
            'head_employee_id' => 'exists:employees,id|unique:departments,head_employee_id,' . $department->id,
        ], $messages);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $department->update($data);
            // Cargar las relaciones
            $department->load('headEmployeeDepartament.user');
    
            $departmentData = $this->formatDepartmentData($department);
    
            return $this->successResponse('Departamento actualizado con éxito', $departmentData, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el departamento: ' . $e->getMessage(), 500);
        }
    }

    public function deleteDepartment(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        try {
            $department->delete();
            return $this->successResponse('Departamento eliminado con éxito', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el departamento: ' . $e->getMessage(), 500);
        }
    }
}
