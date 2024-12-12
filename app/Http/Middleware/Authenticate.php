<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * No redirige a ninguna ruta. Devuelve null para APIs.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Devuelve null si espera JSON, para evitar redirección en APIs.
        return $request->expectsJson() ? null : null;
    }

    /**
     * Devuelve un error JSON para solicitudes no autenticadas.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Respuesta JSON para solicitudes API no autenticadas
        abort(response()->json(['error' => 'Unauthenticated'], 401));
    }
}
