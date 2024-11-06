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
        'time_unit',
        'icon',
        'color'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    // Relación con Leave
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
