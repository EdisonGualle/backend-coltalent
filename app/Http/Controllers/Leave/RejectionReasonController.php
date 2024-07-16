<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreRejectionReasonRequest;
use App\Http\Requests\Leave\UpdateRejectionReasonRequest;
use App\Services\Leave\RejectionReasonService;

class RejectionReasonController extends Controller
{
    private $rejectionReasonService;

    public function __construct(RejectionReasonService $rejectionReasonService)
    {
        $this->rejectionReasonService = $rejectionReasonService;
    }

    public function index()
    {
        return $this->rejectionReasonService->getAllRejectionReasons();
    }

    // Obtener todas las razones de rechazo, incluyendo las inactivas
    public function indexIncludingDeleted()
    {
        return $this->rejectionReasonService->getAllRejectionReasons(true);
    }


    public function store(StoreRejectionReasonRequest $request)
    {
        return $this->rejectionReasonService->createRejectionReason($request->validated());
    }

    public function show(string $id)
    {
        return $this->rejectionReasonService->getRejectionReasonById($id);
    }

    public function update(UpdateRejectionReasonRequest $request, string $rejection_reason)
    {
        return $this->rejectionReasonService->updateRejectionReason($rejection_reason, $request->validated());
    }

    public function destroy(string $rejection_reason)
    {
        return $this->rejectionReasonService->deleteRejectionReason($rejection_reason);
    }

      // Alternar el estado de activación de una razón de rechazo
      public function toggleStatus(string $id)
      {
          return $this->rejectionReasonService->toggleRejectionReasonStatus($id);
      }
}
