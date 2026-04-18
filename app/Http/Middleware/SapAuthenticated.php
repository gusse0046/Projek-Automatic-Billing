<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SapAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('sap_authenticated')) {
            return redirect()->route('sap.login.form');
        }

        return $next($request);
    }
}