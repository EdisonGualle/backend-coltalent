<?php

namespace App\Models\Employee\Education;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    use HasFactory;

    
    protected $table = 'training_types';

    protected $fillable = [
        'name',
        'description'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    // Deshabilitar timestamps automáticos
    public $timestamps = false;

    //Relacion de 1-n con Training explicame eso de 1-n con Training

    public function trainings(){
        return $this->hasMany(Training::class);
    }
}
