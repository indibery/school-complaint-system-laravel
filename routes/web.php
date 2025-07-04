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
