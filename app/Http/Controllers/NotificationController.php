<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * 알림 목록 페이지
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['read_status', 'type', 'date_from', 'date_to']);
        $notifications = $this->notificationService->getUserNotifications(
            $request->user(),
            $filters
        );

        $stats = $this->notificationService->getNotificationStats($request->user());

        return view('notifications.index', compact('notifications', 'stats', 'filters'));
    }

    /**
     * 읽지 않은 알림 목록 (API)
     */
    public function unread(Request $request): JsonResponse
    {
        $data = $this->notificationService->getRealTimeNotificationData($request->user());
        
        return response()->json($data);
    }

    /**
     * 알림을 읽음으로 표시
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $result = $this->notificationService->markAsRead($notification, $request->user());
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => '알림을 읽음으로 표시할 수 없습니다.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => '알림을 읽음으로 표시했습니다.'
        ]);
    }

    /**
     * 모든 알림을 읽음으로 표시
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());
        
        return response()->json([
            'success' => true,
            'message' => "{$count}개의 알림을 읽음으로 표시했습니다.",
            'count' => $count
        ]);
    }

    /**
     * 알림 삭제
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $result = $this->notificationService->deleteNotification($notification, $request->user());
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => '알림을 삭제할 수 없습니다.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => '알림이 삭제되었습니다.'
        ]);
    }

    /**
     * 읽은 알림 모두 삭제
     */
    public function clearRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->deleteReadNotifications($request->user());
        
        return response()->json([
            'success' => true,
            'message' => "{$count}개의 읽은 알림이 삭제되었습니다.",
            'count' => $count
        ]);
    }

    /**
     * 알림 설정 페이지
     */
    public function settings(Request $request): View
    {
        $user = $request->user();
        $settings = $user->notification_settings ?? [];

        return view('notifications.settings', compact('settings'));
    }

    /**
     * 알림 설정 업데이트
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'complaint_created' => 'boolean',
            'complaint_assigned' => 'boolean',
            'complaint_status_changed' => 'boolean',
            'complaint_comment_added' => 'boolean',
            'complaint_resolved' => 'boolean',
        ]);

        $user = $request->user();
        $user->notification_settings = $validated;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '알림 설정이 업데이트되었습니다.',
            'settings' => $validated
        ]);
    }

    /**
     * 알림 통계 (API)
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->notificationService->getNotificationStats($request->user());
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 실시간 알림 데이터 (WebSocket용)
     */
    public function realTimeData(Request $request): JsonResponse
    {
        $data = $this->notificationService->getRealTimeNotificationData($request->user());
        
        return response()->json($data);
    }
}
