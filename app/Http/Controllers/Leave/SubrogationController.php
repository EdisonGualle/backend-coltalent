<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Services\Leave\SubrogationService;
use Illuminate\Http\Request;

class SubrogationController extends Controller
{
    protected $subrogationService;

    public function __construct(SubrogationService $subrogationService)
    {
        $this->subrogationService = $subrogationService;
    }


    public function getCandidates(int $leaveId)
    {
        return $this->subrogationService->getAvailableSubrogates($leaveId);
    }

    public function listByEmployee(int $employeeId)
    {
        return $this->subrogationService->getSubrogationsByEmployee($employeeId);
    }

    public function listAllSubrogations()
    {
        return $this->subrogationService->getAllSubrogations();
    }

    public function listAssignedByEmployee(int $employeeId)
    {
        return $this->subrogationService->getDelegationsAssignedByEmployee($employeeId);
    }

}
