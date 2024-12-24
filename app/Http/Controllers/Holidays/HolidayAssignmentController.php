<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Http\Requests\Holidays\CreateHolidayAssignmentRequest;
use App\Http\Requests\Holidays\DeleteHolidayAssignmentsRequest;
use App\Services\Holidays\HolidayAssignmentService;
use Illuminate\Http\JsonResponse;

class HolidayAssignmentController extends Controller
{
    protected $holidayAssignmentService;

    public function __construct(HolidayAssignmentService $holidayAssignmentService)
    {
        $this->holidayAssignmentService = $holidayAssignmentService;
    }

    /**
     * Crear asignaciones.
     */
    public function store(CreateHolidayAssignmentRequest $request, int $holidayId): JsonResponse
    {
        return $this->holidayAssignmentService->createAssignments($holidayId, $request->validated()['employee_ids']);
    }

    /**
     * Obtener todas las asignaciones activas.
     */
    public function index(): JsonResponse
    {
        return $this->holidayAssignmentService->getAllAssignments();
    }

    /**
     * Obtener asignaciones activas de un empleado.
     */
    public function showByEmployee(int $employeeId): JsonResponse
    {
        return $this->holidayAssignmentService->getAssignmentsByEmployee($employeeId);
    }

    /**
     * Eliminar múltiples asignaciones.
     */
    public function destroy(DeleteHolidayAssignmentsRequest $request): JsonResponse
    {
        return $this->holidayAssignmentService->deleteAssignments($request->validated()['assignment_ids']);
    }
}
