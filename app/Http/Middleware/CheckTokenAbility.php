<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Helpers\ApiResponseHelper;

class CheckTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return ApiResponseHelper::unauthorized('인증이 필요합니다.');
        }

        $token = $request->user()->currentAccessToken();
        
        if (!$token) {
            return ApiResponseHelper::unauthorized('유효한 토큰이 필요합니다.');
        }

        // 관리자는 모든 권한 허용
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 토큰에 필요한 권한이 있는지 확인
        if (!$token->can($ability)) {
            return ApiResponseHelper::forbidden('해당 작업을 수행할 권한이 없습니다.');
        }

        return $next($request);
    }
}
