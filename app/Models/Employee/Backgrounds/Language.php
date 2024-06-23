<?php

namespace App\Models\Employee\Backgrounds;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $table = 'employee_languages';

    protected $fillable = [
        'language',
        'spoken_level',
        'written_level',
        'proficiency_certificate',
        'issuing_institution',
        'employee_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Relacion con empleado 1-n un ejemplo tiene muchos idiomas
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

}
