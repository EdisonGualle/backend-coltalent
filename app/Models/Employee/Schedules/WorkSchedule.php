<?php

namespace App\Models\Employee\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee\Employee;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_work_schedules';

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'start_time',
        'end_time',
        'has_lunch_break',
        'lunch_start_time',
        'lunch_end_time',
    ];

    /**
     * Define the relationship with the Employee model
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get a readable representation of the day of the week
     */
    public function getDayOfWeekNameAttribute()
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        return $days[$this->day_of_week] ?? 'Desconocido';
    }

    /**
     * Get the full schedule for a given day
     */
    public function getFullScheduleAttribute()
    {
        $schedule = "{$this->start_time} - {$this->end_time}";

        if ($this->has_lunch_break) {
            $schedule .= " (Almuerzo: {$this->lunch_start_time} - {$this->lunch_end_time})";
        }

        return $schedule;
    }
}
