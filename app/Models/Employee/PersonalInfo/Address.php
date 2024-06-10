<?php

namespace App\Models\Employee\PersonalInfo;

use App\Models\Address\Parish;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'employee_addresses';

    protected $fillable = [
        'sector',
        'streets',
        'main_street',
        'secondary street',
        'number',
        'reference',       
        'parish_id'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function parish(){
        return $this->belongsTo(Parish::class);
    }

    public function employee(){
        return $this->hasOne(Employee::class);
    }

    public function canton()
    {
        return $this->parish->canton;
    }

    public function province()
    {
        return $this->parish->canton->province;
    }

}
