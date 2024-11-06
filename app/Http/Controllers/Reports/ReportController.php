<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Reports\ApprovedLeavesExport;
use App\Reports\EmployeesExport;

class ReportController extends Controller
{
    public function export(Request $request)
    {
        $params = $request->all();
        $export = new EmployeesExport($params);
        $filePath = $export->export();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    public function approvedLeavesReport(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $employeeId = $request->input('employee_id', null);

        $report = new ApprovedLeavesExport($month, $year, $employeeId);
        $filePath = $report->generateReport();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
