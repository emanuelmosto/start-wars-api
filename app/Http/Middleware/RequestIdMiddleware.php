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

        // Let's add the request_id to the request context for logs
        Log::withContext([
            'request_id' => $requestId,
        ]);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Propagate the request_id on the response for debugging
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
