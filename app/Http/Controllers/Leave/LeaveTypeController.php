<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Services\Leave\LeaveTypeService;

class LeaveTypeController extends Controller
{
    private $leaveTypeService;
    
    public function __construct(LeaveTypeService $leaveTypeService)
    {
        $this->leaveTypeService = $leaveTypeService;
    }

    public function index()
    {
        return $this->leaveTypeService->getAllLeaveTypes();
    }

    public function store(StoreLeaveTypeRequest $request)
    {
        return $this->leaveTypeService->createLeaveType($request->validated());
    }

    public function show(string $id)
    {
        return $this->leaveTypeService->getLeaveTypeById($id);
    }

    public function update(UpdateLeaveTypeRequest $request, string $id)
    {
        return $this->leaveTypeService->updateLeaveType($id, $request->validated());
    }

    public function destroy(string $id)
    {
        return $this->leaveTypeService->deleteLeaveType($id);
    }
}
