<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreateDirectionRequest;
use App\Http\Requests\Organization\UpdateDirectionRequest;
use App\Services\Organization\DirectionService;

class DirectionController extends Controller
{
    private $directionService;

    public function __construct(DirectionService $directionService)
    {
        $this->directionService = $directionService;
    }

    public function index()
    {
        return $this->directionService->getAllDirections();
    }

    public function store(CreateDirectionRequest $request)
    {
        return $this->directionService->createDirection($request->validated());
    }

    public function show(string $id)
    {
        return $this->directionService->getDirectionById($id);
    }

    public function update(UpdateDirectionRequest $request, string $id)
    {
        return $this->directionService->updateDirection($id, $request->validated());
    }

    public function destroy(string $id)
    {
        return $this->directionService->deleteDirection($id);
    }
}
