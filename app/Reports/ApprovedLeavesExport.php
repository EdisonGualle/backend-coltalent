<?php

namespace App\Reports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\Employee\Employee;
use App\Models\Leave\Leave;
use Carbon\Carbon;

class ApprovedLeavesExport
{
    protected $month;
    protected $year;
    protected $employeeId;

    public function __construct($month, $year, $employeeId = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->employeeId = $employeeId;
    }

    public function generateReport()
    {
        $filePath = storage_path('app/public/approved_leaves_report.xlsx');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->addLeaveTypesHeader($sheet);
        $this->addHeaders($sheet);

        $employees = $this->getEmployees();
        $this->addEmployeeRows($sheet, $employees);

        $this->setColumnWidth($sheet);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    private function addLeaveTypesHeader($sheet)
    {
        $leaveTypes = Leave::select('leave_types.name', 'leave_types.color')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->distinct()
            ->get();

        $row = 1;
        foreach ($leaveTypes as $leaveType) {
            $sheet->setCellValue("A{$row}", $leaveType->name);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ltrim($leaveType->color, '#'));
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $row++;
        }
    }

    private function addHeaders($sheet)
    {
        $monthName = strtoupper(Carbon::create($this->year, $this->month)->locale('es')->monthName);
        $daysInMonth = Carbon::create($this->year, $this->month)->daysInMonth;

        $sheet->setCellValue("A4", "NOMBRES Y APELLIDOS");
        $sheet->getStyle("A4")->getFont()->setBold(true);
        $sheet->setCellValue("B4", $monthName);
        $sheet->mergeCells("B4:" . $this->getColumn($daysInMonth + 1) . "4");
        $sheet->getStyle("B4")->getFont()->setBold(true);
        $sheet->getStyle("B4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $column = $this->getColumn($day + 1);
            $sheet->setCellValue("{$column}5", Carbon::create($this->year, $this->month, $day)->shortDayName);
            $sheet->setCellValue("{$column}6", $day);
        }
    }

    private function getEmployees()
    {
        $query = Employee::query();

        if ($this->employeeId) {
            $query->where('id', $this->employeeId);
        }

        return $query->whereHas('user', function ($q) {
            $q->whereHas('userState', function ($q2) {
                $q2->where('name', 'Activo');
            });
        })->get();
    }

    private function addEmployeeRows($sheet, $employees)
    {
        $row = 7;
        foreach ($employees as $employee) {
            $sheet->setCellValue("A{$row}", $employee->getFullNameAttribute());

            $leaves = Leave::where('employee_id', $employee->id)
                ->whereMonth('start_date', $this->month)
                ->whereYear('start_date', $this->year)
                ->whereHas('state', function ($q) {
                    $q->where('name', 'Aprobado');
                })
                ->get();

            foreach ($leaves as $leave) {
                $startDate = Carbon::parse($leave->start_date);
                $endDate = Carbon::parse($leave->end_date);

                if ($leave->start_time && $leave->end_time) { // Permiso por horas
                    $day = $startDate->day;
                    $column = $this->getColumn($day + 1);
                    $sheet->getStyle("{$column}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ltrim($leave->leaveType->color, '#'));
                } else { // Permiso por días
                    for ($day = $startDate->day; $day <= $endDate->day; $day++) {
                        $column = $this->getColumn($day + 1);
                        $sheet->getStyle("{$column}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ltrim($leave->leaveType->color, '#'));
                    }
                }
            }

            $row++;
        }
    }

    private function setColumnWidth($sheet)
    {
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }

    private function getColumn($number)
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)) . $column;
            $number = (int)($number / 26);
        }
        return $column;
    }
}
