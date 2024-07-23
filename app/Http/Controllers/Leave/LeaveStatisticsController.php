<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave\LeaveComment;
use App\Models\Leave\LeaveType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveStatisticsController extends Controller
{
    public function getAprobacionesPorMes($employeeId)
    {
        $currentMonth = Carbon::now()->month;
        $rawData = LeaveComment::where('commented_by', $employeeId)
            ->join('leaves', 'leave_comments.leave_id', '=', 'leaves.id')
            ->select(
                DB::raw('MONTH(leaves.start_date) as mes'),
                DB::raw('SUM(CASE WHEN leave_comments.action = "Aprobado" THEN 1 ELSE 0 END) as Aprobados'),
                DB::raw('SUM(CASE WHEN leave_comments.action = "Rechazado" THEN 1 ELSE 0 END) as Rechazados'),
                DB::raw('COUNT(*) as Total_Permisos')
            )
            ->whereYear('leaves.start_date', Carbon::now()->year)
            ->groupBy('mes')
            ->get()
            ->keyBy('mes');

        // Crear un arreglo con todos los meses hasta el mes actual y asignar valores por defecto
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        $data = [];
        for ($num = 1; $num <= $currentMonth; $num++) {
            $data[] = [
                'mes' => $meses[$num],
                'Aprobados' => $rawData[$num]->Aprobados ?? 0,
                'Rechazados' => $rawData[$num]->Rechazados ?? 0,
                'Total_Permisos' => $rawData[$num]->Total_Permisos ?? 0,
            ];
        }

        return response()->json(['data' => $data]);
    }
    // Obtener permisos por tipo para el aprobador logueado
    public function getAprobacionesPorTipo($employeeId)
    {
        $leaveTypes = LeaveType::all();
        $rawData = LeaveComment::where('commented_by', $employeeId)
            ->join('leaves', 'leave_comments.leave_id', '=', 'leaves.id')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->select(
                'leave_types.name as Tipo',
                DB::raw('COUNT(*) as Cantidad')
            )
            ->groupBy('Tipo')
            ->get()
            ->keyBy('Tipo');

        // Crear un arreglo con todos los tipos de permiso y asignar valores por defecto
        $data = [];
        foreach ($leaveTypes as $leaveType) {
            $data[] = [
                'Tipo' => $leaveType->name,
                'Cantidad' => $rawData[$leaveType->name]->Cantidad ?? 0,
            ];
        }

        return response()->json(['data' => $data]);
    }
}
