<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\CreateContractRequest;
use App\Http\Requests\Contracts\RenewContractRequest;
use App\Http\Requests\Contracts\TerminateContractRequest;
use App\Services\Contracts\ContractService;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    /**
     * Listar todos los contratos.
     */
    public function index(): JsonResponse
    {
        return $this->contractService->getAllContracts(true); // Incluye contratos eliminados lógicamente
    }

    /**
     * Crear un nuevo contrato.
     */
    public function store(CreateContractRequest $request): JsonResponse
    {
        return $this->contractService->createContract($request->validated());
    }

    /**
     * Mostrar un contrato específico.
     */
    public function show(string $id): JsonResponse
    {
        return $this->contractService->getContractById($id, true); // Incluye eliminados
    }

    /**
     * Renovar un contrato existente.
     */
    public function renew(string $id): JsonResponse
    {
        return $this->contractService->renewContract($id);
    }
    

    /**
     * Terminar un contrato.
     */
    public function terminate(TerminateContractRequest $request, string $id): JsonResponse
    {
        return $this->contractService->terminateContract($id, $request->validated()['reason']);
    }
}
