<?php

namespace App\Models\Employee\Backgrounds;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationType extends Model
{
   
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public $timestamps = false;

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function publications(){
        return $this->hasMany(Publication::class);
    }
}
