<?php

namespace App\Models\Organization;

use App\Models\Leave\Delegation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionResponsibility extends Model
{
    use HasFactory;

    
    protected $table = 'position_responsibilities';

    protected $fillable = [
        'position_id',
        'name',
        'description',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

     // Relación muchos a muchos con Delegation
     public function delegations()
    {
        return $this->belongsToMany(
            Delegation::class,
            'delegation_responsibilities', 
            'responsibility_id', 
            'delegation_id'
        );
    }
}
