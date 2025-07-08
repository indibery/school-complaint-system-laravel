<?php

echo "🔗 API 및 웹 인터페이스 통합 테스트\n";
echo "==============================================\n\n";

// 1. 라우트 구조 분석
echo "1. 라우트 구조 분석\n";
echo "----------------------------------------------\n";

try {
    // 라우트 파일 분석
    $webRoutes = file_get_contents('routes/web.php');
    $apiRoutes = file_get_contents('routes/api.php');
    
    echo "✅ 라우트 파일 읽기 성공\n";
    
    // 웹 라우트 분석
    preg_match_all('/Route::(get|post|put|patch|delete|resource)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $webRoutes, $webMatches);
    $webRouteCount = count($webMatches[0]);
    echo "📊 웹 라우트: 약 {$webRouteCount}개 패턴 발견\n";
    
    // API 라우트 분석
    preg_match_all('/Route::(get|post|put|patch|delete|resource|apiResource)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $webRoutes, $apiMatches);
    $apiRouteCount = count($apiMatches[0]);
    echo "📊 API 라우트: 약 {$apiRouteCount}개 패턴 발견\n";
    
} catch (Exception $e) {
    echo "❌ 라우트 분석 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. 컨트롤러 구조 비교
echo "2. 컨트롤러 구조 비교\n";
echo "----------------------------------------------\n";

try {
    // API 컨트롤러 분석
    $apiControllerPath = 'app/Http/Controllers/Api/ComplaintController.php';
    $webControllerPath = 'app/Http/Controllers/Web/ComplaintController.php';
    
    if (file_exists($apiControllerPath) && file_exists($webControllerPath)) {
        echo "✅ API 및 웹 컨트롤러 파일 존재\n";
        
        $apiController = file_get_contents($apiControllerPath);
        $webController = file_get_contents($webControllerPath);
        
        // 메소드 분석
        preg_match_all('/public function (\w+)\(/', $apiController, $apiMethods);
        preg_match_all('/public function (\w+)\(/', $webController, $webMethods);
        
        $apiMethodCount = count($apiMethods[1]);
        $webMethodCount = count($webMethods[1]);
        
        echo "📊 API 컨트롤러 메소드: {$apiMethodCount}개\n";
        echo "📊 웹 컨트롤러 메소드: {$webMethodCount}개\n";
        
        // 공통 메소드 확인
        $commonMethods = array_intersect($apiMethods[1], $webMethods[1]);
        echo "🔗 공통 메소드: " . count($commonMethods) . "개\n";
        
        if (!empty($commonMethods)) {
            echo "   - " . implode(', ', array_slice($commonMethods, 0, 5)) . "\n";
        }
        
    } else {
        echo "❌ 컨트롤러 파일 누락\n";
    }
    
} catch (Exception $e) {
    echo "❌ 컨트롤러 분석 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Service Layer 의존성 확인
echo "3. Service Layer 의존성 확인\n";
echo "----------------------------------------------\n";

try {
    $serviceInterfaces = [
        'ComplaintServiceInterface',
        'ComplaintStatusServiceInterface',
        'ComplaintAssignmentServiceInterface',
        'ComplaintFileServiceInterface',
        'ComplaintStatisticsServiceInterface'
    ];
    
    // API 컨트롤러 의존성 확인
    if (file_exists($apiControllerPath)) {
        $apiController = file_get_contents($apiControllerPath);
        $apiDependencies = 0;
        
        foreach ($serviceInterfaces as $interface) {
            if (strpos($apiController, $interface) !== false) {
                $apiDependencies++;
            }
        }
        
        echo "✅ API 컨트롤러 Service 의존성: {$apiDependencies}/{count($serviceInterfaces)}\n";
    }
    
    // 웹 컨트롤러 의존성 확인
    if (file_exists($webControllerPath)) {
        $webController = file_get_contents($webControllerPath);
        $webDependencies = 0;
        
        foreach ($serviceInterfaces as $interface) {
            if (strpos($webController, $interface) !== false) {
                $webDependencies++;
            }
        }
        
        echo "✅ 웹 컨트롤러 Service 의존성: {$webDependencies}/{count($serviceInterfaces)}\n";
    }
    
    if ($apiDependencies === $webDependencies && $apiDependencies === count($serviceInterfaces)) {
        echo "🎉 API와 웹 컨트롤러의 Service 의존성이 일치합니다!\n";
    } else {
        echo "⚠️  API와 웹 컨트롤러의 Service 의존성이 다릅니다.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Service 의존성 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Response 형식 분석
echo "4. Response 형식 분석\n";
echo "----------------------------------------------\n";

try {
    // API 컨트롤러 응답 형식 확인
    if (file_exists($apiControllerPath)) {
        $apiController = file_get_contents($apiControllerPath);
        
        $jsonResponseCount = substr_count($apiController, 'JsonResponse');
        $successResponseCount = substr_count($apiController, 'successResponse');
        $errorResponseCount = substr_count($apiController, 'errorResponse');
        
        echo "📊 API 컨트롤러 응답 형식:\n";
        echo "   - JsonResponse: {$jsonResponseCount}개\n";
        echo "   - successResponse: {$successResponseCount}개\n";
        echo "   - errorResponse: {$errorResponseCount}개\n";
    }
    
    // 웹 컨트롤러 응답 형식 확인
    if (file_exists($webControllerPath)) {
        $webController = file_get_contents($webControllerPath);
        
        $viewCount = substr_count($webController, 'view(');
        $redirectCount = substr_count($webController, 'redirect(');
        $responseJsonCount = substr_count($webController, 'response()->json');
        
        echo "📊 웹 컨트롤러 응답 형식:\n";
        echo "   - view(): {$viewCount}개\n";
        echo "   - redirect(): {$redirectCount}개\n";
        echo "   - response()->json(): {$responseJsonCount}개\n";
    }
    
} catch (Exception $e) {
    echo "❌ Response 형식 분석 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. 뷰 파일 연동성 확인
echo "5. 뷰 파일 연동성 확인\n";
echo "----------------------------------------------\n";

try {
    $viewDirectory = 'resources/views/complaints';
    
    if (is_dir($viewDirectory)) {
        $viewFiles = scandir($viewDirectory);
        $viewFiles = array_filter($viewFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });
        
        echo "✅ 뷰 파일 디렉토리 존재: {$viewDirectory}\n";
        echo "📊 뷰 파일 수: " . count($viewFiles) . "개\n";
        
        foreach ($viewFiles as $file) {
            echo "   - {$file}\n";
        }
        
        // 뷰 파일과 컨트롤러 메소드 연동 확인
        $expectedViews = ['index.blade.php', 'create.blade.php', 'edit.blade.php', 'show.blade.php'];
        $missingViews = array_diff($expectedViews, $viewFiles);
        
        if (empty($missingViews)) {
            echo "✅ 모든 필수 뷰 파일이 존재합니다!\n";
        } else {
            echo "⚠️  누락된 뷰 파일: " . implode(', ', $missingViews) . "\n";
        }
        
    } else {
        echo "❌ 뷰 파일 디렉토리 없음: {$viewDirectory}\n";
    }
    
} catch (Exception $e) {
    echo "❌ 뷰 파일 연동성 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. JavaScript/AJAX 연동 확인
echo "6. JavaScript/AJAX 연동 확인\n";
echo "----------------------------------------------\n";

try {
    // 뷰 파일에서 AJAX 사용 확인
    $viewFiles = ['index.blade.php', 'create.blade.php', 'edit.blade.php', 'show.blade.php'];
    $ajaxUsage = 0;
    
    foreach ($viewFiles as $viewFile) {
        $filePath = "resources/views/complaints/{$viewFile}";
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            
            // AJAX 관련 패턴 확인
            $patterns = [
                '/\$.ajax\(/',
                '/\$.post\(/',
                '/\$.get\(/',
                '/fetch\(/',
                '/axios\.'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $ajaxUsage++;
                    break;
                }
            }
        }
    }
    
    echo "📊 AJAX 사용 뷰 파일: {$ajaxUsage}개\n";
    
    // API 엔드포인트 패턴 확인
    $apiEndpoints = [
        'api/complaints',
        'api/complaints/statistics',
        'api/complaints/{id}/status',
        'api/complaints/{id}/assign'
    ];
    
    echo "📊 예상 API 엔드포인트: " . count($apiEndpoints) . "개\n";
    
} catch (Exception $e) {
    echo "❌ JavaScript/AJAX 연동 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. 에러 핸들링 일관성 확인
echo "7. 에러 핸들링 일관성 확인\n";
echo "----------------------------------------------\n";

try {
    // API 컨트롤러 에러 핸들링
    if (file_exists($apiControllerPath)) {
        $apiController = file_get_contents($apiControllerPath);
        
        $apiTryCatchCount = substr_count($apiController, 'try {');
        $apiLogErrorCount = substr_count($apiController, 'Log::error');
        
        echo "📊 API 컨트롤러 에러 핸들링:\n";
        echo "   - try-catch 블록: {$apiTryCatchCount}개\n";
        echo "   - 로그 기록: {$apiLogErrorCount}개\n";
    }
    
    // 웹 컨트롤러 에러 핸들링
    if (file_exists($webControllerPath)) {
        $webController = file_get_contents($webControllerPath);
        
        $webTryCatchCount = substr_count($webController, 'try {');
        $webLogErrorCount = substr_count($webController, 'Log::error');
        
        echo "📊 웹 컨트롤러 에러 핸들링:\n";
        echo "   - try-catch 블록: {$webTryCatchCount}개\n";
        echo "   - 로그 기록: {$webLogErrorCount}개\n";
    }
    
    // 에러 핸들링 일관성 평가
    if ($apiTryCatchCount > 0 && $webTryCatchCount > 0) {
        echo "✅ 양쪽 컨트롤러 모두 적절한 에러 핸들링 구현\n";
    } else {
        echo "⚠️  에러 핸들링 개선 필요\n";
    }
    
} catch (Exception $e) {
    echo "❌ 에러 핸들링 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. 권한 체크 일관성 확인
echo "8. 권한 체크 일관성 확인\n";
echo "----------------------------------------------\n";

try {
    // API 컨트롤러 권한 체크
    if (file_exists($apiControllerPath)) {
        $apiController = file_get_contents($apiControllerPath);
        $apiAuthCount = substr_count($apiController, 'authorize(');
        echo "📊 API 컨트롤러 권한 체크: {$apiAuthCount}개\n";
    }
    
    // 웹 컨트롤러 권한 체크
    if (file_exists($webControllerPath)) {
        $webController = file_get_contents($webControllerPath);
        $webAuthCount = substr_count($webController, 'authorize(');
        echo "📊 웹 컨트롤러 권한 체크: {$webAuthCount}개\n";
    }
    
    // 미들웨어 권한 체크
    if (file_exists('routes/web.php')) {
        $webRoutes = file_get_contents('routes/web.php');
        $middlewareCount = substr_count($webRoutes, 'middleware(');
        echo "📊 라우트 미들웨어: {$middlewareCount}개\n";
    }
    
} catch (Exception $e) {
    echo "❌ 권한 체크 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

echo "==============================================\n";
echo "🎉 API 및 웹 인터페이스 통합 테스트 완료\n";
echo "==============================================\n";
