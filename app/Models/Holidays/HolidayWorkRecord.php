<?php

namespace App\Models\Holidays;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HolidayWorkRecord extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'employee_id',
        'holiday_id',
        'type',
        'worked_value',
        'generates_compensatory',
        'reason'
    ];

    public function holiday()
    {
        return $this->belongsTo(Holiday::class, 'holiday_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
