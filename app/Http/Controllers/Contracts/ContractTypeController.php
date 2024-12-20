<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\CreateContractTypeRequest;
use App\Http\Requests\Contracts\UpdateContractTypeRequest;
use App\Services\Contracts\ContractTypeService;
use Illuminate\Http\JsonResponse;

class ContractTypeController extends Controller
{
    protected $contractTypeService;

    public function __construct(ContractTypeService $contractTypeService)
    {
        $this->contractTypeService = $contractTypeService;
    }

    /**
     * Mostrar todos los tipos de contrato.
     */
    public function index(): JsonResponse
    {
        return $this->contractTypeService->getAllContractTypes();
    }

    /**
     * Crear un nuevo tipo de contrato.
     */
    public function store(CreateContractTypeRequest $request): JsonResponse
    {
        return $this->contractTypeService->createContractType($request->validated());
    }

    /**
     * Mostrar un tipo de contrato específico.
     */
    public function show(string $id): JsonResponse
    {
        return $this->contractTypeService->getContractTypeById($id);
    }

    /**
     * Actualizar un tipo de contrato.
     */
    public function update(UpdateContractTypeRequest $request, string $id): JsonResponse
    {
        return $this->contractTypeService->updateContractType($id, $request->validated());
    }

    /**
     * Eliminar un tipo de contrato.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->contractTypeService->deleteContractType($id);
    }

    /**
     * Restaurar un tipo de contrato eliminado.
     */
    public function restore(string $id): JsonResponse
    {
        return $this->contractTypeService->restoreContractType($id);
    }
}
