<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 
        'description', 
        'max_duration', 
        'requires_document', 
        'advance_notice_days', 
        'deducts_from_vacation',
        'time_unit',
        'icon',
        'flow_type'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Relación muchos a muchos con RejectionReason
    public function rejectionReasons()
    {
        return $this->belongsToMany(RejectionReason::class, 'leave_type_rejection_reason');
    }
    

    // Relación con Leave
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
