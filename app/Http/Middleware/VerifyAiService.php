<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAiService
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string)$request->header('X-AI-SECRET', '');
        $expected = (string)config('services.ai.secret', '');

        if ($expected === '') return response()->json(['message' => 'Service not configured.'], 500);
        if (!hash_equals($expected, $incoming)) return response()->json(['message' => 'Unauthorized access.'], 403);

        return $next($request);
    }
}
