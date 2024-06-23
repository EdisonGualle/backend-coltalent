<?php

namespace App\Http\Controllers\Employee\Backgrounds;

use App\Http\Controllers\Controller;
use App\Models\Employee\Backgrounds\PublicationType;
use Illuminate\Http\Request;

class PublicationTypeController extends Controller
{
    public function index()
    {
        try {
            $publicationTypes = PublicationType::all();
            return $this->respondWithSuccess('Tipos de publicación recuperados exitosamente', $publicationTypes);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los tipos de publicación', 500);
        }
    }

    public function show($id)
    {
        try {
            $publicationType = PublicationType::find($id);
            return $this->respondWithSuccess('Tipo de publicación recuperado exitosamente', $publicationType);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el tipo de publicación', 500);
        }
    }

    private function respondWithSuccess(string $message, $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'msg' => $message,
            'data' => $data,
        ]);
    }

    private function respondWithError(string $message, int $statusCode): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => false,
            'msg' => $message,
        ], $statusCode);
    }
}
