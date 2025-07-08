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
        // API 라우트는 CSRF 검증에서 제외
        'api/*',
        
        // 특정 외부 연동 라우트도 제외 (필요시)
        'webhooks/*',
        'mobile-api/*',
    ];
}
