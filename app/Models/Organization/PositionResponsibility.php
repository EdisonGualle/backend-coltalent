<?php

namespace App\Models\Organization;

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
}
