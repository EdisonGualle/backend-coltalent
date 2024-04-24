<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function canton()
    {
        return $this->hasMany(Canton::class);
    }
}
