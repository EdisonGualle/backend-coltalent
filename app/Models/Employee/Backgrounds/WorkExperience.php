<?php

namespace App\Models\Employee\Backgrounds;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    use HasFactory;

    protected $table = 'employee_work_experiences';

    protected $fillable = [
        'from',
        'to',
        'position',
        'institution',
        'responsibilities',
        'activities',
        'functions',
        'departure_reason',
        'note',
        'employee_id',
    ];

    // Deshabilitar timestamps automáticos
    public $timestamps = false;

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    //Relacion con empleado n-1
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
