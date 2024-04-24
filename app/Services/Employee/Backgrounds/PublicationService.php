<?php

namespace App\Services\Employee\Backgrounds;

use App\Models\Employee\Backgrounds\Publication;
use Illuminate\Http\JsonResponse;
use App\Services\ResponseService;

class PublicationService extends ResponseService
{
    public function getPublications(int $employee_id): JsonResponse
    {
        try {
            $publications = Publication::where('employee_id', $employee_id)->with('publicationType:id,name')->get();
            return $this->successResponse('Lista de publicaciones obtenida con éxito', $publications);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la lista de publicaciones', 500);
        }
    }

    public function createPublication(int $employee_id, array $data): JsonResponse
    {
        try {
            $publicationData = array_merge($data, ['employee_id' => $employee_id]);
            $publication = Publication::create($publicationData);  
            $publication->load('publicationType:id,name');                         //Carga los detalles de el tipo de publicacion
            return $this->successResponse('Publicación creada con éxito', $publication, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo crear la publicación. Error: ' . $e->getMessage(), 500);
        }
    }
    
    
    public function getPublicationById(int $employee_id, string $id): JsonResponse
    {
        try {
            $publication = Publication::where('employee_id', $employee_id)->with('publicationType:id,name')->findOrFail($id);
            return $this->successResponse('Detalles de la publicación obtenidos con éxito', $publication);
        } catch (\Exception $e) {
            return $this->errorResponse('Publicación no encontrada', 404);
        }
    }

    public function updatePublication(int $employee_id, string $id, array $data): JsonResponse
    {
        try {
            $publication = Publication::where('employee_id', $employee_id)->findOrFail($id);
            $publication->update($data);
            $publication->load('publicationType:id,name');
            return $this->successResponse('Publicación actualizada con éxito', $publication);
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo actualizar la publicación', 500);
        }
    }
    

    public function deletePublication(int $employee_id, string $id): JsonResponse
    {
        try {
            $publication = Publication::where('employee_id', $employee_id)->findOrFail($id);
            $publication->delete();
            return $this->successResponse('Publicación eliminada con éxito');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo eliminar la publicación', 500);
        }
    }
}
