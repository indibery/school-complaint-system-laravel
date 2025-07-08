<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// 테스트 API
Route::get('/test', [App\Http\Controllers\Api\TestController::class, 'index']);

// 인증 관련 API (토큰 불필요)
Route::prefix('v1')->group(function () {
    // 인증 API
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    
    // 공개 카테고리 목록
    Route::get('/categories/public', [App\Http\Controllers\Api\CategoryController::class, 'publicIndex']);
    
    // 테스트 API
    Route::get('/test', [App\Http\Controllers\Api\TestController::class, 'index']);
});

// 인증이 필요한 API (Sanctum 토큰 필요)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // 인증 관련
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::put('/profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::post('/change-password', [App\Http\Controllers\Api\AuthController::class, 'changePassword']);
    Route::post('/refresh-token', [App\Http\Controllers\Api\AuthController::class, 'refreshToken']);
    Route::get('/tokens', [App\Http\Controllers\Api\AuthController::class, 'tokens']);
    Route::delete('/tokens/{tokenId}', [App\Http\Controllers\Api\AuthController::class, 'revokeToken']);
    Route::post('/deactivate', [App\Http\Controllers\Api\AuthController::class, 'deactivate']);
    
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
    Route::get('/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'getStats']);
    
    // 부서 API
    Route::get('/departments', [App\Http\Controllers\Api\DepartmentController::class, 'index']);
    
    // 알림 API
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'unread']);
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
});

// 기존 Sanctum 사용자 라우트
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
