<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * 사용자의 알림 목록 조회
     */
    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // 읽음/읽지 않음 필터
        if (isset($filters['read_status'])) {
            if ($filters['read_status'] === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filters['read_status'] === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        // 타입 필터
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // 날짜 필터
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * 읽지 않은 알림 목록 조회
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 읽지 않은 알림 개수 조회
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * 알림을 읽음으로 표시
     */
    public function markAsRead(Notification $notification, User $user): bool
    {
        if ($notification->user_id !== $user->id) {
            return false;
        }

        if ($notification->read_at) {
            return true; // 이미 읽음
        }

        return $notification->update([
            'read_at' => now()
        ]);
    }

    /**
     * 모든 알림을 읽음으로 표시
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now()
            ]);
    }

    /**
     * 알림 삭제
     */
    public function deleteNotification(Notification $notification, User $user): bool
    {
        if ($notification->user_id !== $user->id) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * 읽은 알림 모두 삭제
     */
    public function deleteReadNotifications(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * 오래된 알림 정리 (30일 이상)
     */
    public function cleanupOldNotifications(): int
    {
        $deletedCount = Notification::where('created_at', '<', now()->subDays(30))
            ->whereNotNull('read_at')
            ->delete();

        Log::info('오래된 알림 정리 완료', ['deleted_count' => $deletedCount]);

        return $deletedCount;
    }

    /**
     * 알림 생성
     */
    public function createNotification(User $user, array $data): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'data' => json_encode($data['data'] ?? []),
            'action_url' => $data['action_url'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * 벌크 알림 생성
     */
    public function createBulkNotifications(array $users, array $data): int
    {
        $notifications = [];
        $now = now();

        foreach ($users as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'],
                'data' => json_encode($data['data'] ?? []),
                'action_url' => $data['action_url'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 배치 삽입
        Notification::insert($notifications);

        return count($notifications);
    }

    /**
     * 알림 통계 조회
     */
    public function getNotificationStats(User $user): array
    {
        $total = Notification::where('user_id', $user->id)->count();
        $unread = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        $read = $total - $unread;

        $typeStats = Notification::where('user_id', $user->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 실시간 알림 데이터 조회
     */
    public function getRealTimeNotificationData(User $user): array
    {
        $notifications = $this->getUnreadNotifications($user);
        $count = $this->getUnreadCount($user);

        return [
            'count' => $count,
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'action_url' => $notification->action_url,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            })
        ];
    }
}
