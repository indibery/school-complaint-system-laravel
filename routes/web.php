<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 웹 인증 필요 라우트
Route::middleware(['auth'])->group(function () {
    // 대시보드
    Route::get('/dashboard', [App\Http\Controllers\Web\DashboardController::class, 'index'])->name('dashboard');
    
    // 민원 관리
    Route::resource('complaints', App\Http\Controllers\Web\ComplaintController::class);
    
    // 민원 추가 기능
    Route::post('/complaints/{complaint}/comments', [App\Http\Controllers\Web\ComplaintController::class, 'storeComment'])->name('complaints.comments.store');
    Route::delete('/comments/{comment}', [App\Http\Controllers\Web\ComplaintController::class, 'destroyComment'])->name('comments.destroy');
    Route::post('/complaints/{complaint}/attachments', [App\Http\Controllers\Web\ComplaintController::class, 'uploadAttachment'])->name('complaints.attachments.upload');
    Route::get('/attachments/{attachment}/download', [App\Http\Controllers\Web\ComplaintController::class, 'downloadAttachment'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Web\ComplaintController::class, 'deleteAttachment'])->name('attachments.delete');
    Route::get('/complaints/{complaint}/attachments/{attachment}/download', [App\Http\Controllers\Web\ComplaintController::class, 'downloadAttachment'])->name('complaints.download-attachment');
    Route::post('/complaints/bulk-update', [App\Http\Controllers\Web\ComplaintController::class, 'bulkUpdate'])->name('complaints.bulk-update');
    Route::get('/complaints/export', [App\Http\Controllers\Web\ComplaintController::class, 'export'])->name('complaints.export');
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Web\ComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Web\ComplaintController::class, 'assignUser'])->name('complaints.assign-user');
    Route::put('/complaints/{complaint}/priority', [App\Http\Controllers\Web\ComplaintController::class, 'updatePriority'])->name('complaints.update-priority');
    Route::get('/complaints/search', [App\Http\Controllers\Web\ComplaintController::class, 'search'])->name('complaints.search');
    Route::get('/complaints/statistics', [App\Http\Controllers\Web\ComplaintController::class, 'statistics'])->name('complaints.statistics');
    
    // 사용자 관리 (관리자만)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', App\Http\Controllers\Web\UserController::class);
        Route::resource('categories', App\Http\Controllers\Web\CategoryController::class);
        Route::resource('departments', App\Http\Controllers\Web\DepartmentController::class);
        Route::get('/settings', [App\Http\Controllers\Web\SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\Web\SettingController::class, 'update'])->name('settings.update');
    });
    
    // 통계 및 리포트
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Web\ReportController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Web\ReportController::class, 'export'])->name('export');
    });
});

// API 라우트 (내부 웹 애플리케이션용)
Route::prefix('api')->middleware('auth')->group(function () {
    // 민원 API
    Route::get('/complaints', [App\Http\Controllers\Api\ComplaintController::class, 'index']);
    Route::get('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'show']);
    Route::post('/complaints', [App\Http\Controllers\Api\ComplaintController::class, 'store']);
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

require __DIR__.'/auth.php';
