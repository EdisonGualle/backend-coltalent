<?php

namespace App\Models\Employee\Backgrounds;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'to',
        'position',
        'institution',
        'responsabilities',
        'activities',
        'functions',
        'departure_reason',
        'glade',
        'employee_id',
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    //Relacion con empleado n-1
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
