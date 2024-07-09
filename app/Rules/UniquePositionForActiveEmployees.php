<?php

namespace App\Rules;

use App\Models\Employee\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Other\UserState;
use Closure;

class UniquePositionForActiveEmployees implements ValidationRule
{
    protected $employeeId;

    public function __construct($employeeId = null)
    {
        $this->employeeId = $employeeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $activeState = UserState::where('name', 'activo')->first();

        $exists = Employee::where('position_id', $value)
            ->where('id', '!=', $this->employeeId)
            ->whereHas('user', function ($query) use ($activeState) {
                $query->where('user_state_id', $activeState->id);
            })
            ->exists();

        if ($exists) {
            $fail('Este cargo ya está asignado a un empleado activo.');
        }
    }
}
