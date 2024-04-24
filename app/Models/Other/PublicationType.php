<?php

namespace App\Models\Other;

use App\Models\Employee\Backgrounds\Publication;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
    
    public function publications(){
        return $this->hasMany(Publication::class);
    }
}
