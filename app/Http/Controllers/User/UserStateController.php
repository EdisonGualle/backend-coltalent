<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Other\UserState;
use Illuminate\Http\Request;

class UserStateController extends Controller
{
    
    public function index() 
    {
        try {
            $userStates = UserState::all();
            return $this->respondWithSuccess('Estados de usuario recuperados exitosamente', $userStates);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los estados de usuario', 500);
        }
    }
    
    public function show($id)
    {
        try {
            $userState = UserState::find($id);
            return $this->respondWithSuccess('Estado de usuario recuperado exitosamente', $userState);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el estado de usuario', 500);
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
