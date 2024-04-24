<?php

namespace App\Http\Controllers\Employee\Education;

use App\Http\Controllers\Controller;

use App\Http\Requests\Employee\Education\StoreFormalEducationRequest;
use App\Http\Requests\Employee\Education\UpdateFormalEducationRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\Education\FormalEducationService;
use Illuminate\Http\JsonResponse;

class FormalEducationController extends Controller
{ 
    private $formalEducationService;

    public function __construct(FormalEducationService $formalEducationService)
    {
        $this->formalEducationService = $formalEducationService;
    }

    public function index($employee_id): JsonResponse
    {
        $employee = Employee::findOrFail($employee_id);
        return $this->formalEducationService->getEducations($employee_id);
    }

    public function store(StoreFormalEducationRequest $request, $employee_id): JsonResponse
    {
        return $this->formalEducationService->createEducation($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->formalEducationService->getEducationById($employee_id, $id);
    }

    public function update(UpdateFormalEducationRequest $request, $employee_id, string $id): JsonResponse
    {
        return $this->formalEducationService->updateEducation($employee_id, $id, $request->validated());
    }

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->formalEducationService->deleteEducation($employee_id, $id);
    }
}
