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
        'training_type_id',
        'employee_id'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at',
        'training_type_id',
    ];

    // Deshabilitar timestamps automáticos
    public $timestamps = false;

    
    //Relacion de n-1
    public function employee(){
        return $this->belongsTo(Employee::class);
    }

    //Relacion de n-1
    public function trainingType(){
        return $this->belongsTo(TrainingType::class, 'training_type_id');
    }
}
