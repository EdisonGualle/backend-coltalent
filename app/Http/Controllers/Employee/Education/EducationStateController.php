<?php
namespace App\Http\Controllers\Employee\Education;

use App\Http\Controllers\Controller;
use App\Models\Employee\Education\EducationState;
use Illuminate\Http\Request;

class EducationStateController extends Controller
{
    public function index() 
    {
        try {
            $educationStates = EducationState::all();
            return $this->respondWithSuccess('Estados de educación recuperados exitosamente', $educationStates);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los estados de educación', 500);
        }
    }
    
    public function show($id)
    {
        try {
            $educationState = EducationState::find($id);
            return $this->respondWithSuccess('Estado de educación recuperado exitosamente', $educationState);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el estado de educación', 500);
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