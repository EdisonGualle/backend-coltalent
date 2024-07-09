<?php

namespace App\Http\Controllers\Address;

use App\Http\Controllers\Controller;
use App\Models\Address\Province;
use App\Models\Address\Canton;
use App\Models\Address\Parish;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    // Obtener todas las provincias
    public function getProvinces()
    {
        $provinces = Province::all();
        return response()->json($provinces);
    }

    // Obtener todos los cantones por provincia
    public function getCantons($province_id)
    {
        $cantons = Canton::where('province_id', $province_id)->get();
        return response()->json($cantons);
    }

    // Obtener todas las parroquias por cantón
    public function getParishes($canton_id)
    {
        $parishes = Parish::where('cantons_id', $canton_id)->get();
        return response()->json($parishes);
    }
}
