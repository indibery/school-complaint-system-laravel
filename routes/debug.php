<?php

use App\Models\User;
use App\Models\Complaint;
use Illuminate\Support\Facades\Route;

// 디버깅용 라우트 (테스트 중에만 사용)
Route::middleware('auth:sanctum')->prefix('debug')->group(function () {
    
    // 현재 사용자의 민원 목록 확인
    Route::get('/my-complaints', function () {
        $user = auth()->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'complaints' => Complaint::where('created_by', $user->id)
                ->with(['category', 'department'])
                ->latest()
                ->get()
                ->map(function ($complaint) {
                    return [
                        'id' => $complaint->id,
                        'title' => $complaint->title,
                        'status' => $complaint->status,
                        'created_by' => $complaint->created_by,
                        'created_at' => $complaint->created_at,
                        'category' => $complaint->category?->name,
                        'department' => $complaint->department?->name,
                    ];
                }),
            'total_complaints' => Complaint::count(),
            'user_complaints_count' => Complaint::where('created_by', $user->id)->count(),
        ]);
    });

    // 최근 민원 5개 확인
    Route::get('/recent-complaints', function () {
        return response()->json([
            'recent_complaints' => Complaint::with(['category', 'department', 'complainant'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($complaint) {
                    return [
                        'id' => $complaint->id,
                        'title' => $complaint->title,
                        'status' => $complaint->status,
                        'created_by' => $complaint->created_by,
                        'complainant' => $complaint->complainant?->name,
                        'created_at' => $complaint->created_at,
                    ];
                }),
        ]);
    });

    // API 호출 테스트
    Route::get('/test-api', function () {
        $user = auth()->user();
        
        return response()->json([
            'message' => 'API 연결 성공',
            'timestamp' => now(),
            'user' => $user->name,
            'user_id' => $user->id,
        ]);
    });
});
