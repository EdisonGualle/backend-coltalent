<?php

namespace App\Models\Other;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    public function user(){
        return $this->hasOne(User::class);
    }
}
