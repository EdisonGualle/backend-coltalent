<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaveComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id', 
        'commented_by', 
        'comment', 
        'rejection_reason_id', 
        'action'
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function commentedBy()
    {
        return $this->belongsTo(Employee::class, 'commented_by');
    }

    public function rejectionReason()
    {
        return $this->belongsTo(RejectionReason::class);
    }

    // Mutators to format created_at and updated_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }
}
