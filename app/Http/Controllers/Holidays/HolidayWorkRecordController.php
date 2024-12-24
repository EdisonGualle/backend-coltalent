<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Http\Requests\Holidays\CreateHolidayWorkRecordRequest;
use App\Http\Requests\Holidays\DeleteHolidayWorkRecordsRequest;
use App\Services\Holidays\HolidayWorkRecordService;
use Illuminate\Http\JsonResponse;

class HolidayWorkRecordController extends Controller
{
    protected $workRecordService;

    public function __construct(HolidayWorkRecordService $workRecordService)
    {
        $this->workRecordService = $workRecordService;
    }

    /**
     * Crear registros en rango.
     */
    public function store(CreateHolidayWorkRecordRequest $request): JsonResponse
    {
        return $this->workRecordService->createWorkRecords($request->validated());
    }

    /**
     * Obtener todos los registros activos.
     */
    public function index(): JsonResponse
    {
        return $this->workRecordService->getAllActiveWorkRecords();
    }

    /**
     * Obtener registros activos para un empleado específico.
     */
    public function showByEmployee(int $employeeId): JsonResponse
    {
        return $this->workRecordService->getActiveWorkRecordsByEmployee($employeeId);
    }

    /**
     * Obtener un registro específico.
     */
    public function show(int $recordId): JsonResponse
    {
        return $this->workRecordService->getWorkRecordById($recordId);
    }

    /**
     * Eliminar registros en rango.
     */
    public function destroy(DeleteHolidayWorkRecordsRequest $request): JsonResponse
    {
        return $this->workRecordService->deleteWorkRecords($request->validated()['record_ids']);
    }
}
