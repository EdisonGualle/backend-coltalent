<?php

namespace App\Http\Controllers\Employee\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Education\StoreTrainingRequest;
use App\Http\Requests\Employee\Education\UpdateTrainingRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\Education\TrainingService;
use Illuminate\Http\JsonResponse;

class TrainingController extends Controller
{ 
    private $trainingService;

    public function __construct(TrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    public function index($employee_id): JsonResponse
    {
        $employee = Employee::findOrFail($employee_id);
        return $this->trainingService->getTrainings($employee_id);
    }

    public function store(StoreTrainingRequest $request, $employee_id): JsonResponse
    {
        return $this->trainingService->createTraining($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->trainingService->getTrainingById($employee_id, $id);
    }

    public function update(UpdateTrainingRequest $request, $employee_id, string $id): JsonResponse
    {
        return $this->trainingService->updateTraining($employee_id, $id, $request->validated());
    }

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->trainingService->deleteTraining($employee_id, $id);
    }
}
