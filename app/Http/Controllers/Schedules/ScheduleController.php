<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\CreateScheduleRequest;
use App\Http\Requests\Schedules\UpdateScheduleRequest;
use App\Services\Schedules\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Obtener todos los horarios.
     */
    public function index(): JsonResponse
    {
        return $this->scheduleService->getAllSchedules(true); // Incluye eliminados
    }

    /**
     * Crear un nuevo horario.
     */
    public function store(CreateScheduleRequest $request): JsonResponse
    {
        return $this->scheduleService->createSchedule($request->validated());
    }

    /**
     * Mostrar un horario específico.
     */
    public function show(int $id): JsonResponse
    {
        return $this->scheduleService->getScheduleById($id);
    }

    /**
     * Actualizar un horario existente.
     */
    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        return $this->scheduleService->updateSchedule($id, $request->validated());
    }

    /**
     * Eliminar un horario lógicamente.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->scheduleService->deleteSchedule($id);
    }

    /**
     * Restaurar un horario eliminado lógicamente.
     */
    public function restore(int $id): JsonResponse
    {
        return $this->scheduleService->restoreSchedule($id);
    }
}
