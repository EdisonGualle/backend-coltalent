<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class LeaveRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $leave;
    public $approver;

    public function __construct($employee, $leave, $approver)
    {
        $this->employee = $employee;
        $this->leave = $leave;
        $this->approver = $approver;
    }

    public function build()
    {
        $startDate = $this->leave->start_date ? Carbon::parse($this->leave->start_date)->format('d/m/Y') : 'N/A';
        $endDate = $this->leave->end_date ? Carbon::parse($this->leave->end_date)->format('d/m/Y') : null;
        $startTime = $this->leave->start_time ? Carbon::parse($this->leave->start_time)->format('H:i') : null;
        $endTime = $this->leave->end_time ? Carbon::parse($this->leave->end_time)->format('H:i') : null;

        $duration = 'N/A';

        if ($this->leave->start_date && $this->leave->end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $this->leave->start_date);
            $end_date = Carbon::createFromFormat('Y-m-d', $this->leave->end_date);
            if ($start_date && $end_date) {
                $interval = $start_date->diff($end_date);
                $days = $interval->days + 1; // Incluye el último día
                $duration = $days . ' ' . ($days > 1 ? 'Días' : 'Día');
            }
        } elseif ($this->leave->start_time && $this->leave->end_time) {
            $start_time = Carbon::createFromFormat('H:i', $this->leave->start_time);
            $end_time = Carbon::createFromFormat('H:i', $this->leave->end_time);
            if ($start_time && $end_time) {
                $interval = $start_time->diff($end_time);
                $hours = $interval->h;
                $minutes = $interval->i;
                $duration = '';
                if ($hours > 0) {
                    $duration .= $hours . ' ' . ($hours > 1 ? 'horas' : 'hora');
                }
                if ($minutes > 0) {
                    if ($hours > 0) {
                        $duration .= ' y ';
                    }
                    $duration .= $minutes . ' ' . ($minutes > 1 ? 'minutos' : 'minuto');
                }
            }
        }

        return $this->view('emails.leave_requested')
            ->subject('Constancia de Solicitud de Permiso')
            ->with([
                'employeeName' => $this->employee->getFullNameAttribute(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $duration,
                'approverName' => $this->approver->getFullNameAttribute(),
                'leaveType' => $this->leave->leaveType->name,
                'leaveReason' => $this->leave->reason,
            ]);
    }
}
