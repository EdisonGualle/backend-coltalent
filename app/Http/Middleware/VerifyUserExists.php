<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class VerifyUserExists
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->route('user');
        if (!User::where('id', $userId)->exists()) {
            return response()->json([
                'message' => 'El usuario no existe',
            ], Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}