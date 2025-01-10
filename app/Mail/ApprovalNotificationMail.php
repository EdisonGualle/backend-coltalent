<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApprovalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $approver;
    public $leave;
    public $applicant;

    public function __construct($approver, $leave, $applicant)
    {
        $this->approver = $approver;
        $this->leave = $leave;
        $this->applicant = $applicant;
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
            try {
                // Verificar si los valores contienen segundos
                $start_time_raw = $this->leave->start_time;
                $end_time_raw = $this->leave->end_time;
            
            
                // Validar formato basado en la cantidad de ":"
                $start_time_format = (substr_count($start_time_raw, ':') === 2) ? 'H:i:s' : 'H:i';
                $end_time_format = (substr_count($end_time_raw, ':') === 2) ? 'H:i:s' : 'H:i';
            
            
                // Crear objetos Carbon
                $start_time = Carbon::createFromFormat($start_time_format, $start_time_raw);
                $end_time = Carbon::createFromFormat($end_time_format, $end_time_raw);

            } catch (\Exception $e) {
               
                $start_time = null;
                $end_time = null;
            }
            
            

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

        return $this->view('emails.approval_notification')
            ->subject('Nueva Solicitud de Permiso Pendiente')
            ->with([
                'approverName' => $this->approver->getFullNameAttribute(),
                'applicantName' => $this->applicant->getFullNameAttribute(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $duration,
                'leaveType' => $this->leave->leaveType->name,
                'leaveReason' => $this->leave->reason,
            ]);
    }
}
