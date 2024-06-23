<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Services\Leave\LeaveStateService;

class LeaveStateController extends Controller
{
    private $leaveStateService;

    public function __construct(LeaveStateService $leaveStateService)
    {
        $this->leaveStateService = $leaveStateService;
    }

    public function index()
    {
        return $this->leaveStateService->getAllLeaveStates();
    }

    public function show(string $id)
    {
        return $this->leaveStateService->getLeaveStateById($id);
    }
}
