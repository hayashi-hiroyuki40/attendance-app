<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $adminCorrectionController, string $method = 'index')
    {
        if ($request->user() && $request->user()->admin_status) {
            $request->route()->controllerClass = "App\\Http\\Controllers\\{$adminCorrectionController}";
            $request->route()->uses = "App\\Http\\Controllers\\{$adminCorrectionController}@{$method}";
        }

        return $next($request);
    }
}
