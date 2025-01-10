<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Services\Leave\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    public function store(StoreLeaveRequest $request, int $employee): JsonResponse
    {
        $data = $request->all();

        // Manejar la subida de archivo
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            // Obtener el nombre original
            $originalName = $file->getClientOriginalName();
            $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $safeName = Str::slug($filenameWithoutExtension); // Limpiar caracteres no deseados
            $finalName = $safeName . '.' . $extension; // Nombre inicial

            // Verificar si ya existe un archivo con el mismo nombre
            $storagePath = 'leave_documents/' . $finalName;
            $counter = 1;

            while (Storage::disk('public')->exists($storagePath)) {
                // Si existe, agregar un sufijo numérico
                $finalName = $safeName . '_' . $counter . '.' . $extension;
                $storagePath = 'leave_documents/' . $finalName;
                $counter++;
            }

            // Guardar el archivo con el nombre final
            $path = $file->storeAs('leave_documents', $finalName, 'public');
            $data['attachment'] = $path;
        }


        return $this->leaveService->createLeave($employee, $data);
    }

    public function getFilteredLeaves(Request $request, int $employee): JsonResponse
    {
        $filter = $request->query('filter', 'todos');

        // Llamar al servicio y obtener los datos
        $response = $this->leaveService->getLeavesByFilter($employee, $filter);

        return $response;
    }

    public function getEmployeeLeaves(int $employee_id, Request $request): JsonResponse
    {
        $filter = $request->query('filter', 'todos');
        return $this->leaveService->getLeavesByEmployee($employee_id, $filter);
    }


    // Nueva función para actualizar una solicitud de permiso
    public function update(Request $request, int $employee_id, int $leave_id): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'duration_hours' => 'nullable|integer',
            'reason' => 'string|max:255',
        ]);

        return $this->leaveService->updateLeave($employee_id, $leave_id, $data);
    }

    // Nueva función para obtener las estadísticas de permisos
    public function getLeaveStatistics(int $employee_id): JsonResponse
    {
        return $this->leaveService->getLeaveStatistics($employee_id);
    }


}
