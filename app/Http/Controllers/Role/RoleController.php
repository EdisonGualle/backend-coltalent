<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function index() 
    {
        try {
            $roles = Role::all();
            return $this->respondWithSuccess('Roles recuperados exitosamente', $roles);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudieron recuperar los roles', 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::find($id);
            return $this->respondWithSuccess('Role recuperado exitosamente', $role);
        } catch (\Exception $e) {
            return $this->respondWithError('No se pudo recuperar el role', 500);
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