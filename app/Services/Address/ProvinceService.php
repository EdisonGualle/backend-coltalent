<?php
namespace App\Services;

use App\Models\Address\Province;
use Illuminate\Http\Request;


class ProvinceService
{
    public function getProvinces()
    {
        try {
            $provinces = Province::all();
            return response()->json(['successful' => true, 'data' => $provinces], 200);
        } catch (\Exception $e) {
            // Log de errores o manejo de errores según tus necesidades.
            return response()->json(['successful' => false, 'error' => 'Error al obtener las provincias'], 500);
        }
    }

}