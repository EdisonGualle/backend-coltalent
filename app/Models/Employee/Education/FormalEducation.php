<?php

namespace App\Models\Employee\Education;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormalEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_formal_educations';
    protected $fillable = [
        'level_id',
        'institution',
        'title',
        'specialization',
        'state_id',
        'date',
        'registration',
        'employee_id'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    public $timestamps = false;

    // Relación de n-1 con Employee
    public function employee(){
        return $this->belongsTo(Employee::class);
    }

    // Relación de n-1 con EducationLevel
    public function educationLevel(){
        return $this->belongsTo(EducationLevel::class, 'level_id');
    }

    // Relación de n-1 con EducationState
    public function educationState(){
        return $this->belongsTo(EducationState::class, 'state_id');
    }
}
