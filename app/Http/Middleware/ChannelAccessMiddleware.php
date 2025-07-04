<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ChannelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $channel): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => '인증이 필요합니다.',
                'data' => null
            ], 401);
        }

        $user = $request->user();
        $userChannel = $user->access_channel;
        
        // 관리자는 모든 채널 접근 가능
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 사용자 채널과 요청 채널이 일치하는지 확인
        if ($userChannel !== $channel) {
            return response()->json([
                'success' => false,
                'message' => '해당 채널에 접근할 수 없습니다.',
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
