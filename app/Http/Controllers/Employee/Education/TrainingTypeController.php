<?php

namespace App\Http\Controllers\Employee\Education;

use App\Http\Controllers\Controller;
use App\Models\Employee\Education\TrainingType;
use Illuminate\Http\Request;

class TrainingTypeController extends Controller
{
    public function index() 
    {
        try {
            $trainingTypes = TrainingType::all();
            return $this->respondWithSuccess('Tipos de formación recuperados exitosamente', $trainingTypes);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los tipos de formación', 500);
        }
    }
    
    public function show($id)
    {
        try {
            $trainingType = TrainingType::find($id);
            if (!$trainingType) {
                return $this->respondWithError('Tipo de formación no encontrado', 404);
            }
            return $this->respondWithSuccess('Tipo de formación recuperado exitosamente', $trainingType);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el tipo de formación', 500);
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