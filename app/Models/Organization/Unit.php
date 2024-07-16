<?php
namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'function',
        'phone',
        'direction_id', 
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    // Relación con dirección 1-n
    public function direction() {
        return $this->belongsTo(Direction::class);
    }

    // Relación con posiciones 1-n
    public function positions() {
        return $this->hasMany(Position::class);
    }

    // Relación con el jefe de la unidad
    public function manager()
    {
        return $this->hasOne(Position::class)->where('is_manager', 1);
    }

    // Relación con el empleado que ocupa la posición de jefe
    public function managerEmployee()
    {
        return $this->manager()->with('employee'); 
    }
}
