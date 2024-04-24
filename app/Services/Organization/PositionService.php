<?php

namespace App\Services\Organization;

use App\Models\Organization\Position;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;


class PositionService extends ResponseService
{
    public function getAllPositions(): JsonResponse
    {
        try {
            $positions = Position::with('unit:id,name')
                ->get()
                ->map(function ($position) {
                    $positionData = $position->only([
                        'id', 'name', 'function'
                    ]);
    
                    $positionData['unit'] = $position->unit ? [
                        'id' => $position->unit->id,
                        'name' => $position->unit->name,
                    ] : null;
    
                    return $positionData;
                });
    
            return $this->successResponse('Lista de posiciones obtenida con éxito', $positions);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de posiciones: ' . $e->getMessage(), 500);
        }
    }

    public function createPosition(array $data): JsonResponse
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:positions,name',
            'function' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
        ]);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $position = Position::create($data);
    
            $positionData = $position->only([
                'id', 'name', 'function'
            ]);
    
            $positionData['unit'] = $position->unit ? [
                'id' => $position->unit->id,
                'name' => $position->unit->name,
            ] : null;
    
            return $this->successResponse('Posición creada con éxito', $positionData, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la posición: ' . $e->getMessage(), 500);
        }
    }

    public function getPositionById(string $id): JsonResponse
    {
        try {
            $position = Position::with('unit:id,name')->findOrFail($id);
    
            $positionData = $position->only([
                'id', 'name', 'function'
            ]);
    
            $positionData['unit'] = $position->unit ? [
                'id' => $position->unit->id,
                'name' => $position->unit->name,
            ] : null;
    
            return $this->successResponse('Detalles de la posición obtenidos con éxito', $positionData);
        } catch (\Exception $e) {
            return $this->errorResponse('Posición no encontrada', 404);
        }
    }

    public function updatePosition(string $id, array $data): JsonResponse
    {
        $position = Position::findOrFail($id);
    
        $validator = Validator::make($data, [
            'name' => 'string|max:255|unique:positions,name,' . $position->id,
            'function' => 'string|max:255',
            'unit_id' => 'exists:units,id',
        ]);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            $position->update($data);
    
            $positionData = $position->only([
                'id', 'name', 'function'
            ]);
    
            $positionData['unit'] = $position->unit ? [
                'id' => $position->unit->id,
                'name' => $position->unit->name,
            ] : null;
    
            return $this->successResponse('Posición actualizada con éxito', $positionData);
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