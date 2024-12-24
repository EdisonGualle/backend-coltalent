<?php

namespace App\Models\Holidays;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'date',
        'name',
        'is_recurring',
        'applies_to_all'
    ];

    public function assignments()
    {
        return $this->hasMany(HolidayAssignment::class, 'holiday_id');
    }

    public function workRecords()
    {
        return $this->hasMany(HolidayWorkRecord::class, 'holiday_id');
    }
}
