<?php

namespace App\Http\Requests\Leave;

use App\Models\Leave\Leave;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Leave\LeaveType;
use Carbon\Carbon;

class StoreLeaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();
            $leaveType = LeaveType::find($data['leave_type_id']);

         

            // Validar la duración máxima basada en el tipo de permiso
            if ($leaveType) {
                if ($leaveType->time_unit == 'Días') {
                    if (!empty($data['end_date'])) {
                        $startDateObj = new \DateTime($data['start_date']);
                        $endDateObj = new \DateTime($data['end_date']);
                        $interval = $startDateObj->diff($endDateObj)->days + 1;

                        if ($interval > (int) $leaveType->max_duration) {
                            $validator->errors()->add('end_date', "La duración máxima permitida para este tipo de permiso es de {$leaveType->max_duration} días.");
                        }
                    } 
                   
                } elseif ($leaveType->time_unit == 'Horas') {
                    if (!empty($data['start_time']) && !empty($data['end_time'])) {
                        $startTime = new \DateTime($data['start_time']);
                        $endTime = new \DateTime($data['end_time']);
                        $interval = $startTime->diff($endTime);
                        $requestedMinutes = ($interval->h * 60) + $interval->i;

                        list($maxHours, $maxMinutes) = explode(':', $leaveType->max_duration);
                        $maxDurationMinutes = ($maxHours * 60) + $maxMinutes;

                        if ($requestedMinutes > $maxDurationMinutes) {
                            $validator->errors()->add('end_time', "La duración máxima permitida para este tipo de permiso es de {$leaveType->max_duration} horas.");
                        }
                    } 
                }

                // Validar el aviso previo de días de anticipación
                $advanceNoticeDays = (int) $leaveType->advance_notice_days;
                $currentDate = Carbon::today();
                $startDate = Carbon::parse($data['start_date']);
                $noticePeriod = $currentDate->diffInDays($startDate);

                if ($noticePeriod < $advanceNoticeDays) {
                    $validator->errors()->add('start_date', "Debe solicitar el permiso con al menos {$advanceNoticeDays} días de anticipación.");
                }

                // Validar si se requiere un documento adjunto
                if ($leaveType->requires_document === 'Si' && empty($data['attachment'])) {
                    $validator->errors()->add('attachment', 'Este tipo de permiso requiere un documento adjunto.');
                }
            }

            // Validar que no haya un permiso en curso, futuro aprobado, o pendiente de aprobación
            $employeeId = $this->route('employee');
            $startDate = $data['start_date'];
            $endDate = $data['end_date'] ?? $startDate;

            // Verificar permisos en curso o futuros (aprobados)
            $overlappingApprovedLeaves = Leave::where('employee_id', $employeeId)
                ->where('state_id', function ($query) {
                    $query->select('id')->from('leave_states')->where('name', 'Aprobado');
                })
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($overlappingApprovedLeaves) {
                $validator->errors()->add('start_date', 'Ya tiene un permiso aprobado que cubre el período seleccionado.');
            }

            // Verificar permisos pendientes
            $overlappingPendingLeaves = Leave::where('employee_id', $employeeId)
                ->where('state_id', function ($query) {
                    $query->select('id')->from('leave_states')->where('name', 'Pendiente');
                })
                ->exists();

            if ($overlappingPendingLeaves) {
                $validator->errors()->add('start_date', 'Tiene un permiso pendiente de aprobación.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'El tipo de permiso es obligatorio.',
            'leave_type_id.exists' => 'El tipo de permiso seleccionado no es válido.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser una fecha pasada.',
            'end_date.date' => 'La fecha de finalización debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
            'start_time.date_format' => 'La hora de inicio debe estar en el formato HH:mm.',
            'end_time.date_format' => 'La hora de finalización debe estar en el formato HH:mm.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
            'reason.required' => 'La razón del permiso es obligatoria.',
            'reason.string' => 'La razón del permiso debe ser una cadena de texto.',
            'reason.max' => 'La razón del permiso no puede exceder los 255 caracteres.',
            'attachment.file' => 'El archivo adjunto debe ser un archivo válido.',
            'attachment.mimes' => 'El archivo adjunto debe ser de tipo: pdf, jpeg, png, jpg.',
            'attachment.max' => 'El archivo adjunto no debe superar los 5MB.',
        ];
    }
}
