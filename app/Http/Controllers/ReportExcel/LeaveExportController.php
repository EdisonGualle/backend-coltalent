<?php

namespace App\Http\Controllers\ReportExcel;

use App\Http\Controllers\Controller;
use App\Models\Leave\Leave;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LeaveExportController extends Controller
{
    public function export(Request $request, int $employee_id)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        // Obtener todas las solicitudes de permiso para el mes y el año especificados
        $leaves = Leave::where('employee_id', $employee_id)
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->with(['leaveType', 'state'])
            ->get();

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Calendario Permisos');

        // Establecer los encabezados del calendario
        $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($daysOfWeek as $index => $day) {
            $sheet->setCellValue(chr(65 + $index) . '1', $day);
        }

        // Definir colores para cada tipo de permiso
        $colors = [
            'Compensación' => 'FFFF00',
            'Cargo vacaciones' => 'FF0000',
            'Calamidad Domestica' => '00FF00',
            'Atención medica' => '0000FF',
            'Institucional' => 'FF00FF',
        ];

        // Calcular el primer día del mes y el último día del mes
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $lastDayOfMonth = (clone $firstDayOfMonth)->modify('last day of this month');

        // Obtener el índice de la fila inicial para el calendario
        $startRow = 2;
        $currentRow = $startRow;
        $currentColumn = $firstDayOfMonth->format('N');

        // Rellenar las celdas del calendario con los números de los días del mes
        for ($day = 1; $day <= $lastDayOfMonth->format('j'); $day++) {
            $sheet->setCellValue($this->getColumnLetter($currentColumn) . $currentRow, $day);

            // Avanzar al siguiente día
            $currentColumn++;
            if ($currentColumn > 7) {
                $currentColumn = 1;
                $currentRow++;
            }
        }

        // Pintar los días del calendario según los permisos
        foreach ($leaves as $leave) {
            $leaveType = $leave->leaveType->name;
            $color = $colors[$leaveType] ?? 'FFFFFF';

            // Calcular el rango de fechas para el permiso
            $startDate = new \DateTime($leave->start_date);
            $endDate = new \DateTime($leave->end_date ?? $leave->start_date);

            while ($startDate <= $endDate) {
                // Obtener la columna y la fila del día en el calendario
                $day = $startDate->format('j');
                $column = $startDate->format('N');
                $row = $startRow + floor(($day + $firstDayOfMonth->format('N') - 2) / 7);

                // Pintar la celda del día
                $sheet->getStyle($this->getColumnLetter($column) . $row)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);

                // Avanzar al siguiente día
                $startDate->modify('+1 day');
            }
        }

        // Calcular el tiempo total por cada tipo de permiso
        $leaveTypeDurations = [];
        foreach ($leaves as $leave) {
            $leaveType = $leave->leaveType->name;
            if (!isset($leaveTypeDurations[$leaveType])) {
                $leaveTypeDurations[$leaveType] = ['days' => 0, 'hours' => 0, 'minutes' => 0];
            }

            if ($leave->start_date && $leave->end_date) {
                $start_date = \DateTime::createFromFormat('Y-m-d', $leave->start_date);
                $end_date = \DateTime::createFromFormat('Y-m-d', $leave->end_date);
                if ($start_date && $end_date) {
                    $interval = $start_date->diff($end_date);
                    $leaveTypeDurations[$leaveType]['days'] += $interval->days + 1; // Incluye el último día
                }
            } elseif ($leave->start_time && $leave->end_time) {
                $start_time = \DateTime::createFromFormat('H:i:s', $leave->start_time);
                $end_time = \DateTime::createFromFormat('H:i:s', $leave->end_time);
                if ($start_time && $end_time) {
                    $interval = $start_time->diff($end_time);
                    $leaveTypeDurations[$leaveType]['hours'] += $interval->h;
                    $leaveTypeDurations[$leaveType]['minutes'] += $interval->i;
                }
            }
        }

        // Convertir minutos en horas si es necesario
        foreach ($leaveTypeDurations as $type => $duration) {
            if ($duration['minutes'] >= 60) {
                $leaveTypeDurations[$type]['hours'] += intdiv($duration['minutes'], 60);
                $leaveTypeDurations[$type]['minutes'] %= 60;
            }
        }

        // Agregar el tiempo total por cada tipo de permiso al final del archivo
        $row = $currentRow + 2;
        $sheet->setCellValue('A' . $row, 'Total por Tipo de Permiso');
        foreach ($leaveTypeDurations as $type => $duration) {
            $row++;
            $sheet->setCellValue('A' . $row, $type);
            $sheet->setCellValue('B' . $row, $duration['days'] . ' Días');
            $sheet->setCellValue('C' . $row, str_pad($duration['hours'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($duration['minutes'], 2, '0', STR_PAD_LEFT) . ' Horas');
        }

        // Guardar el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = 'permisos_' . $employee_id . '_' . $year . '_' . $month . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        // Descargar el archivo
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    private function getColumnLetter($columnNumber)
    {
        $alphabet = range('A', 'Z');
        return $alphabet[$columnNumber - 1];
    }
}
