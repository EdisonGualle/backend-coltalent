<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Employee\Employee;

class VerifyEmployeeExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $employeeId = $request->route('employee');

        if (!Employee::where('id', $employeeId)->exists()) {
            return response()->json([
                'message' => 'El empleado no existe',
            ], Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}