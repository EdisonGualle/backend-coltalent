<?php

namespace App\Models\Employee;

use App\Models\Employee\Backgrounds\Publication;
use App\Models\Employee\Backgrounds\WorkExperience;
use App\Models\Employee\Backgrounds\WorkReference;
use App\Models\Employee\Education\FormalEducation;
use App\Models\Employee\Education\Training;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

use App\Models\Employee\PersonalInfo\Address;
use App\Models\Employee\PersonalInfo\Contact;
use App\Models\Organization\Position;
use App\Models\User;

class Employee extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'identification',
        'passport',
        'first_name',
        'second_name',
        'last_name',
        'second_last_name',
        'date_of_birth',
        'gender',
        'ethnicity',
        'marital_status',
        'blood_type',
        'nationality',
        'military_id',
        'contact_id',
        'address_id',
        'position_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Obtener el nombre completo del empleado
    public function getFullNameAttribute()
    {
        $fullName = trim($this->first_name . ' ' . $this->second_name . ' ' . $this->last_name . ' ' . $this->second_last_name);
        return $fullName !== '' ? $fullName : null;
    }

    // Obtener el nombre del empleado
    public function getNameAttribute()
    {
        $Name = trim($this->first_name . ' ' . $this->last_name);
        return $Name !== '' ? $Name : null;
    }

    // Obtener la foto del usuario
    public function userPhoto()
    {
        return $this->user()->select('photo')->first()->photo ?? null;
    }
    
    // Relacion con user 1 - 1
    public function user()
    {
        return $this->hasOne(User::class);
    }

    //Relacion con Address 1 - 1
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    //Relacion con Contact 1 - 1
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    //Relacion con Position 1 - 1
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    //Relacion con Training 1-n 
    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    //Relacion con FormalEducation 1-n
    public function formalEducations()
    {
        return $this->hasMany(FormalEducation::class);
    }

    //Relacion con WorkExperience 1-n
    public function workExperiences()
    {
        return $this->hasMany(WorkExperience::class);
    }

    //Relacion con WorkReference 1-n
    public function workReferences()
    {
        return $this->hasMany(WorkReference::class);
    }

    //Relacion con Publications 1-n
    public function publications()
    {
        return $this->hasMany(Publication::class);
    }

    //Busquedas
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function ($query, $search) {
            $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('identification', 'like', '%' . $search . '%')
                ->orWhere('nationality', 'like', '%' . $search . '%');
        });
    }
}