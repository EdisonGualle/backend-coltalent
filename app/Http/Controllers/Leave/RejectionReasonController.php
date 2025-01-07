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

    public function indexIncludingDeleted()
    {
        return $this->rejectionReasonService->getAllRejectionReasons(true);
    }

    public function store(StoreRejectionReasonRequest $request)
    {
        return $this->rejectionReasonService->createRejectionReason($request->validated());
    }

    public function update(UpdateRejectionReasonRequest $request, string $id)
    {
        return $this->rejectionReasonService->updateRejectionReason($id, $request->validated());
    }

    public function destroy(string $id)
    {
        return $this->rejectionReasonService->deleteRejectionReason($id);
    }

    public function toggleStatus(string $id)
    {
        return $this->rejectionReasonService->toggleRejectionReasonStatus($id);
    }
}
