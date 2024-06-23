<?php

namespace App\Services\Employee\Backgrounds;

use App\Models\Employee\Backgrounds\Language;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class LanguageService extends ResponseService
{
    public function getLanguages(int $employee_id): JsonResponse
    {
        try {
            $languages = Language::where('employee_id', $employee_id)->get();
            return $this->successResponse('Lista de idiomas obtenida con éxito', $languages);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de idiomas', 500);
        }
    }

    public function createLanguage(int $employee_id, array $data): JsonResponse
    {
        try {
            $languageData = array_merge($data, ['employee_id' => $employee_id]);
            $language = Language::create($languageData);
            return $this->successResponse('Idioma creado con éxito', $language, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear el idioma. Error: ' . $e->getMessage(), 500);
        }
    }

    public function getLanguageById(int $employee_id, string $id): JsonResponse
    {
        try {
            $language = Language::where('employee_id', $employee_id)->findOrFail($id);
            return $this->successResponse('Detalles del idioma obtenidos con éxito', $language);
        } catch (\Exception $e) {
            return $this->errorResponse('Idioma no encontrado', 404);
        }
    }

    public function updateLanguage(int $employee_id, string $id, array $data): JsonResponse
    {
        try {
            $language = Language::where('employee_id', $employee_id)->findOrFail($id);
            $language->update($data);
            return $this->successResponse('Idioma actualizado con éxito', $language);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar el idioma', 500);
        }
    }

    public function deleteLanguage(int $employee_id, string $id): JsonResponse
    {
        try {
            $language = Language::where('employee_id', $employee_id)->findOrFail($id);
            $language->delete();
            return $this->successResponse('Idioma eliminado con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar el idioma', 500);
        }
    }
}
