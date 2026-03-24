<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockShopOnAdminHost
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'admin.zerobuy.in') {
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
