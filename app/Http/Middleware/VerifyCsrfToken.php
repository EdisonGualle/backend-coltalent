<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
       '/sanctum/csrf-cookie',
       '/broadcasting/auth',
    ];

    

    protected function tokensMatch($request)
    {
        $token = $request->header('X-XSRF-TOKEN') ?: $request->input('_token');
        $sessionToken = $request->session()->token();
        
        return hash_equals((string) $sessionToken, (string) $token);
    }
    
    
    
}   
