<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee\Employee;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class LeaveReportController extends Controller
{
    public function generateReport(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('m'));
        $year = $request->input('year', Carbon::now()->format('Y'));
        $employeeId = $request->input('employee_id');

        // Obtener permisos aprobados en el mes especificado
        $query = Leave::with(['employee', 'leaveType', 'state'])
            ->whereHas('state', function($q) {
                $q->where('name', 'Aprobado');
            })
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $leaves = $query->get();

        // Crear un nuevo documento de Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezado del reporte
        $header = ['Nombres'];
        for ($i = 1; $i <= 31; $i++) {
            $header[] = $i;
        }

        $sheet->fromArray($header, null, 'A1');

        // Agrupar los permisos por empleado
        $employees = $leaves->groupBy('employee_id');

        $rowNumber = 2;

        foreach ($employees as $employeeId => $leaves) {
            $employee = $leaves->first()->employee;
            $row = array_fill(0, 32, '');

            $row[0] = $employee->getFullNameAttribute();

            foreach ($leaves as $leave) {
                $startDay = Carbon::parse($leave->start_date)->day;
                $endDay = Carbon::parse($leave->end_date)->day;

                for ($i = $startDay; $i <= $endDay; $i++) {
                    $row[$i] = $leave->leaveType->name; // Aquí se podría agregar color
                }
            }

            $sheet->fromArray($row, null, 'A' . $rowNumber++);
        }

        // Ajustar el tamaño de las columnas
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Guardar el archivo de Excel
        $fileName = 'leave_report_' . $month . '_' . $year . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
