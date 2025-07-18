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

// 헬스 체크 API (모바일 앱 자동 발견용)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'server' => request()->getHost(),
        'port' => request()->getPort(),
        'app_name' => config('app.name'),
        'app_version' => '1.0.0',
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
    ]);
});

// 테스트 API
Route::get('/test', [App\Http\Controllers\Api\TestController::class, 'index']);

// 간단한 테스트 로그인 (보안 체크 없음)
Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    try {
        // 사용자 찾기 또는 생성
        $email = 'parent@test.com';
        $password = 'password123';
        
        $user = \App\Models\User::firstOrCreate(
        ['email' => $email],
        [
        'name' => 'Test Parent',
        'password' => \Illuminate\Support\Facades\Hash::make($password),
        'role' => 'parent',
        'is_active' => true,
        ]
        );
        
        // 토큰 생성
        $token = $user->createToken('mobile-app')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => '로그인 성공',
            'token' => $token,
            'user' => $user,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => '로그인 실패',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// 인증 없이 접근 가능한 디버깅 API
Route::prefix('debug')->group(function () {
    Route::get('/public-test', function () {
        return response()->json([
            'message' => 'Public API 연결 성공!',
            'timestamp' => now(),
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    });
    
    // 개발용 초간단 민원 생성 (보안 체크 최소화)
    Route::post('/create-simple-complaint', function (\Illuminate\Http\Request $request) {
        try {
            // 가장 최근에 로그인한 사용자 찾기
            $user = \App\Models\User::where('email', 'parent@test.com')->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다. 먼저 로그인하세요.',
                ], 404);
            }
            
            // 필수 데이터 미리 조회
            $student = \App\Models\Student::first();
            $category = \App\Models\Category::first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student 데이터가 없습니다. 먼저 학생 데이터를 생성하세요.',
                ], 404);
            }
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category 데이터가 없습니다. 먼저 카테고리 데이터를 생성하세요.',
                ], 404);
            }

            // 간단한 더미 데이터로 민원 생성 (필수 필드 모두 포함)
            $complaint = \App\Models\Complaint::create([
                'title' => '개발용 테스트 민원 - ' . now()->format('H:i:s'),
                'content' => '개발 효율성을 위한 테스트 민원입니다.',
                'status' => 'submitted',
                'priority' => 'normal',
                'user_id' => $user->id,
                'student_id' => $student->id,  // 필수 필드 추가
                'category_id' => $category->id,  // 필수 필드 추가
                'is_public' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '개발용 민원이 생성되었습니다!',
                'complaint' => [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'user_id' => $complaint->user_id,
                    'status' => $complaint->status,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '개발용 민원 생성 실패',
                'error' => $e->getMessage(),
                'debug' => '개발 환경에서는 상세한 오류 정보를 표시합니다.',
            ], 500);
        }
    });
    
    Route::get('/check-db', function () {
        try {
            $userCount = \App\Models\User::count();
            $complaintCount = \App\Models\Complaint::count();
            
            // 최근 등록된 사용자들
            $recentUsers = \App\Models\User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']);
            
            // 최근 등록된 민원들
            $recentComplaints = \App\Models\Complaint::latest()->take(5)->get(['id', 'title', 'created_by', 'created_at']);
            
            return response()->json([
                'message' => 'DB 연결 성공',
                'data' => [
                    'users' => $userCount,
                    'complaints' => $complaintCount,
                    'recent_users' => $recentUsers,
                    'recent_complaints' => $recentComplaints,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'DB 연결 실패',
                'error' => $e->getMessage(),
            ], 500);
        }
    });
    
    // 테스트 민원 생성 (디버깅용)
    Route::post('/create-test-complaint', function (\Illuminate\Http\Request $request) {
        try {
            // 개발 환경에서는 더 관대한 토큰 처리
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => '인증 토큰이 필요합니다',
                    'debug' => '배운 인증 헤더가 없습니다',
                ], 401);
            }
            
            // Bearer 토큰에서 실제 토큰 추출
            $tokenValue = str_replace('Bearer ', '', $token);
            
            // 개발 환경에서는 더 상세한 토큰 디버깅
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenValue);
            if (!$personalAccessToken) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 토큰입니다',
                    'debug' => '토큰을 데이터베이스에서 찾을 수 없습니다',
                    'token_length' => strlen($tokenValue),
                    'token_prefix' => substr($tokenValue, 0, 10) . '...',
                ], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            // 더미 학생 생성 또는 찾기
            $student = \App\Models\Student::firstOrCreate(
                ['name' => 'Test Student'],
                [
                    'name' => 'Test Student',
                    'grade' => 1,
                    'class' => 1,
                    'student_number' => '001',
                    'parent_id' => $user->id,  // 현재 로그인한 사용자를 부모로 설정
                    'is_active' => true,
                ]
            );
            
            // 더미 카테고리 생성 또는 찾기
            $category = \App\Models\Category::firstOrCreate(
                ['name' => '기타'],
                [
                    'name' => '기타',
                    'description' => '기타 민원',
                    'is_active' => true,
                ]
            );
            
            // 테스트 민원 생성
            $complaint = \App\Models\Complaint::create([
                'title' => '테스트 민원 - ' . date('Y-m-d H:i:s'),
                'content' => '디버깅용 테스트 민원입니다.',
                'status' => 'submitted',
                'priority' => 'normal',
                'user_id' => $user->id,
                'student_id' => $student->id,
                'category_id' => $category->id,
                'is_public' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '테스트 민원이 생성되었습니다',
                'complaint' => [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'user_id' => $complaint->user_id,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '민원 생성 실패',
                'error' => $e->getMessage(),
            ], 500);
        }
    });
    
    // 간단한 테스트 로그인 (디버깅용)
    Route::post('/simple-login', function (\Illuminate\Http\Request $request) {
        try {
            $email = $request->input('email', 'parent@test.com');
            $password = $request->input('password', 'password123');
            
            // 사용자 찾기
            $user = \App\Models\User::where('email', $email)->first();
            
            if (!$user) {
                // 테스트 사용자 생성
                $user = \App\Models\User::create([
                    'name' => 'Test Parent',
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                    'role' => 'parent',
                    'is_active' => true,
                ]);
            }
            
            // 비밀번호 확인
            if (!\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
                return response()->json([
                    'message' => '비밀번호가 올바르지 않습니다',
                    'success' => false,
                ], 401);
            }
            
            // 토큰 생성
            $token = $user->createToken('mobile-app')->plainTextToken;
            
            return response()->json([
                'message' => '로그인 성공',
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => '로그인 실패',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    });
});

// 기본 API 상태 확인
Route::get('/', function () {
    return response()->json([
        'message' => 'School Complaint System API',
        'version' => '1.0',
        'status' => 'running',
        'timestamp' => now(),
    ]);
});

// 인증 관련 API (토큰 불필요)
Route::prefix('v1')->group(function () {
    // 인증 API
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    
    // 공개 카테고리 목록
    Route::get('/categories/public', [App\Http\Controllers\Api\CategoryController::class, 'publicIndex']);
    
    // 간단한 민원 등록 (디버깅용, CSRF 제외)
    Route::post('/create-simple-complaint-no-csrf', function (\Illuminate\Http\Request $request) {
        try {
            // 기본 데이터 검증
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'nullable|integer|exists:categories,id',
            ]);
            
            // 기본 사용자 찾기 또는 생성
            $user = \App\Models\User::firstOrCreate(
                ['email' => 'parent@test.com'],
                [
                    'name' => 'Test Parent',
                    'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                    'role' => 'parent',
                    'is_active' => true,
                ]
            );
            
            // 기본 학생 찾기 또는 생성
            $student = \App\Models\Student::firstOrCreate(
                ['parent_id' => $user->id],
                [
                    'name' => 'Test Student',
                    'grade' => 1,
                    'class' => 1,
                    'student_number' => '001',
                    'parent_id' => $user->id,
                    'is_active' => true,
                ]
            );
            
            // 기본 카테고리 찾기
            $categoryId = $request->input('category_id');
            if (!$categoryId) {
                $category = \App\Models\Category::where('name', '기타')->first();
                $categoryId = $category ? $category->id : 1;
            }
            
            // 민원 생성
            $complaint = \App\Models\Complaint::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'status' => 'submitted',
                'priority' => 'normal',
                'user_id' => $user->id,
                'student_id' => $student->id,
                'category_id' => $categoryId,
                'is_public' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '민원이 성공적으로 등록되었습니다.',
                'complaint' => [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '민원 등록 중 오류가 발생했습니다.',
                'error' => $e->getMessage(),
            ], 500);
        }
    });
    
    // 간단한 민원 목록 조회
    Route::get('/complaints/public', function () {
        try {
            $complaints = \App\Models\Complaint::with(['user', 'category'])
                ->where('is_public', true)
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($complaint) {
                    return [
                        'id' => $complaint->id,
                        'title' => $complaint->title,
                        'status' => $complaint->status,
                        'priority' => $complaint->priority,
                        'user_name' => $complaint->user->name ?? 'Unknown',
                        'category_name' => $complaint->category->name ?? 'Unknown',
                        'created_at' => $complaint->created_at,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'message' => '공개 민원 목록을 조회했습니다.',
                'data' => $complaints,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '민원 목록 조회 중 오류가 발생했습니다.',
                'error' => $e->getMessage(),
            ], 500);
        }
    });
    
    // 민원 등록 API (CSRF 제외) - 인증 필요
    Route::post('/complaints', [App\Http\Controllers\Api\ComplaintController::class, 'store'])->middleware('auth:sanctum');
    
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
    
    // 민원 API - 특별한 라우트는 리소스 라우트보다 먼저 정의
    Route::get('/complaints/my-complaints', [App\Http\Controllers\Api\ComplaintController::class, 'myComplaints']);
    Route::get('/complaints/assigned-to-me', [App\Http\Controllers\Api\ComplaintController::class, 'assignedToMe']);
    Route::get('/complaints/statistics', [App\Http\Controllers\Api\ComplaintController::class, 'statistics']);
    Route::get('/complaints/export-statistics', [App\Http\Controllers\Api\ComplaintController::class, 'exportStatistics']);
    Route::get('/complaints/{complaint}/assignable-users', [App\Http\Controllers\Api\ComplaintController::class, 'getAssignableUsers']);
    Route::put('/complaints/{complaint}/status', [App\Http\Controllers\Api\ComplaintController::class, 'updateStatus']);
    Route::put('/complaints/{complaint}/assign', [App\Http\Controllers\Api\ComplaintController::class, 'assign']);
    
    // 민원 리소스 라우트 (위의 특별 라우트 이후에 정의) - store 메소드 제외
    Route::apiResource('complaints', App\Http\Controllers\Api\ComplaintController::class)->except(['store']);
    
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
    
    // 디버깅 라우트 (개발 중에만 사용)
    if (config('app.debug')) {
        require __DIR__ . '/debug.php';
    }
});

// 기존 Sanctum 사용자 라우트
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
