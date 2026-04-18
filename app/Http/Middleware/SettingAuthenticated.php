<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('setting_authenticated')) {
            return redirect()->route('setting-document.login');
        }

        return $next($request);
    }
}