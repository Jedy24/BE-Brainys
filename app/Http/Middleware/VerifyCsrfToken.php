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
        'api/capaian-pembelajaran/mata-pelajaran',
        'api/capaian-pembelajaran/fase',
        'api/capaian-pembelajaran/element',
        'api/capaian-pembelajaran/final',
        'api/capaian-pembelajaran/final',
        'callback/paydisini',
        'callback/xendit',
    ];
}
