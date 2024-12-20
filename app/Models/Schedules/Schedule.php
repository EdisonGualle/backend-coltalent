<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'rest_days',
    ];

    protected $casts = [
        'rest_days' => 'array',
    ];

    public function employeeSchedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'schedule_id');
    }
}
