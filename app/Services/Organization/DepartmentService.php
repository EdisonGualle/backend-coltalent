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
    public function getAllDepartments(): JsonResponse
    {
        try {
            $departments = Department::all();
            return $this->successResponse('Lista de departamentos obtenida con éxito', $departments, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de departamentos', 500);
        }
    }

    public function createDepartment(array $data): JsonResponse
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:departments,name',
            'function' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $department = Department::create($data);
            return $this->successResponse('Departamento creado con éxito', $department, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el departamento', 500);
        }
    }

    public function getDepartmentById(string $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);
            return $this->successResponse('Detalles del departamento obtenidos con éxito', $department, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Departamento no encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el departamento', 500);
        }
    }

    public function updateDepartment(string $id, array $data): JsonResponse
    {
        $department = Department::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:departments,name,' . $department->id,
            'function' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $department->update($data);
            return $this->successResponse('Departamento actualizado con éxito', $department, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el departamento', 500);
        }
    }

    public function deleteDepartment(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        try {
            $department->delete();
            return $this->successResponse('Departamento eliminado con éxito', null, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el departamento', 500);
        }
    }
}
