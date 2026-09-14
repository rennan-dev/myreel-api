<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnalyticsKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('services.analytics.key', '');
        $provided = (string) $request->header('X-Analytics-Key', '');

        if ($key === '' || $provided === '' || ! hash_equals($key, $provided)) {
            abort(401, 'Chave de analytics inválida.');
        }

        return $next($request);
    }
}