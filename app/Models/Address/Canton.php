<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Canton extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'province_id'
    ];

    public $timestamps = false;

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function parish()
    {
        return $this->hasMany(parish::class);
    }
}
