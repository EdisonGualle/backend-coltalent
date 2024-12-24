<?php

namespace App\Models\Employee;

use App\Models\Contracts\Contract;
use App\Models\Employee\Backgrounds\Language;
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
use App\Models\Holidays\CompensatoryDay;
use App\Models\Holidays\HolidayAssignment;
use App\Models\Holidays\HolidayWorkRecord;
use App\Models\Leave\Delegation;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveComment;
use App\Models\Organization\Position;
use App\Models\User;
use App\Models\Work\OvertimeWork;

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

    public function overtimeWorks()
    {
        return $this->hasMany(OvertimeWork::class, 'employee_id');
    }


    // Relación uno a muchos con HolidayAssignment
    public function holidayAssignments()
    {
        return $this->hasMany(HolidayAssignment::class, 'employee_id');
    }

    // Relación uno a muchos con HolidayWorkRecord
    public function holidayWorkRecords()
    {
        return $this->hasMany(HolidayWorkRecord::class, 'employee_id');
    }

    // Relación uno a muchos con CompensatoryDay
    public function compensatoryDays()
    {
        return $this->hasMany(CompensatoryDay::class, 'employee_id');
    }

    // Relación uno a muchos
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'employee_id');
    }

    // Obtener el contrato actual
    public function currentContract()
    {
        return $this->hasOne(Contract::class, 'employee_id')->where('is_active', true);
    }

    // Relación con Leave como solicitante
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    // Relación con LeaveComment como commentedBy
    public function comments()
    {
        return $this->hasMany(LeaveComment::class, 'commented_by');
    }

    public function delegations()
    {
        return $this->hasMany(Delegation::class, 'delegate_id');
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

    // Relacion con idiomas 1-n  un empleado tiene muchos idiomas

    public function languages()
    {
        return $this->hasMany(Language::class);
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