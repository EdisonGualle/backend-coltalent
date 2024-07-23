<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 
        'value',
        'description'
    ];
    
    public $timestamps = false;

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

}
