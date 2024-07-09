<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Direction;
use App\Models\Organization\Unit;
use App\Models\Organization\Position;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    // Obtener todas las direcciones
    public function getDirections()
    {
        $directions = Direction::all();
        return response()->json($directions);
    }

    // Obtener unidades y cargos asociados a una dirección
    public function getUnitsAndPositions($directionId)
    {
        $units = Unit::where('direction_id', $directionId)->with('positions')->get();
        $positions = Position::where('direction_id', $directionId)->whereNull('unit_id')->get(); // Posiciones que no están asociadas a una unidad
        return response()->json(['units' => $units, 'positions' => $positions]);
    }

    // Obtener cargos específicos de una unidad
    public function getPositions($unitId)
    {
        $positions = Position::where('unit_id', $unitId)->get();
        return response()->json($positions);
    }
}
