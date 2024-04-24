<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Services\Organization\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    
    
    private $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    public function index()
    {
        return $this->unitService->getAllUnits();
    }

    
    public function store(Request $request)
    {
        return $this->unitService->createUnit($request->all());
    }

    public function show(string $id)
    {
        return $this->unitService->getUnitById($id);
    }


    public function update(Request $request, string $id)
    {
        return $this->unitService->updateUnit($id, $request->all());
    }


    public function destroy(string $id)
    {
        return $this->unitService->deleteUnit($id);
    }
}
