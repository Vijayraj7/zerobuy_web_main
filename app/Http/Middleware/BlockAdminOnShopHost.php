<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAdminOnShopHost
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'shop.zerobuy.in') {
            return redirect('/shop/login');
        }

        return $next($request);
    }
}
