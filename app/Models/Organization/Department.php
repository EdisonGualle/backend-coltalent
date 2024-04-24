<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'function'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function unit(){
        return $this->hasMany(Unit::class);
    }
}
