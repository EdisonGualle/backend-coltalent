<?php

namespace App\Services\Organization;

use App\Models\Organization\Position;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;


class PositionService extends ResponseService
{
    private function formatPositionData(Position $position): array
    {
        return array_merge(
            $position->only('id', 'name', 'function'),
            [
                'unit' => $position->unit ? [
                    'id' => $position->unit->id,
                    'name' => $position->unit->name,
                ] : null,
            ]
        );
    }

    public function getAllPositions(): JsonResponse
    {
        try {
            $positions = Position::with('unit:id,name')
                ->get()
                ->map(function ($position) {
                    return $this->formatPositionData($position);
                });

            return $this->successResponse('Lista de posiciones obtenida con éxito', $positions);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de posiciones: ' . $e->getMessage(), 500);
        }
    }

    public function createPosition(array $data): JsonResponse
    {
        $messages = [
            'name.required' => 'El nombre del cargo es obligatorio.',
            'name.string' => 'El nombre del cargo debe ser una cadena de texto.',
            'name.max' => 'El nombre del cargo no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe un cargo con ese nombre.',
            'function.required' => 'La función del cargo es obligatoria.',
            'function.string' => 'La función del cargo debe ser una cadena de texto.',
            'function.max' => 'La función del cargo no puede tener más de 255 caracteres.',
            'unit_id.required' => 'El ID de la unidad es obligatorio.',
            'unit_id.exists' => 'La unidad seleccionada no existe.',
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:positions,name',
            'function' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ], $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $position = Position::create($data);
            $position->load('unit');

            return $this->successResponse('Posición creada con éxito', $this->formatPositionData($position), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la posición: ' . $e->getMessage(), 500);
        }
    }

    public function getPositionById(string $id): JsonResponse
    {
        try {
            $position = Position::with('unit:id,name')->findOrFail($id);

            return $this->successResponse('Detalles de la posición obtenidos con éxito', $this->formatPositionData($position));
        } catch (\Exception $e) {
            return $this->errorResponse('Posición no encontrada', 404);
        }
    }

    public function updatePosition(string $id, array $data): JsonResponse
    {
        $position = Position::findOrFail($id);

        $messages = [
            'name.string' => 'El nombre del cargo  debe ser una cadena de texto.',
            'name.max' => 'El nombre del cargo no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe un cargo con ese nombre.',
            'function.string' => 'La función del cargo debe ser una cadena de texto.',
            'function.max' => 'La función del cargo no puede tener más de 255 caracteres.',
            'unit_id.exists' => 'La unidad seleccionada no existe.',
        ];

        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:positions,name,' . $position->id,
            'function' => 'string|max:255',
            'unit_id' => 'exists:units,id',
        ], $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $position->update($data);
            $position->load('unit');

            return $this->successResponse('Posición actualizada con éxito', $this->formatPositionData($position));
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la posición: ' . $e->getMessage(), 500);
        }
    }

    public function deletePosition(string $id): JsonResponse
    {
        $position = Position::findOrFail($id);

        try {
            $position->delete();
            return $this->successResponse('Posición eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la posición', 500);
        }
    }
}