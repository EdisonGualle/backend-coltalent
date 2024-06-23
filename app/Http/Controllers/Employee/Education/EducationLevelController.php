<?php

namespace App\Http\Controllers\Employee\Education;

use App\Http\Controllers\Controller;
use App\Models\Employee\Education\EducationLevel;
use Illuminate\Http\Request;

class EducationLevelController extends Controller
{
    public function index() 
    {
        try {
            $educationLevels = EducationLevel::all();
            return $this->respondWithSuccess('Niveles de educación recuperados exitosamente', $educationLevels);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los niveles de educación', 500);
        }
    }
    
    public function show($id)
    {
        try {
            $educationLevel = EducationLevel::find($id);
            return $this->respondWithSuccess('Nivel de educación recuperado exitosamente', $educationLevel);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el nivel de educación', 500);
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
