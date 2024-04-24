<?php

namespace App\Services\Employee\Education;

use App\Models\Employee\Education\FormalEducation;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class FormalEducationService extends ResponseService
{
    public function getEducations(int $employee_id): JsonResponse
    {
        try {
            $educations = FormalEducation::where('employee_id', $employee_id)->get();
            return $this->successResponse('Lista de educaciones formales obtenida con éxito', $educations);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de educaciones formales', 500);
        }
    }

    public function createEducation(int $employee_id, array $data): JsonResponse
    {
        try {
            $educationData = array_merge($data, ['employee_id' => $employee_id]);
            $education = FormalEducation::create($educationData);
            return $this->successResponse('Educación formal creada con éxito', $education, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la educación formal. Error: ' . $e->getMessage(), 500);
        }
    }
    

    public function getEducationById(int $employee_id, string $id): JsonResponse
    {
        try {
            $education = FormalEducation::where('employee_id', $employee_id)->findOrFail($id);
            return $this->successResponse('Detalles de la educación formal obtenidos con éxito', $education);
        } catch (\Exception $e) {
            return $this->errorResponse('Educación formal no encontrada', 404);
        }
    }

    public function updateEducation(int $employee_id, string $id, array $data): JsonResponse
    {
        try {
            $education = FormalEducation::where('employee_id', $employee_id)->findOrFail($id);
            $education->update($data);
            return $this->successResponse('Educación formal actualizada con éxito', $education);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la educación formal', 500);
        }
    }

    public function deleteEducation(int $employee_id, string $id): JsonResponse
    {
        try {
            $education = FormalEducation::where('employee_id', $employee_id)->findOrFail($id);
            $education->delete();
            return $this->successResponse('Educación formal eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la educación formal', 500);
        }
    }
}
