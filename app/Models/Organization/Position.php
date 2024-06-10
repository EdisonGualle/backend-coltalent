<?php
namespace App\Models\Organization;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'function', 
        'unit_id', 
        'direction_id',
        'is_manager',
        'is_general_manager', 
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    // Corregir la relación a employee en singular
    public function employee()
    {
        return $this->hasOne(Employee::class, 'position_id');
    }
}
