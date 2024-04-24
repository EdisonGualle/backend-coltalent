<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Services\Organization\PositionService;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        return $this->positionService->createPosition($request->all());
    }

    public function show(string $id)
    {
        return $this->positionService->getPositionById($id);
    }

    public function update(Request $request, string $id)
    {
        return $this->positionService->updatePosition($id, $request->all());
    }

    public function destroy(string $id)
    {
        return $this->positionService->deletePosition($id);
    }
}
