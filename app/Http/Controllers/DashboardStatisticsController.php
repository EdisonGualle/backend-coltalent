<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Organization\Direction;
use App\Models\Organization\Unit;
use App\Models\Organization\Position;
use App\Models\Leave\LeaveType;
use App\Models\Leave\Leave;
use Illuminate\Http\Request;

class DashboardStatisticsController extends Controller
{
    // Obtener las estadísticas del dashboard
    public function getStatistics()
    {
        $totalUsuarios = User::count();
        $totalEmpleados = Employee::count();
        $totalDirecciones = Direction::count();
        $totalUnidades = Unit::count();
        $totalCargos = Position::count();
        $totalTiposPermisos = LeaveType::count();
        $totalSolicitudesPermisos = Leave::count();

        return response()->json([
            'totalUsuarios' => $totalUsuarios,
            'totalEmpleados' => $totalEmpleados,
            'totalDirecciones' => $totalDirecciones,
            'totalUnidades' => $totalUnidades,
            'totalCargos' => $totalCargos,
            'totalTiposPermisos' => $totalTiposPermisos,
            'totalSolicitudesPermisos' => $totalSolicitudesPermisos
        ]);
    }


    // Obtener estadísticas de permisos por estado para un empleado solicitante
    public function getSolicitudesPermisosPorEmpleado($employeeId)
    {
        $totalSolicitudes = Leave::where('employee_id', $employeeId)->count();
        $totalAprobados = Leave::where('employee_id', $employeeId)->whereHas('comments', function($query) {
            $query->where('action', 'Aprobado');
        })->count();
        $totalRechazados = Leave::where('employee_id', $employeeId)->whereHas('comments', function($query) {
            $query->where('action', 'Rechazado');
        })->count();

        return response()->json([
            'totalSolicitudes' => $totalSolicitudes,
            'totalAprobados' => $totalAprobados,
            'totalRechazados' => $totalRechazados
        ]);
    }
}
