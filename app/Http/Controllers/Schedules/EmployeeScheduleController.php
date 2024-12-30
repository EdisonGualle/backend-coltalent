<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\CreateEmployeeScheduleRequest;
use App\Http\Requests\Schedules\ChangeEmployeeScheduleRequest;
use App\Services\Schedules\EmployeeScheduleService;
use Illuminate\Http\JsonResponse;

class EmployeeScheduleController extends Controller
{
    protected $service;

    public function __construct(EmployeeScheduleService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar todas las asignaciones de horarios.
     */
    public function index(): JsonResponse
    {
        return $this->service->getAllEmployeeSchedules(false); 
    }

    /**
     * Obtener asignaciones activas de un empleado.
     */
    public function activeSchedules(int $employee_id): JsonResponse
    {
        return $this->service->getActiveSchedulesByEmployee($employee_id);
    }

    /**
     * Asignar un nuevo horario a un empleado.
     */
    public function store(CreateEmployeeScheduleRequest $request, int $employee_id): JsonResponse
    {
        return $this->service->createEmployeeSchedule($employee_id, $request->validated());
    }

    /**
     * Cambiar el horario activo de un empleado.
     */
    public function change(ChangeEmployeeScheduleRequest $request, int $employee_id): JsonResponse
    {
        return $this->service->changeEmployeeSchedule($employee_id, $request->validated());
    }

    /**
     * Eliminar lógicamente una asignación.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->service->deleteEmployeeSchedule($id);
    }

    /**
     * Restaurar una asignación eliminada.
     */
    public function restore(int $id): JsonResponse
    {
        return $this->service->restoreEmployeeSchedule($id);
    }
}
