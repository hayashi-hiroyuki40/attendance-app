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
        // 管理者の場合は指定された管理者コントローラーを実行
        if ($request->user()?->is_admin) {
            return app()->call(["App\\Http\\Controllers\\{$adminController}", $method]);
        }

        // 一般ユーザーはそのまま通過
        return $next($request);
    }
}
