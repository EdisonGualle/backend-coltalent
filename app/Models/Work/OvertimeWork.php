<?php

namespace App\Models\Work;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeWork extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'worked_value',
        'reason',
        'generates_compensatory',
    ];

    /**
     * Relación con el empleado.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
