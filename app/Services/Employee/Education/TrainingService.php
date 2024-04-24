<?php

namespace App\Services\Employee\Education;

use App\Models\Employee\Education\Training;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class TrainingService extends ResponseService
{
    public function getTrainings(int $employee_id): JsonResponse
    {
        try {
            $trainings = Training::where('employee_id', $employee_id)->get();
            return $this->successResponse('Lista de cursos de capacitación obtenida con éxito', $trainings);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de cursos de capacitación', 500);
        }
    }

    public function createTraining(int $employee_id, array $data): JsonResponse
    {
        try {
            $trainingData = array_merge($data, ['employee_id' => $employee_id]);
            $training = Training::create($trainingData);
            return $this->successResponse('Curso de capacitación creado con éxito', $training, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el curso de capacitación', 500);
        }
    }

    public function getTrainingById(int $employee_id, string $id): JsonResponse
    {
        try {
            $training = Training::where('employee_id', $employee_id)->findOrFail($id);
  
            return $this->successResponse('Detalles del curso de capacitación obtenido con éxito', $training);
        } catch (\Exception $e) {
            return $this->errorResponse('Curso de capacitación no encontrado', 404);
        }
    }

    public function updateTraining(int $employee_id, string $id, array $data): JsonResponse
    {
        try {
            $training = Training::where('employee_id', $employee_id)->findOrFail($id);
            $training->update($data);
            return $this->successResponse('Curso de capacitación actualizado con éxito', $training);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el curso de capacitación', 500);
        }
    }

    public function deleteTraining(int $employee_id, string $id): JsonResponse
    {
        try {
            $training = Training::where('employee_id', $employee_id)->findOrFail($id);
            $training->delete();
            return $this->successResponse('Curso de capacitación eliminado con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el curso de capacitación', 500);
        }
    }
}
