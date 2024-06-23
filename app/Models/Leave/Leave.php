<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 
        'leave_type_id', 
        'start_date', 
        'end_date', 
        'duration_hours', 
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
}
