<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\CalendarService;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    protected $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Generar el calendario para un empleado específico.
     */
    public function generateCalendar(int $employeeId): JsonResponse
    {
        return $this->calendarService->generateEmployeeCalendar($employeeId);
    }
}
