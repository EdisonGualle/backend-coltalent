<?php

namespace App\Services\Organization;

use App\Models\Organization\Unit;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;


class UnitService extends ResponseService
{

    private function formatUnitData(Unit $unit): array
    {
        $headEmployee = optional($unit->headEmployee);
        $user = optional($headEmployee->user);

        return array_merge(
            $unit->only('id', 'name', 'function', 'phone'),
            [
                'head_employee_id' => $headEmployee->id,
                'head_employee_name' => $headEmployee->getNameAttribute(),
                'head_employee_photo' => $user->photo,
                'department' => $unit->department ? $unit->department->toArray() : null,
            ]
        );
    }

    public function getAllUnits(): JsonResponse
    {
        try {
            $units = Unit::with('headEmployee', 'department')
                ->get()
                ->map(function ($unit) {
                    return $this->formatUnitData($unit);
                });

            return $this->successResponse('Lista de unidades obtenida con éxito', $units, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de unidades: ' . $e->getMessage(), 500);
        }
    }

    public function createUnit(array $data): JsonResponse
    {
        $messages = [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.string' => 'El nombre de la unidad debe ser una cadena de texto.',
            'name.max' => 'El nombre de la unidad no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una undiad con ese nombre.',
            'function.required' => 'La función de la unidad es obligatoria.',
            'function.string' => 'La función de la unidad debe ser una cadena de texto.',
            'function.max' => 'La función de la unidad no puede tener más de 255 caracteres.',
            'phone.digits' => 'El teléfono de la unidad debe tener 9 dígitos.',
            'head_employee_id.required' => 'El ID del empleado jefe es obligatorio.',
            'head_employee_id.exists' => 'El empleado jefe seleccionado no existe.',
            'head_employee_id.unique' => 'Este empleado ya es jefe de otra unidad.',
            'department_id.required' => 'El ID del departamento es obligatorio.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
        ];
    
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:units,name',
            'function' => 'required|string|max:255',
            'phone' => 'nullable|digits:9',
            'head_employee_id' => 'required|exists:employees,id|unique:units,head_employee_id',
            'department_id' => 'required|exists:departments,id',
        ], $messages);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $unit = Unit::create($data);
            $unit->load('headEmployee.user', 'department');
    
            return $this->successResponse('Unidad creada con éxito', $this->formatUnitData($unit), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function getUnitById(string $id): JsonResponse
    {
        try {
            $unit = Unit::with('headEmployee.user', 'department')->findOrFail($id);

            return $this->successResponse('Detalles de la unidad obtenidos con éxito', $this->formatUnitData($unit));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unidad no encontrada', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la unidad: ' . $e->getMessage(), 500);
        }
    }

    public function updateUnit(string $id, array $data): JsonResponse
    {
        $unit = Unit::findOrFail($id);
    
        $messages = [
            'name.string' => 'El nombre de la unidad debe ser una cadena de texto.',
            'name.max' => 'El nombre de la unidad no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una undiad con ese nombre.',
            'function.string' => 'La función de la unidad debe ser una cadena de texto.',
            'function.max' => 'La función de la unidad no puede tener más de 255 caracteres.',
            'phone.digits' => 'El teléfono de la unidad debe tener 9 dígitos.',
            'head_employee_id.exists' => 'El empleado jefe seleccionado no existe.',
            'head_employee_id.unique' => 'Este empleado ya es jefe de otra unidad.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
        ];
    
        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:units,name,' . $unit->id,
            'function' => 'string|max:255',
            'phone' => 'nullable|digits:9',
            'head_employee_id' => 'exists:employees,id|unique:units,head_employee_id,' . $unit->id,
            'department_id' => 'exists:departments,id',
        ], $messages);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $unit->update($data);
            $unit->load('headEmployee.user', 'department');
    
            return $this->successResponse('Unidad actualizada con éxito', $this->formatUnitData($unit), 200);
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