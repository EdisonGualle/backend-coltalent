<?php

namespace App\Http\Controllers;

use App\Models\Other\Configuration;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index()
    {
        $configurations = Configuration::all();
        return $this->respondWithSuccess('Lista de configuraciones obtenida con éxito', $configurations);
    }

    public function show($id)
    {
        try {
            $configuration = Configuration::findOrFail($id);
            return $this->respondWithSuccess('Configuración obtenida con éxito', $configuration);
        } catch (\Exception $e) {
            return $this->respondWithError('Configuración no encontrada', 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|unique:configurations|max:255',
            'value' => 'required|max:255',
            'description' => 'nullable',
        ]);

        try {
            $configuration = Configuration::create($request->all());
            return $this->respondWithSuccess('Configuración creada con éxito', $configuration);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al crear la configuración', 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'value' => 'required|max:255',
        ]);

        try {
            $configuration = Configuration::findOrFail($id);
            $configuration->update($request->all());
            return $this->respondWithSuccess('Configuración actualizada con éxito', $configuration);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al actualizar la configuración', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $configuration = Configuration::findOrFail($id);
            $configuration->delete();
            return $this->respondWithSuccess('Configuración eliminada con éxito');
        } catch (\Exception $e) {
            return $this->respondWithError('Error al eliminar la configuración', 500);
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