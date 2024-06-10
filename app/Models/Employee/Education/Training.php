<?php

namespace App\Models\Employee\Education;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $table = 'employee_trainings';

    protected $fillable = [
        'institution',
        'topic',
        'year',
        'num_hours',
        'start_date',
        'end_date',
        'attendance',
        'approval',
        'employee_id'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    //Relacion de n-1
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
