<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\WeeklyScheduleService;
use Illuminate\Http\JsonResponse;

class WeeklyScheduleController extends Controller
{
    protected $weeklyScheduleService;

    public function __construct(WeeklyScheduleService $weeklyScheduleService)
    {
        $this->weeklyScheduleService = $weeklyScheduleService;
    }

    /**
     * Obtener el horario semanal de un empleado.
     */
    public function getWeeklySchedule(int $employeeId): JsonResponse
    {
        return $this->weeklyScheduleService->getWeeklySchedule($employeeId);
    }
}
