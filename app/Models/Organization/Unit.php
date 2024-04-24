<?php

namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
      'name',
      'function',
      'phone',
      'head_employee_id',
      'department_id',
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function position(){
        return $this->hasMany(Position::class);
    }

    public function headEmployee()
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }
}
