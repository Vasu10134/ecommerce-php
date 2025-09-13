<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/checkout/mercadopago/notify',
        '/paytm-callback',
        '/api/paytm-callback',
        '/razorpay-callback',
        'api/razorpay-callback',
        '/flutter/notify',
        '/front/flutter/notify',
        'user/api/flutter/notify',
        '**/ssl/notify',
        'api/ssl/notify',
        '/ssl/cancle',
        'api/ssl/cancle',
        '/user/mercadopago/notify',
        '/user/paytm/notify',
        '/user/razorpay/notify',
        'user/api/razorpay-callback',
        '/user/uflutter/notify',
        '/user/ssl/notify',
        '/user/ssl/cancle',
        '/user/deposit/mercadopago/notify',
        '/user/deposit/paytm/notify',
        '/user/deposit/razorpay/notify',
        '/user/dflutter/notify',
        '/user/deposit/ssl/notify',
        '/user/deposit/ssl/cancle',
        '/user/api/ssl/notify',
        '/user/api/ssl/cancle',
        '/vendor/paytm/notify',
        '/vendor/razorpay/notify',
        '/vendor/vflutter/notify'
    ];
}
