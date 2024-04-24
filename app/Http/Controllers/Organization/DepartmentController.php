<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;

use App\Services\Organization\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{

    private $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function index()
    {
        return $this->departmentService->getAllDepartments();
    }

    public function store(Request $request)
    {
        return $this->departmentService->createDepartment($request->all());
    }

    public function show(string $id)
    {
        return $this->departmentService->getDepartmentById($id);
    }

    public function update(Request $request, string $id)
    {
        return $this->departmentService->updateDepartment($id, $request->all());
    }

    public function destroy(string $id)
    {
        return $this->departmentService->deleteDepartment($id);
    }
}
