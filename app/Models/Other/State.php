<?php

namespace App\Models\Other;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    protected $fillable = [
        'entity_type',
        'state',
    ];

    // Filtra los estados por tipo de entidad.
    public function scopeEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    // Relacion con user 1 - 1

    public function User()
    {
        return $this->hasOne(User::class);
    }
}
