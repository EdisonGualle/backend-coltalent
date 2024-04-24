<?php

namespace App\Services\Employee\Backgrounds;

use App\Models\Employee\Backgrounds\WorkExperience;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class WorkExperienceService extends ResponseService
{
    public function getWorkExperiences(int $employee_id): JsonResponse
    {
        try {
            $workExperiences = WorkExperience::where('employee_id', $employee_id)->get();
            return $this->successResponse('Lista de experiencias laborales obtenida con éxito', $workExperiences);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de experiencias laborales', 500);
        }
    }

    public function createWorkExperience(int $employee_id, array $data): JsonResponse
    {
        try {
            $workExperienceData = array_merge($data, ['employee_id' => $employee_id]);
            $workExperience = WorkExperience::create($workExperienceData);
            return $this->successResponse('Experiencia laboral creada con éxito', $workExperience, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la experiencia laboral. Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function getWorkExperienceById(int $employee_id, string $id): JsonResponse
    {
        try {
            $workExperience = WorkExperience::where('employee_id', $employee_id)->findOrFail($id);
            return $this->successResponse('Detalles de la experiencia laboral obtenidos con éxito', $workExperience);
        } catch (\Exception $e) {
            return $this->errorResponse('Experiencia laboral no encontrada', 404);
        }
    }

    public function updateWorkExperience(int $employee_id, string $id, array $data): JsonResponse
    {
        try {
            $workExperience = WorkExperience::where('employee_id', $employee_id)->findOrFail($id);
            $workExperience->update($data);
            return $this->successResponse('Experiencia laboral actualizada con éxito', $workExperience);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la experiencia laboral', 500);
        }
    }

    public function deleteWorkExperience(int $employee_id, string $id): JsonResponse
    {
        try {
            $workExperience = WorkExperience::where('employee_id', $employee_id)->findOrFail($id);
            $workExperience->delete();
            return $this->successResponse('Experiencia laboral eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la experiencia laboral', 500);
        }
    }
}
