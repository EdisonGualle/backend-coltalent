<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'delegate_id',
        'reason',
        'status',
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function delegate()
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }

    public function responsibilities()
    {
        return $this->hasMany(DelegationResponsibility::class, 'delegation_id');
    }
}
