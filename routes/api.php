<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Helpers\ApiResponseHelper;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API 헬스 체크
Route::get('/health', function () {
    return ApiResponseHelper::success([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ], 'API 서비스가 정상적으로 작동중입니다.');
});

// API 버전 1
Route::prefix('v1')->group(function () {
    
    // 테스트 라우트 (개발용)
    Route::prefix('test')->group(function () {
        Route::get('/responses', [\App\Http\Controllers\Api\TestController::class, 'testResponses']);
        Route::get('/resources', [\App\Http\Controllers\Api\TestController::class, 'testResources']);
        Route::get('/auth', [\App\Http\Controllers\Api\TestController::class, 'testAuth']);
        Route::get('/error', [\App\Http\Controllers\Api\TestController::class, 'testError']);
        Route::get('/validation', [\App\Http\Controllers\Api\TestController::class, 'testValidation']);
        Route::get('/pagination', [\App\Http\Controllers\Api\TestController::class, 'testPagination']);
        Route::middleware('auth:sanctum')->get('/permission', [\App\Http\Controllers\Api\TestController::class, 'testPermission']);
    });
    
    // 인증 관련 라우트 (인증 없이 접근 가능)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
        Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
        
        // 인증 필요 라우트
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
            Route::post('/logout-all', [\App\Http\Controllers\Api\AuthController::class, 'logoutAll']);
            Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
            Route::put('/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
            Route::put('/change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);
            Route::post('/refresh-token', [\App\Http\Controllers\Api\AuthController::class, 'refreshToken']);
            Route::get('/tokens', [\App\Http\Controllers\Api\AuthController::class, 'tokens']);
            Route::delete('/tokens/{tokenId}', [\App\Http\Controllers\Api\AuthController::class, 'revokeToken']);
            Route::post('/deactivate', [\App\Http\Controllers\Api\AuthController::class, 'deactivate']);
        });
    });
    
    // 인증이 필요한 라우트
    Route::middleware('auth:sanctum')->group(function () {
        
        // 사용자 정보 조회
        Route::get('/user', function (Request $request) {
            return ApiResponseHelper::success($request->user(), '사용자 정보를 조회했습니다.');
        });
        
        // 사용자 관리 라우트
        Route::prefix('users')->group(function () {
            // 기본 CRUD 라우트
            Route::get('/', [\App\Http\Controllers\Api\UserController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\UserController::class, 'store']);
            Route::get('/{user}', [\App\Http\Controllers\Api\UserController::class, 'show']);
            Route::put('/{user}', [\App\Http\Controllers\Api\UserController::class, 'update']);
            Route::delete('/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
            
            // 상태 관리
            Route::put('/{user}/status', [\App\Http\Controllers\Api\UserController::class, 'updateStatus']);
            
            // 역할별 사용자 조회
            Route::get('/role/{role}', [\App\Http\Controllers\Api\UserController::class, 'getUsersByRole']);
            Route::get('/teachers', [\App\Http\Controllers\Api\UserController::class, 'getTeachers']);
            Route::get('/parents', [\App\Http\Controllers\Api\UserController::class, 'getParents']);
            Route::get('/staff', [\App\Http\Controllers\Api\UserController::class, 'getStaff']);
            Route::get('/students', [\App\Http\Controllers\Api\UserController::class, 'getStudents']);
            Route::get('/homeroom-teachers', [\App\Http\Controllers\Api\UserController::class, 'getHomeroomTeachers']);
            Route::get('/students/by-class', [\App\Http\Controllers\Api\UserController::class, 'getStudentsByClass']);
            
            // 검색 및 필터링
            Route::post('/search', [\App\Http\Controllers\Api\UserController::class, 'advancedSearch']);
            Route::get('/suggestions', [\App\Http\Controllers\Api\UserController::class, 'getSearchSuggestions']);
            Route::get('/filter-options', [\App\Http\Controllers\Api\UserController::class, 'getFilterOptions']);
            
            // 통계 및 대량 작업
            Route::get('/statistics', [\App\Http\Controllers\Api\UserController::class, 'getUserStatistics']);
            Route::post('/export', [\App\Http\Controllers\Api\UserController::class, 'exportUsers']);
            Route::get('/bulk-options', [\App\Http\Controllers\Api\UserController::class, 'getBulkOptions']);
        });
        
        // 민원 관리 라우트
        Route::prefix('complaints')->group(function () {
            // 기본 CRUD 라우트
            Route::get('/', [\App\Http\Controllers\Api\ComplaintController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\ComplaintController::class, 'store']);
            Route::get('/{complaint}', [\App\Http\Controllers\Api\ComplaintController::class, 'show']);
            Route::put('/{complaint}', [\App\Http\Controllers\Api\ComplaintController::class, 'update']);
            Route::delete('/{complaint}', [\App\Http\Controllers\Api\ComplaintController::class, 'destroy']);
            
            // 상태 관리
            Route::put('/{complaint}/status', [\App\Http\Controllers\Api\ComplaintController::class, 'updateStatus']);
            Route::put('/{complaint}/assign', [\App\Http\Controllers\Api\ComplaintController::class, 'assign']);
            Route::put('/{complaint}/priority', [\App\Http\Controllers\Api\ComplaintController::class, 'updatePriority']);
            
            // 이관 관리 (단순화된 시스템)
            Route::post('/{complaint}/transfer', [\App\Http\Controllers\Api\ComplaintController::class, 'transfer']);
            Route::get('/{complaint}/transfer-options', [\App\Http\Controllers\Api\ComplaintController::class, 'getTransferOptions']);
            Route::get('/transfer-stats', [\App\Http\Controllers\Api\ComplaintController::class, 'getTransferStats']);
            
            // 대량 작업
            Route::post('/bulk-status', [\App\Http\Controllers\Api\ComplaintController::class, 'bulkStatusUpdate']);
            Route::post('/bulk-assign', [\App\Http\Controllers\Api\ComplaintController::class, 'bulkAssign']);
            Route::post('/bulk-delete', [\App\Http\Controllers\Api\ComplaintController::class, 'bulkDelete']);
            
            // 통계 및 검색
            Route::get('/statistics', [\App\Http\Controllers\Api\ComplaintController::class, 'getStatistics']);
            Route::post('/search', [\App\Http\Controllers\Api\ComplaintController::class, 'search']);
            Route::post('/export', [\App\Http\Controllers\Api\ComplaintController::class, 'export']);
            
            // 특별 기능
            Route::post('/{complaint}/follow-up', [\App\Http\Controllers\Api\ComplaintController::class, 'scheduleFollowUp']);
            Route::post('/{complaint}/satisfaction-survey', [\App\Http\Controllers\Api\ComplaintController::class, 'scheduleSatisfactionSurvey']);
        });
        
        // 댓글 관리 라우트
        Route::prefix('comments')->group(function () {
            // 민원별 댓글 조회
            Route::get('/complaint/{complaint}', [\App\Http\Controllers\Api\CommentController::class, 'index']);
            
            // 댓글 CRUD
            Route::post('/complaint/{complaint}', [\App\Http\Controllers\Api\CommentController::class, 'store']);
            Route::get('/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'show']);
            Route::put('/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
            Route::delete('/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);
            
            // 대댓글 조회
            Route::get('/{comment}/replies', [\App\Http\Controllers\Api\CommentController::class, 'replies']);
            
            // 대량 작업
            Route::post('/bulk-delete', [\App\Http\Controllers\Api\CommentController::class, 'bulkDelete']);
            
            // 통계
            Route::get('/statistics', [\App\Http\Controllers\Api\CommentController::class, 'getStatistics']);
        });
        
        // 알림 관리 라우트
        Route::prefix('notifications')->group(function () {
            // 알림 목록 조회
            Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            
            // 읽지 않은 알림 개수
            Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
            
            // 특정 알림 조회
            Route::get('/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'show']);
            
            // 알림 읽음 처리
            Route::put('/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
            Route::put('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
            
            // 알림 삭제
            Route::delete('/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
            Route::delete('/clear-read', [\App\Http\Controllers\Api\NotificationController::class, 'clearRead']);
            
            // 알림 설정
            Route::get('/settings', [\App\Http\Controllers\Api\NotificationController::class, 'settings']);
            Route::put('/settings', [\App\Http\Controllers\Api\NotificationController::class, 'updateSettings']);
        });
        
        // 카테고리 관리 라우트
        Route::prefix('categories')->group(function () {
            // 기본 CRUD 라우트
            Route::get('/', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
            Route::get('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
            Route::put('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
            Route::delete('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);
            
            // 상태 관리
            Route::put('/{category}/toggle-status', [\App\Http\Controllers\Api\CategoryController::class, 'toggleStatus']);
            
            // 정렬 관리
            Route::post('/bulk-update-sort-order', [\App\Http\Controllers\Api\CategoryController::class, 'bulkUpdateSortOrder']);
            
            // 특별 기능
            Route::get('/tree', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);
            Route::get('/search', [\App\Http\Controllers\Api\CategoryController::class, 'search']);
            Route::get('/select-options', [\App\Http\Controllers\Api\CategoryController::class, 'getSelectOptions']);
            
            // 통계
            Route::get('/statistics', [\App\Http\Controllers\Api\CategoryController::class, 'getStatistics']);
        });
        
        // 부서 관리 라우트
        Route::prefix('departments')->group(function () {
            // 기본 CRUD 라우트
            Route::get('/', [\App\Http\Controllers\Api\DepartmentController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\DepartmentController::class, 'store']);
            Route::get('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'show']);
            Route::put('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'update']);
            Route::delete('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'destroy']);
            
            // 상태 관리
            Route::put('/{department}/toggle-status', [\App\Http\Controllers\Api\DepartmentController::class, 'toggleStatus']);
            
            // 부서원 관리
            Route::get('/{department}/members', [\App\Http\Controllers\Api\DepartmentController::class, 'getMembers']);
            Route::post('/{department}/members', [\App\Http\Controllers\Api\DepartmentController::class, 'addMember']);
            Route::delete('/{department}/members/{user}', [\App\Http\Controllers\Api\DepartmentController::class, 'removeMember']);
            
            // 통계 및 선택 옵션
            Route::get('/{department}/statistics', [\App\Http\Controllers\Api\DepartmentController::class, 'getStatistics']);
            Route::get('/select-options', [\App\Http\Controllers\Api\DepartmentController::class, 'getSelectOptions']);
        });
        
        // 첨부파일 관리 라우트
        Route::prefix('attachments')->group(function () {
            // 민원별 첨부파일 조회
            Route::get('/complaint/{complaint}', [\App\Http\Controllers\Api\AttachmentController::class, 'index']);
            
            // 첨부파일 업로드
            Route::post('/complaint/{complaint}', [\App\Http\Controllers\Api\AttachmentController::class, 'store']);
            
            // 첨부파일 조회/다운로드/삭제
            Route::get('/{attachment}', [\App\Http\Controllers\Api\AttachmentController::class, 'show']);
            Route::get('/{attachment}/download', [\App\Http\Controllers\Api\AttachmentController::class, 'download']);
            Route::delete('/{attachment}', [\App\Http\Controllers\Api\AttachmentController::class, 'destroy']);
            
            // 대량 작업
            Route::post('/bulk-delete', [\App\Http\Controllers\Api\AttachmentController::class, 'bulkDelete']);
            
            // 설정 및 통계
            Route::get('/upload-config', [\App\Http\Controllers\Api\AttachmentController::class, 'getUploadConfig']);
            Route::get('/statistics', [\App\Http\Controllers\Api\AttachmentController::class, 'getStatistics']);
        });
        
        // 대시보드 라우트
        Route::prefix('dashboard')->group(function () {
            // 대시보드 개요
            Route::get('/overview', [\App\Http\Controllers\Api\DashboardController::class, 'overview']);
            
            // 통계 데이터
            Route::get('/complaint-stats', [\App\Http\Controllers\Api\DashboardController::class, 'complaintStats']);
            Route::get('/user-performance', [\App\Http\Controllers\Api\DashboardController::class, 'userPerformance']);
            
            // 실시간 데이터
            Route::get('/alerts', [\App\Http\Controllers\Api\DashboardController::class, 'alerts']);
            Route::get('/recent-activities', [\App\Http\Controllers\Api\DashboardController::class, 'recentActivities']);
            
            // 시스템 상태 (관리자만)
            Route::get('/system-health', [\App\Http\Controllers\Api\DashboardController::class, 'systemHealth']);
            
            // 위젯 데이터
            Route::get('/widget', [\App\Http\Controllers\Api\DashboardController::class, 'getWidget']);
        });
        
        // 역할별 접근 제어 라우트
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // 관리자 전용 라우트
        });
        
        Route::middleware('role:teacher')->prefix('teacher')->group(function () {
            // 교사 전용 라우트
        });
        
        Route::middleware('role:parent')->prefix('parent')->group(function () {
            // 학부모 전용 라우트
        });
        
        Route::middleware('role:security_staff,ops_staff')->prefix('staff')->group(function () {
            // 직원 전용 라우트
        });
    });
});

// 404 에러 처리
Route::fallback(function () {
    return ApiResponseHelper::notFound('요청한 API 엔드포인트를 찾을 수 없습니다.');
});
