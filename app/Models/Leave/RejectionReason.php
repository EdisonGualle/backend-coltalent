<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectionReason extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reason'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

     // Relación muchos a muchos con LeaveType
     public function leaveTypes()
     {
         return $this->belongsToMany(LeaveType::class, 'leave_type_rejection_reason');
     }
     

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(LeaveComment::class);
    }
}
