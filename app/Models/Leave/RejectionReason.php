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

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(LeaveComment::class);
    }
}
