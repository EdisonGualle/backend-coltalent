<?php

namespace App\Models\Employee\Backgrounds;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkReference extends Model
{
    use HasFactory;

    protected $table = 'employee_work_references';
    protected $fillable = [
        'name',
        'position',
        'company_name',
        'contact_number',
        'relationship_type',
        'employee_id'
    ];

    public $timestamps = false;

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
