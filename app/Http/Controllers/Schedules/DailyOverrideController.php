<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\CreateDailyOverrideRequest;
use App\Http\Requests\Schedules\UpdateDailyOverrideRequest;
use App\Services\Schedules\DailyOverrideService;
use Illuminate\Http\JsonResponse;

class DailyOverrideController extends Controller
{
    protected $service;

    public function __construct(DailyOverrideService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return $this->service->getAllOverrides(true);
    }

    public function store(CreateDailyOverrideRequest $request): JsonResponse
    {
        return $this->service->createDailyOverride($request->validated());
    }

    public function update(UpdateDailyOverrideRequest $request, int $id): JsonResponse
    {
        return $this->service->updateDailyOverride($id, $request->validated());
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->service->deleteDailyOverride($id);
    }

    public function restore(int $id): JsonResponse
    {
        return $this->service->restoreDailyOverride($id);
    }

    public function getByEmployee(int $employee_id): JsonResponse
{
    return $this->service->getOverridesByEmployee($employee_id, true);
}

}
