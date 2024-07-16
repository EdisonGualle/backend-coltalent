<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveState extends Model
{
    use HasFactory; 
    
    protected $fillable = [
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;


    // Relación con Leave
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
