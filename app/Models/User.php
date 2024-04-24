<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Employee\Employee;
use App\Models\Other\UserState;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',              
        'email',             
        'password',  
        'photo',       
        'employee_id',       // Identificador para el empleado asociado
        'role_id',           // Identificador para el rol
        'user_state_id',     // Identificador para el estado del usuario
        'failed_attempts',   // Número de intentos de inicio de sesión fallidos
        'blocked_until',     // Fecha y hora hasta la cual la cuenta está bloqueada
    ];
    protected $hidden = [
        'password'
    ];

    protected $dates = ['blocked_until'];

    public function getRouteKeyName()
    {
        return 'username';
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
            // Puedes agregar más campos aquí según tus necesidades
        });
    }

    // Relacion con employee 1 - 1
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relacion con  UserState 1 - 1
    public function userState(){
        return $this->belongsTo(UserState::class);
    }

    // Relacion con Role 1 - n
    public function role(){
        return $this->belongsTo(Role::class);
    }
}
