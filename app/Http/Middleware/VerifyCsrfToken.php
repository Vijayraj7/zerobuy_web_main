<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'payment/*',
        'subscription/payment/*',
        'paytabs/*',
        'shop/advertisements/webhook/*',
        'api/seller/subscription/webhook/*',
        'api/shiprocket/webhook',
        'api/shiprocket/webhook/*',
        'api/seller/shiprocket/webhook',
        'api/seller/shiprocket/webhook/*',
    ];
}
