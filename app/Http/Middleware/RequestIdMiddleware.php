<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->headers->get('X-Request-Id', (string) Str::uuid());

        // Añadir el request_id al contexto de los logs para toda la request
        Log::withContext([
            'request_id' => $requestId,
        ]);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Propagar el request_id en la respuesta para facilitar el debugging
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
