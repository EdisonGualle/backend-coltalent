<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parish extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'canton_id'
    ];
    public $timestamps = false;


    public function canton()
    {
        return $this->belongsTo(Canton::class);
    }
}
