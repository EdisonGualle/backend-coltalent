<?php
namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
class Direction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'function',
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    // Añade deleted_at a las fechas para manejarlas automáticamente
    protected $dates = ['deleted_at'];
    
    // Relación con unidades 1-n
    public function units() {
        return $this->hasMany(Unit::class);
    }

    // Relación con posiciones 1-n
    public function positions() {
        return $this->hasMany(Position::class);
    }

    // Relación con el jefe de la dirección
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
