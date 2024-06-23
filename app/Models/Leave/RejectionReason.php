<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectionReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    public function comments()
    {
        return $this->hasMany(LeaveComment::class);
    }
}
