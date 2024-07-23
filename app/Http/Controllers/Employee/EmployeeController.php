<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\EmployeeService;

use Exception;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
    private $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }


    public function index()
    {
        try {
            $employees = $this->employeeService->getAllEmployees();
            return $this->respondWithSuccess('Empleados obtenidos exitosamente.', $employees);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener los empleados: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $employee = $this->employeeService->getEmployeeById($id);
            return $this->respondWithSuccess('Empleado obtenido exitosamente.', $employee);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener el empleado: ' . $e->getMessage(), 500);
        }
    }
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $employee = $this->employeeService->createEmployee($request);
            return $this->respondWithSuccess('Empleado creado exitosamente.', $employee->toArray(), 201);
        } catch (Exception $e) {
            return $this->respondWithError('Error al crear el empleado: ' . $e->getMessage(), 500);
        }
    }
    
    

    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            $employee = $this->employeeService->updateEmployee($id, $request->validated());
            return $this->respondWithSuccess('Empleado actualizado exitosamente.', $employee->toArray());
        } catch (Exception $e) {
            return $this->respondWithError('Error al actualizar el empleado: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $this->employeeService->deleteEmployee($id);
            return $this->respondWithSuccess('Empleado eliminado exitosamente.', []);
        } catch (Exception $e) {
            return $this->respondWithError( $e->getMessage(), 500);
        }
    }

    protected function respondWithSuccess(string $message, array $data, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'msg' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function respondWithError(string $message, int $statusCode): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => false,
            'msg' => $message,
        ], $statusCode);
    }


}
