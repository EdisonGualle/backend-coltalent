<?php

namespace App\Http\Controllers\Employee\Backgrounds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Backgrounds\StorePublicationRequest;
use App\Http\Requests\Employee\Backgrounds\UpdatePublicationRequest;
use App\Services\Employee\Backgrounds\PublicationService;
use Illuminate\Http\JsonResponse;

class PublicationController extends Controller
{ 
    private $publicationService;

    public function __construct(PublicationService $publicationService)
    {
        $this->publicationService = $publicationService;
    }

    public function index($employee_id): JsonResponse
    {
        return $this->publicationService->getPublications($employee_id);
    }

    public function store(StorePublicationRequest $request, $employee_id): JsonResponse
    {
        return $this->publicationService->createPublication($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->publicationService->getPublicationById($employee_id, $id);
    }

    public function update(UpdatePublicationRequest $request, $employee_id, string $id)
    {
        return $this->publicationService->updatePublication($employee_id, $id, $request->validated());
    }
    
    

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->publicationService->deletePublication($employee_id, $id);
    }
}
