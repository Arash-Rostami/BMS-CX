<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Filament::auth()->check() && !auth()->check()) {
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
