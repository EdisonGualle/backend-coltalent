<?php

namespace App\Models\Employee\Backgrounds;

use App\Models\Employee\Employee;
use App\Models\Employee\Backgrounds\PublicationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;
    
    protected $table = 'employee_publications';

    protected $fillable = [
        'publication_type_id',
        'title',
        'publisher',
        'isbn_issn',
        'authorship',
        'employee_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'publication_type_id'
    ];


    public function publicationType()
    {
        return $this->belongsTo(PublicationType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
