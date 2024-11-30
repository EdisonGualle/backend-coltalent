<?php

namespace App\Models\Leave;

use App\Models\Organization\PositionResponsibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelegationResponsibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegation_id',
        'responsibility_id',
    ];

    public function delegation()
    {
        return $this->belongsTo(Delegation::class);
    }

    public function responsibility()
    {
        return $this->belongsTo(PositionResponsibility::class, 'responsibility_id');
    }
}
