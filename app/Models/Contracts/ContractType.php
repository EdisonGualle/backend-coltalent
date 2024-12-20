<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'max_duration_months',
        'renewable',
        'weekly_hours',
        'vacation_days_per_year',
        'max_vacation_days',
        'min_tenure_months_for_vacation'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'contract_type_id');
    }
}
