<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreateUnitRequest;
use App\Http\Requests\Organization\UpdateUnitRequest;
use App\Services\Organization\UnitService;

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

    public function store(CreateUnitRequest $request)
    {
        return $this->unitService->createUnit($request->validated());
    }

    public function show(string $id)
    {
        return $this->unitService->getUnitById($id);
    }

    public function update(UpdateUnitRequest $request, string $id)
    {
        return $this->unitService->updateUnit($id, $request->validated());
    }

    public function destroy(string $id)
    {
        return $this->unitService->deleteUnit($id);
    }
}
