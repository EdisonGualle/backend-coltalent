<?php

namespace App\Http\Controllers\Employee\Backgrounds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Backgrounds\StoreWorkReferenceRequest;
use App\Http\Requests\Employee\Backgrounds\UpdateWorkReferenceRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\Backgrounds\WorkReferenceService;
use Illuminate\Http\JsonResponse;

class WorkReferenceController extends Controller
{ 
    private $workReferenceService;

    public function __construct(WorkReferenceService $workReferenceService)
    {
        $this->workReferenceService = $workReferenceService;
    }

    public function index($employee_id): JsonResponse
    {
        return $this->workReferenceService->getWorkReferences($employee_id);
    }

    public function store(StoreWorkReferenceRequest $request, $employee_id): JsonResponse
    {
        return $this->workReferenceService->createWorkReference($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->workReferenceService->getWorkReferenceById($employee_id, $id);
    }

    public function update(UpdateWorkReferenceRequest $request, $employee_id, string $id)
    {
        $response = $this->workReferenceService->updateWorkReference($employee_id, $id, $request->validated());
        
        return response()->json($response);
    }
    

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->workReferenceService->deleteWorkReference($employee_id, $id);
    }
}
