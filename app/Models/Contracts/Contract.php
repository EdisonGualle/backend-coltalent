<?php

namespace App\Models\Contracts;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'contract_type_id',
        'start_date',
        'end_date',
        'termination_reason',
        'is_active'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }
}
