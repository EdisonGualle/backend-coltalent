<?php

namespace App\Models\Employee\PersonalInfo;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'employee_contacts';
    protected $fillable = [
        'personal_phone',
        'personal_email',
        'home_phone',
        'work_phone',
    ];

    public $timestamps = false;

    protected $hidden = ['created_at', 'updated_at'];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
