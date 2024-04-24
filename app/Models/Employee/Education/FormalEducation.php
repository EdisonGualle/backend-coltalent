<?php

namespace App\Models\Employee\Education;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormalEducation extends Model
{
    use HasFactory;
    protected $table = 'formal_educations';
    protected $fillable = [
        'level',
        'institution',
        'place',
        'title',
        'specialization',
        'level_number',
        'status',
        'date',
        'registration',
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
