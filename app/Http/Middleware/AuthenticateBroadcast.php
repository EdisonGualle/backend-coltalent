<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateBroadcast
{
    public function handle($request, Closure $next)
    {
     
        $token = $request->bearerToken() ?? $request->header('Authorization');
    
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
    
            $accessToken = PersonalAccessToken::findToken($token);
    
            if ($accessToken) {
                $user = $accessToken->tokenable;
    
                Auth::login($user);
    
                // Asegúrate de que el usuario esté autorizado para el canal
                return $next($request);
            }
        }
    
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
}
