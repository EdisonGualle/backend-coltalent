<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 
        'leave_type_id', 
        'start_date', 
        'end_date', 
        'start_time',
        'end_time', 
        'reason', 
        'attachment',
        'state_id'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function state()
    {
        return $this->belongsTo(LeaveState::class); 
    }

    public function comments()
    {
        return $this->hasMany(LeaveComment::class);
    }

    // Mutators to format created_at and updated_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

}
