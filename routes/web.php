<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ComplaintController;
use App\Http\Controllers\Web\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 홈페이지 - 로그인 페이지로 리다이렉트
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// 인증 관련 라우트 (게스트만 접근)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// 인증이 필요한 라우트
Route::middleware('auth')->group(function () {
    
    // 로그아웃
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // 대시보드
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // 민원 관리
    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/', [ComplaintController::class, 'index'])->name('index');
        Route::get('/create', [ComplaintController::class, 'create'])->name('create');
        Route::post('/', [ComplaintController::class, 'store'])->name('store');
        Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show');
        Route::get('/{complaint}/edit', [ComplaintController::class, 'edit'])->name('edit');
        Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
        Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
        
        // 대량 업데이트
        Route::post('/bulk-update', [ComplaintController::class, 'bulkUpdate'])->name('bulk-update');
        
        // 내보내기
        Route::get('/export', [ComplaintController::class, 'export'])->name('export');
        
        // 민원 상태 변경
        Route::patch('/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{complaint}/assign', [ComplaintController::class, 'assign'])->name('assign');
        Route::post('/{complaint}/transfer', [ComplaintController::class, 'transfer'])->name('transfer');
        
        // 댓글 관련
        Route::post('/{complaint}/comments', [ComplaintController::class, 'storeComment'])->name('comments.store');
        Route::delete('/comments/{comment}', [ComplaintController::class, 'destroyComment'])->name('comments.destroy');
        
        // 파일 관련
        Route::post('/{complaint}/attachments', [ComplaintController::class, 'uploadAttachment'])->name('attachments.upload');
        Route::get('/attachments/{attachment}/download', [ComplaintController::class, 'downloadAttachment'])->name('attachments.download');
        Route::delete('/attachments/{attachment}', [ComplaintController::class, 'deleteAttachment'])->name('attachments.delete');
    });
    
    // 사용자 관리 (관리자/부서장만)
    Route::middleware('role:admin,department_head')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/status', [UserController::class, 'updateStatus'])->name('update-status');
    });
    
    // 프로필 관리
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserController::class, 'profile'])->name('show');
        Route::get('/edit', [UserController::class, 'editProfile'])->name('edit');
        Route::put('/', [UserController::class, 'updateProfile'])->name('update');
        Route::put('/password', [UserController::class, 'updatePassword'])->name('update-password');
    });
    
    // 통계 및 보고서 (관리자/부서장만)
    Route::middleware('role:admin,department_head')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [DashboardController::class, 'reports'])->name('index');
        Route::get('/export', [DashboardController::class, 'export'])->name('export');
    });
    
    // 설정 (관리자만)
    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [DashboardController::class, 'settings'])->name('index');
        Route::put('/system', [DashboardController::class, 'updateSystemSettings'])->name('system.update');
        Route::get('/categories', [DashboardController::class, 'categories'])->name('categories');
        Route::post('/categories', [DashboardController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [DashboardController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [DashboardController::class, 'destroyCategory'])->name('categories.destroy');
    });
});

// AJAX 라우트 (API와 유사하지만 웹 전용)
Route::middleware('auth')->prefix('ajax')->name('ajax.')->group(function () {
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/complaints/stats', [DashboardController::class, 'getStats'])->name('complaints.stats');
    Route::post('/complaints/{complaint}/quick-update', [ComplaintController::class, 'quickUpdate'])->name('complaints.quick-update');
});
 [App\Http\Controllers\Api\ComplaintController::class, 'store']);
    Route::put('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'update']);
    Route::delete('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'destroy']);
    
    // 대량 업데이트
    Route::post('/complaints/bulk-update', [App\Http\Controllers\Api\ComplaintController::class, 'bulkUpdate']);
    
    // 상태 관리
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Api\ComplaintController::class, 'updateStatus']);
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Api\ComplaintController::class, 'assignUser']);
    Route::put('/complaints/{complaint}/priority', [App\Http\Controllers\Api\ComplaintController::class, 'updatePriority']);
    
    // 댓글 API
    Route::get('/complaints/{complaint}/comments', [App\Http\Controllers\Api\CommentController::class, 'index']);
    Route::post('/complaints/{complaint}/comments', [App\Http\Controllers\Api\CommentController::class, 'store']);
    Route::put('/comments/{comment}', [App\Http\Controllers\Api\CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [App\Http\Controllers\Api\CommentController::class, 'destroy']);
    
    // 첨부파일 API
    Route::post('/complaints/{complaint}/attachments', [App\Http\Controllers\Api\AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Api\AttachmentController::class, 'destroy']);
    
    // 카테고리 API
    Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    
    // 사용자 API
    Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);
    Route::get('/users/assignees', [App\Http\Controllers\Api\UserController::class, 'getAssignees']);
    
    // 대시보드 API
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'getStats']);
    
    // 부서 API
    Route::get('/departments', [App\Http\Controllers\Api\DepartmentController::class, 'index']);
});

// 외부 API 라우트 (토큰 기반 인증)
Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    // 민원 API
    Route::apiResource('complaints', App\Http\Controllers\Api\ComplaintController::class);
    Route::post('/complaints/bulk-update', [App\Http\Controllers\Api\ComplaintController::class, 'bulkUpdate']);
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Api\ComplaintController::class, 'updateStatus']);
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Api\ComplaintController::class, 'assignUser']);
    Route::put('/complaints/{complaint}/priority', [App\Http\Controllers\Api\ComplaintController::class, 'updatePriority']);
    
    // 댓글 API
    Route::apiResource('comments', App\Http\Controllers\Api\CommentController::class);
    
    // 첨부파일 API
    Route::apiResource('attachments', App\Http\Controllers\Api\AttachmentController::class);
    
    // 카테고리 API
    Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);
    
    // 사용자 API
    Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
    
    // 대시보드 API
    Route::get('/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'getStats']);
    
    // 부서 API
    Route::apiResource('departments', App\Http\Controllers\Api\DepartmentController::class);
});

// 게스트 접근 가능한 API (인증 없음)
Route::prefix('api/v1')->group(function () {
    // 인증 API
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
    
    // 공개 카테고리 목록
    Route::get('/categories/public', [App\Http\Controllers\Api\CategoryController::class, 'publicIndex']);
    
    // 테스트 API
    Route::get('/test', [App\Http\Controllers\Api\TestController::class, 'index']);
});

// 개발/디버깅 라우트 (개발 환경에서만)
if (app()->environment('local')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('/reset-db', function () {
            if (config('app.env') !== 'local') {
                abort(403, '개발 환경에서만 사용 가능합니다.');
            }
            
            Artisan::call('migrate:fresh', ['--seed' => true]);
            return response()->json(['message' => '데이터베이스가 초기화되었습니다.']);
        });
        
        Route::get('/create-test-data', function () {
            if (config('app.env') !== 'local') {
                abort(403, '개발 환경에서만 사용 가능합니다.');
            }
            
            // 테스트 데이터 생성
            App\Models\User::factory()->count(10)->create();
            App\Models\Complaint::factory()->count(50)->create();
            
            return response()->json(['message' => '테스트 데이터가 생성되었습니다.']);
        });
    });
}

// 404 에러 처리
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json(['message' => 'API 경로를 찾을 수 없습니다.'], 404);
    }
    
    return view('errors.404');
});
    // 민원 상급 이관
    Route::put('/complaints/{complaint}/escalate', [App\Http\Controllers\Api\ComplaintController::class, 'escalate']);
    
    // 댓글 관련 추가 라우트
    Route::get('/complaints/{complaint}/comments/recent', [App\Http\Controllers\Api\CommentController::class, 'recent']);
    
    // 첨부파일 관련 추가 라우트
    Route::get('/attachments/{attachment}/preview', [App\Http\Controllers\Api\AttachmentController::class, 'preview']);
    Route::post('/attachments/bulk-delete', [App\Http\Controllers\Api\AttachmentController::class, 'bulkDelete']);
