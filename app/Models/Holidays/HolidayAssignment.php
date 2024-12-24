<?php

namespace App\Models\Holidays;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HolidayAssignment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'holiday_id',
        'employee_id'
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
