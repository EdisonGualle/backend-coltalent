<?php

namespace App\Models\Holidays;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompensatoryDay extends Model
{
        use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'earned_date',
        'type',
        'compensatory_value',
        'reason',
        'is_used',
        'used_date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
