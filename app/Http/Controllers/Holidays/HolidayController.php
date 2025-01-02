<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Http\Requests\Holidays\CreateHolidayRequest;
use App\Http\Requests\Holidays\UpdateHolidayRequest;
use App\Services\Holidays\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    protected $holidayService;

    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    /**
     * Listar todos los días festivos.
     */
    public function index(): JsonResponse
    {
        return $this->holidayService->getAllHolidays(false);
    }

    /**
     * Crear un nuevo día festivo.
     */
    public function store(CreateHolidayRequest $request): JsonResponse
    {
        return $this->holidayService->createHoliday($request->validated());
    }

    /**
     * Mostrar un día festivo específico.
     */
    public function show(int $id): JsonResponse
    {
        return $this->holidayService->getHolidayById($id);
    }

    /**
     * Actualizar un día festivo existente.
     */
    public function update(UpdateHolidayRequest $request, int $id): JsonResponse
    {
        return $this->holidayService->updateHoliday($id, $request->validated());
    }

    /**
     * Eliminar un día festivo.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->holidayService->deleteHoliday($id);
    }

    /**
     * Restaurar un día festivo eliminado.
     */
    public function restore(int $id): JsonResponse
    {
        return $this->holidayService->restoreHoliday($id);
    }

    public function assignable(): JsonResponse
    {
        return $this->holidayService->getAssignableHolidays();
    }

}
