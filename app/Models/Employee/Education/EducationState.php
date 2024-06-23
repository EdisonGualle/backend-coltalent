<?php

namespace App\Models\Employee\Education;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationState extends Model
{
    use HasFactory;

    protected $table = 'education_states';
    protected $fillable = ['name'];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    // Relación de 1-n con FormalEducation
    public function formalEducations(){
        return $this->hasMany(FormalEducation::class);
    }
}
