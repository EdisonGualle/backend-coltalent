<?php

namespace App\Reports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\Employee\Employee;
use Carbon\Carbon;

class EmployeesExport
{
    protected $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function export()
    {
        $filePath = storage_path('app/public/employees_report.xlsx');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headings = $this->getHeadings();
        $sheet->fromArray($headings, null, 'A1');

        // Aplicar estilo a los encabezados
        $headerStyleArray = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E90FF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyleArray);

        $employees = $this->getEmployees();
        $rowNumber = 2;

        foreach ($employees as $employee) {
            $data = $this->mapEmployeeData($employee);
            $sheet->fromArray($data, null, 'A' . $rowNumber++);
        }

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    private function getHeadings()
    {
        $headings = [
            'Cédula',
            'Nombre Completo'
        ];

        if (isset($this->params['with_personal_info']) && $this->params['with_personal_info']) {
            $headings = array_merge($headings, [
                'Fecha de Nacimiento',
                'Edad',
                'Género',
                'Estado Civil',
                'Etnia',
                'Nacionalidad',
            ]);
        }

        if (isset($this->params['with_contact_info']) && $this->params['with_contact_info']) {
            $headings = array_merge($headings, [
                'Correo Personal',
                'Celular Personal',
                'Teléfono de Casa',
                'Teléfono de Trabajo',
            ]);
        } else {
            $headings = array_merge($headings, [
                'Correo Personal',
                'Celular Personal',
            ]);
        }

        if (isset($this->params['with_residence_info']) && $this->params['with_residence_info']) {
            $headings = array_merge($headings, [
                'Provincia',
                'Cantón',
                'Parroquia',
                'Sector',
                'Calle Principal',
                'Calle Secundaria',
                'Número de casa',
                'Referencia',
            ]);
        }

        if (isset($this->params['with_position_info']) && $this->params['with_position_info']) {
            $headings = array_merge($headings, [
                'Dirección',
                'Unidad',
                'Cargo',
            ]);
        }

        return $headings;
    }

    private function mapEmployeeData($employee)
    {
        $data = [
            $employee->identification,
            $employee->getFullNameAttribute()
        ];

        if (isset($this->params['with_personal_info']) && $this->params['with_personal_info']) {
            $age = Carbon::parse($employee->date_of_birth)->age;
            $data = array_merge($data, [
                $employee->date_of_birth,
                $age,
                $employee->gender,
                $employee->marital_status,
                $employee->ethnicity,
                $employee->nationality,
            ]);
        }

        if (isset($this->params['with_contact_info']) && $this->params['with_contact_info']) {
            $data = array_merge($data, [
                optional($employee->contact)->personal_email ?? '',
                optional($employee->contact)->personal_phone ?? '',
                optional($employee->contact)->home_phone ?? '',
                optional($employee->contact)->work_phone ?? '',
            ]);
        } else {
            $data = array_merge($data, [
                optional($employee->contact)->personal_email ?? '',
                optional($employee->contact)->personal_phone ?? '',
            ]);
        }

        if (isset($this->params['with_residence_info']) && $this->params['with_residence_info']) {
            $data = array_merge($data, [
                optional(optional(optional($employee->address)->parish)->canton)->province->name ?? '',
                optional(optional($employee->address)->parish)->canton->name ?? '',
                optional($employee->address->parish)->nombre ?? '',
                optional($employee->address)->sector ?? '',
                optional($employee->address)->main_street ?? '',
                optional($employee->address)->secondary_street ?? '',
                optional($employee->address)->number ?? '',
                optional($employee->address)->reference ?? '',
            ]);
        }

        if (isset($this->params['with_position_info']) && $this->params['with_position_info']) {
            $unitName = optional(optional($employee->position)->unit)->name ?? '';
            $directionName = optional(optional($employee->position)->unit)->direction->name ?? optional($employee->position->direction)->name ?? '';

            $data = array_merge($data, [
                $directionName,
                $unitName,
                optional($employee->position)->name ?? '',
            ]);
        }

        return $data;
    }

    private function getEmployees()
    {
        $query = Employee::query();

        if (isset($this->params['status'])) {
            if ($this->params['status'] === 'activos') {
                $query->whereHas('user.userState', function ($q) {
                    $q->where('name', 'Activo');
                });
            } elseif ($this->params['status'] === 'inactivos') {
                $query->whereHas('user.userState', function ($q) {
                    $q->where('name', 'Inactivo');
                });
            }
        }

        $query->with([
            'contact',
            'address.parish.canton.province',
            'position' => function ($query) {
                $query->withTrashed();
            },
            'position.unit' => function ($query) {
                $query->withTrashed();
            },
            'position.unit.direction' => function ($query) {
                $query->withTrashed();
            },
            'position.direction' => function ($query) {
                $query->withTrashed();
            },
            'user.userState'
        ]);

        return $query->get();
    }
}
