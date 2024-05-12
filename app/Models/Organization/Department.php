<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'function',
        'head_employee_id',
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function unit(){
        return $this->hasMany(Unit::class);
    }

    public function headEmployeeDepartament()
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }
}
