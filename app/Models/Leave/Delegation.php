<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use App\Models\Organization\PositionResponsibility;
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

    // Relación muchos a muchos con PositionResponsibility
    public function responsibilities()
    {
        return $this->belongsToMany(
            PositionResponsibility::class,
            'delegation_responsibilities', // Tabla intermedia
            'delegation_id', // Clave foránea en la tabla intermedia hacia Delegation
            'responsibility_id' // Clave foránea en la tabla intermedia hacia PositionResponsibility
        );
    }
}
