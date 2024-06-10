<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreatePositionRequest;
use App\Http\Requests\Organization\UpdatePositionRequest;
use App\Services\Organization\PositionService;

class PositionController extends Controller
{
    private $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    public function index()
    {
        return $this->positionService->getAllPositions();
    }

    public function store(CreatePositionRequest $request)
    {
        return $this->positionService->createPosition($request->validated());
    }

    public function show(string $id)
    {
        return $this->positionService->getPositionById($id);
    }

    public function update(UpdatePositionRequest $request, string $id)
    {
        return $this->positionService->updatePosition($id, $request->validated());
    }

    public function destroy(string $id)
    {
        return $this->positionService->deletePosition($id);
    }
}
