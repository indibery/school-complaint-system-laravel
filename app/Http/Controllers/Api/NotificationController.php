<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 사용자의 알림 목록 조회
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = $user->notifications();
        
        // 읽지 않은 알림만 필터링
        if ($request->has('unread') && $request->unread == 'true') {
            $query->whereNull('read_at');
        }
        
        $notifications = $query->latest()
            ->paginate($request->get('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }
    
    /**
     * 읽지 않은 알림 개수 조회
     */
    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()->unreadNotifications()->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    }
    
    /**
     * 특정 알림 조회
     */
    public function show($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }
    
    /**
     * 알림을 읽음으로 표시
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        return response()->json([
            'success' => true,
            'message' => '알림을 읽음으로 표시했습니다.',
            'data' => $notification
        ]);
    }
    
    /**
     * 모든 알림을 읽음으로 표시
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => '모든 알림을 읽음으로 표시했습니다.'
        ]);
    }
    
    /**
     * 알림 삭제
     */
    public function destroy($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => '알림이 삭제되었습니다.'
        ]);
    }
    
    /**
     * 읽은 알림 모두 삭제
     */
    public function clearRead(): JsonResponse
    {
        Auth::user()->notifications()
            ->whereNotNull('read_at')
            ->delete();
        
        return response()->json([
            'success' => true,
            'message' => '읽은 알림이 모두 삭제되었습니다.'
        ]);
    }
    
    /**
     * 알림 설정 조회
     */
    public function settings(): JsonResponse
    {
        $user = Auth::user();
        
        // 사용자의 알림 설정 (추후 별도 테이블로 관리 가능)
        $settings = [
            'email_notifications' => $user->email_notifications ?? true,
            'complaint_created' => true,
            'complaint_assigned' => true,
            'complaint_commented' => true,
            'complaint_status_changed' => true,
            'complaint_deadline_reminder' => true
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
    
    /**
     * 알림 설정 업데이트
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'complaint_created' => 'boolean',
            'complaint_assigned' => 'boolean',
            'complaint_commented' => 'boolean',
            'complaint_status_changed' => 'boolean',
            'complaint_deadline_reminder' => 'boolean'
        ]);
        
        $user = Auth::user();
        
        // 이메일 알림 설정 저장 (users 테이블에 email_notifications 컬럼 필요)
        if (isset($validated['email_notifications'])) {
            $user->email_notifications = $validated['email_notifications'];
            $user->save();
        }
        
        // 추후 별도의 notification_settings 테이블로 관리 가능
        
        return response()->json([
            'success' => true,
            'message' => '알림 설정이 업데이트되었습니다.',
            'data' => $validated
        ]);
    }
}
