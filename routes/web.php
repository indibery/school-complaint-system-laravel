<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 홈페이지 접속 시 대시보드로 리다이렉트
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// 민원 목록 직접 라우트 (임시)
Route::get('/complaints', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    // complaints.index가 아닌 admin-panel/issues로 직접 리다이렉트
    return redirect('/admin-panel/issues');
})->middleware('web');

// 민원 목록 웹 페이지 (Herd 리라이트 회피)
Route::get('/web-complaints', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return app(\App\Http\Controllers\Web\ComplaintController::class)->index(request());
})->middleware(['web', 'auth', 'verified', 'force.web'])->name('web.complaints');

// 테스트 라우트
Route::get('/test-web', function () {
    return view('welcome');
});

Route::get('/test-json', function () {
    return response()->json(['message' => 'This is JSON response']);
});

// 강제 HTML 응답 테스트
Route::get('/force-html', function () {
    return response('<h1>웹 페이지가 정상 작동합니다!</h1><p>이 페이지가 보인다면 웹 컴트롤러가 작동하고 있습니다.</p>')
        ->header('Content-Type', 'text/html');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 웹 인증 필요 라우트
Route::middleware(['auth', 'verified'])->group(function () {
    // 대시보드
    Route::get('/dashboard', [App\Http\Controllers\Web\DashboardController::class, 'index'])->name('dashboard');
    
    // 테스트 - 민원 목록 직접 호출
    Route::get('/test-complaints', function () {
        return '민원 목록 페이지 테스트';
    });
    
    // 웹 민원 시스템 (웹서버 리라이트 완전 회피)
    Route::prefix('admin-panel')->name('web.complaints.')->group(function () {
        Route::get('/issues', [App\Http\Controllers\Web\ComplaintController::class, 'index'])->name('index');
        Route::get('/issues/create', [App\Http\Controllers\Web\ComplaintController::class, 'create'])->name('create');
        Route::post('/issues', [App\Http\Controllers\Web\ComplaintController::class, 'store'])->name('store');
        Route::get('/issues/{complaint}', [App\Http\Controllers\Web\ComplaintController::class, 'show'])->name('show');
        Route::get('/issues/{complaint}/edit', [App\Http\Controllers\Web\ComplaintController::class, 'edit'])->name('edit');
        Route::put('/issues/{complaint}', [App\Http\Controllers\Web\ComplaintController::class, 'update'])->name('update');
        Route::delete('/issues/{complaint}', [App\Http\Controllers\Web\ComplaintController::class, 'destroy'])->name('destroy');
        
        // 민원 추가 기능
        Route::post('/issues/{complaint}/comments', [App\Http\Controllers\Web\ComplaintController::class, 'storeComment'])->name('comments.store');
        Route::post('/issues/{complaint}/attachments', [App\Http\Controllers\Web\ComplaintController::class, 'uploadAttachment'])->name('attachments.upload');
        Route::get('/issues/{complaint}/attachments/{attachment}/download', [App\Http\Controllers\Web\ComplaintController::class, 'downloadAttachment'])->name('download-attachment');
        Route::delete('/issues/{complaint}/attachments/{attachment}', [App\Http\Controllers\Web\ComplaintController::class, 'deleteAttachment'])->name('delete-attachment');
        Route::put('/issues/{complaint}/status', [App\Http\Controllers\Web\ComplaintController::class, 'updateStatus'])->name('update-status');
        Route::put('/issues/{complaint}/assign', [App\Http\Controllers\Web\ComplaintController::class, 'assignUser'])->name('assign-user');
        Route::put('/issues/{complaint}/priority', [App\Http\Controllers\Web\ComplaintController::class, 'updatePriority'])->name('update-priority');
        Route::get('/issues/{complaint}/assignable-users', [App\Http\Controllers\Web\ComplaintController::class, 'getAssignableUsers'])->name('assignable-users');
        Route::get('/issues/{complaint}/status-transitions', [App\Http\Controllers\Web\ComplaintController::class, 'getStatusTransitions'])->name('status-transitions');
    });
    
    // 알림 관리
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/clear-read', [App\Http\Controllers\NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    
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
Route::prefix('api')->middleware('auth')->name('api.')->group(function () {
    // 민원 API
    Route::get('/complaints', [App\Http\Controllers\Api\ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/my-complaints', [App\Http\Controllers\Api\ComplaintController::class, 'myComplaints'])->name('complaints.my-complaints');
    Route::get('/complaints/assigned-to-me', [App\Http\Controllers\Api\ComplaintController::class, 'assignedToMe'])->name('complaints.assigned-to-me');
    Route::get('/complaints/statistics', [App\Http\Controllers\Api\ComplaintController::class, 'statistics'])->name('complaints.statistics');
    Route::get('/complaints/export-statistics', [App\Http\Controllers\Api\ComplaintController::class, 'exportStatistics'])->name('complaints.export-statistics');
    Route::get('/complaints/download-export/{file}', [App\Http\Controllers\Api\ComplaintController::class, 'downloadExport'])->name('complaints.download-export');
    Route::get('/complaints/status-options', [App\Http\Controllers\Api\ComplaintController::class, 'getStatusOptions'])->name('complaints.status-options');
    Route::get('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints', [App\Http\Controllers\Api\ComplaintController::class, 'store'])->name('complaints.store');
    Route::put('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'update'])->name('complaints.update');
    Route::delete('/complaints/{complaint}', [App\Http\Controllers\Api\ComplaintController::class, 'destroy'])->name('complaints.destroy');
    
    // 민원 상태 및 할당 관리
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Api\ComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Api\ComplaintController::class, 'assign'])->name('complaints.assign');
    Route::get('/complaints/{complaint}/assignable-users', [App\Http\Controllers\Api\ComplaintController::class, 'getAssignableUsers'])->name('complaints.assignable-users');
    Route::get('/complaints/{complaint}/status-transitions', [App\Http\Controllers\Api\ComplaintController::class, 'getStatusTransitions'])->name('complaints.status-transitions');
    
    // 민원 첨부파일
    Route::post('/complaints/{complaint}/attachments', [App\Http\Controllers\Api\ComplaintController::class, 'uploadAttachments'])->name('complaints.upload-attachments');
    Route::delete('/complaints/{complaint}/attachments', [App\Http\Controllers\Api\ComplaintController::class, 'deleteAttachments'])->name('complaints.delete-attachments');
    Route::get('/complaints/{complaint}/attachments/{attachment}/download', [App\Http\Controllers\Api\ComplaintController::class, 'downloadAttachment'])->name('complaints.download-attachment');
    
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
    
    // 알림 API
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'unread']);
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
});

// 외부 API 라우트 (토큰 기반 인증)
Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    // 민원 API
    Route::apiResource('complaints', App\Http\Controllers\Api\ComplaintController::class);
    Route::get('/complaints/my-complaints', [App\Http\Controllers\Api\ComplaintController::class, 'myComplaints']);
    Route::get('/complaints/assigned-to-me', [App\Http\Controllers\Api\ComplaintController::class, 'assignedToMe']);
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Api\ComplaintController::class, 'updateStatus']);
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Api\ComplaintController::class, 'assign']);
    Route::get('/complaints/{complaint}/assignable-users', [App\Http\Controllers\Api\ComplaintController::class, 'getAssignableUsers']);
    Route::get('/complaints/statistics', [App\Http\Controllers\Api\ComplaintController::class, 'statistics']);
    Route::get('/complaints/export-statistics', [App\Http\Controllers\Api\ComplaintController::class, 'exportStatistics']);
    
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
