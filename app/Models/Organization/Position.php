<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'function', 
        'unit_id'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    // Relacion con unidad 1-n
    public function unit(){
        return $this->belongsTo(Unit::class);
    }


    public function employees(){
        return $this->hasOne(Employee::class);
    }
}
