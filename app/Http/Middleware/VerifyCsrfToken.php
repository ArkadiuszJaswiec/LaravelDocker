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
        '/auth/registration',
        '/auth/registration/confirm',
        '/auth/login',
        '/auth/refresh',
        'project/*',
        'project',
        'tasks/*',
        'tasks',
        'team/*',
        'team',
        'comment/*',
        'comment'
    ];
}
