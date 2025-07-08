<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ForceWebResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Accept 헤더를 HTML로 강제 변경
        $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        
        $response = $next($request);
        
        // JSON 응답을 HTML로 강제 변환
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // 간단한 HTML 페이지 반환
            $html = '<html><head><title>디버그 정보</title></head><body>';
            $html .= '<h1>웹 컴트롤러 동작 확인</h1>';
            $html .= '<p>이 페이지가 보인다면 웹 컴트롤러가 정상 작동하고 있습니다.</p>';
            $html .= '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            $html .= '</body></html>';
            
            return response($html)->header('Content-Type', 'text/html');
        }
        
        return $response;
    }
}
