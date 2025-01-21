<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogCookies
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Procesar la solicitud
        $response = $next($request);

        // Registrar todas las cookies generadas en la respuesta
        if ($response->headers->has('Set-Cookie')) {
            $cookies = $response->headers->get('Set-Cookie');
            Log::info('Cookies generadas en la respuesta:', [
                'cookies' => $cookies,
                'ruta' => $request->path(),
            ]);
        }

        return $response;
    }
}
