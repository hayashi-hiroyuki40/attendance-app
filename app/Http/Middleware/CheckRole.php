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
    public function handle(Request $request, Closure $next, string $adminController, string $method = 'index'): Response
    {
        if ($request->user()?->is_admin) {
            return app()->call(["App\\Http\\Controllers\\{$adminController}", $method]);
        }

        return $next($request);
    }
}
