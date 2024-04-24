<?php

namespace App\Http\Controllers\Employee\Backgrounds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Backgrounds\StoreWorkExperienceRequest;
use App\Http\Requests\Employee\Backgrounds\UpdateWorkExperienceRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\Backgrounds\WorkExperienceService;
use Illuminate\Http\JsonResponse;

class WorkExperienceController extends Controller
{ 
    private $workExperienceService;

    public function __construct(WorkExperienceService $workExperienceService)
    {
        $this->workExperienceService = $workExperienceService;
    }

    public function index($employee_id): JsonResponse
    {
        return $this->workExperienceService->getWorkExperiences($employee_id);
    }

    public function store(StoreWorkExperienceRequest $request, $employee_id): JsonResponse
    {
        return $this->workExperienceService->createWorkExperience($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->workExperienceService->getWorkExperienceById($employee_id, $id);
    }

    public function update(UpdateWorkExperienceRequest $request, $employee_id, string $id): JsonResponse
    {
        return $this->workExperienceService->updateWorkExperience($employee_id, $id, $request->validated());
    }

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->workExperienceService->deleteWorkExperience($employee_id, $id);
    }
}
