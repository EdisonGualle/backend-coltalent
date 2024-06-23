<?php

namespace App\Http\Controllers\Employee\Backgrounds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Backgrounds\StoreLanguageRequest;
use App\Http\Requests\Employee\Backgrounds\UpdateLanguageRequest;
use App\Services\Employee\Backgrounds\LanguageService;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    private $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function index($employee_id): JsonResponse
    {
        return $this->languageService->getLanguages($employee_id);
    }

    public function store(StoreLanguageRequest $request, $employee_id): JsonResponse
    {
        return $this->languageService->createLanguage($employee_id, $request->validated());
    }

    public function show($employee_id, string $id): JsonResponse
    {
        return $this->languageService->getLanguageById($employee_id, $id);
    }

    public function update(UpdateLanguageRequest $request, $employee_id, string $id): JsonResponse
    {
        return $this->languageService->updateLanguage($employee_id, $id, $request->validated());
    }

    public function destroy($employee_id, string $id): JsonResponse
    {
        return $this->languageService->deleteLanguage($employee_id, $id);
    }
}
