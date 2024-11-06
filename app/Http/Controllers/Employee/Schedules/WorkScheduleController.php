<?php

namespace App\Http\Controllers\Employee\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Schedules\CreateWorkScheduleRequest;
use App\Http\Requests\Employee\Schedules\UpdateWorkScheduleRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\Schedules\WorkScheduleService;
use Illuminate\Http\JsonResponse;

class WorkScheduleController extends Controller
{
    private $workScheduleService;

    public function __construct(WorkScheduleService $workScheduleService)
    {
        $this->workScheduleService = $workScheduleService;
    }

    public function index($employee_id): JsonResponse
    {
        return $this->workScheduleService->getWorkSchedules($employee_id);
    }

    public function store(CreateWorkScheduleRequest $request, $employee_id): JsonResponse
    {
        $schedules = $request->validated();
        return $this->workScheduleService->createMultipleWorkSchedules($employee_id, $schedules);
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->workScheduleService->getWorkScheduleById($employee_id, $id);
    }


    public function updateMultiple(UpdateWorkScheduleRequest $request, $employee_id): JsonResponse
    {
        $schedules = $request->validated(); // Obtener todos los horarios enviados en el request
        return $this->workScheduleService->updateMultipleWorkSchedules($employee_id, $schedules);
    }

    public function update(UpdateWorkScheduleRequest $request, $employee_id): JsonResponse
    {
        $schedules = $request->validated();
        return $this->workScheduleService->updateMultipleWorkSchedules($employee_id, $schedules);
    }

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->workScheduleService->deleteWorkSchedule($employee_id, $id);
    }
}
