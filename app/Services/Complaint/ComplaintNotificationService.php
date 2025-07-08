<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ComplaintNotificationService implements ComplaintNotificationServiceInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * 민원 생성 알림
     */
    public function notifyComplaintCreated(Complaint $complaint): void
    {
        // 관리자들에게 알림
        $admins = User::role('admin')->get();
        
        foreach ($admins as $admin) {
            $this->notificationService->createNotification($admin, [
                'title' => '새로운 민원이 접수되었습니다',
                'message' => "민원 제목: {$complaint->title}",
                'type' => 'complaint_created',
                'data' => [
                    'complaint_id' => $complaint->id,
                    'complaint_title' => $complaint->title,
                    'complainant' => $complaint->complainant->name,
                    'category' => $complaint->category->name,
                    'priority' => $complaint->priority,
                ],
                'action_url' => route('complaints.show', $complaint->id)
            ]);
        }

        // 부서 담당자들에게 알림
        if ($complaint->department_id) {
            $departmentStaff = User::where('department_id', $complaint->department_id)
                ->role(['staff', 'department_head'])
                ->get();
            
            foreach ($departmentStaff as $staff) {
                $this->notificationService->createNotification($staff, [
                    'title' => '새로운 민원이 접수되었습니다',
                    'message' => "부서 관련 민원: {$complaint->title}",
                    'type' => 'complaint_created',
                    'data' => [
                        'complaint_id' => $complaint->id,
                        'complaint_title' => $complaint->title,
                        'department' => $complaint->department->name ?? '',
                    ],
                    'action_url' => route('complaints.show', $complaint->id)
                ]);
            }
        }
    }

    /**
     * 민원 상태 변경 알림
     */
    public function notifyStatusChanged(
        Complaint $complaint,
        string $oldStatus,
        string $newStatus,
        User $changedBy,
        array $data = []
    ): void {
        // 민원인에게 알림
        $this->notificationService->createNotification($complaint->complainant, [
            'title' => '민원 상태가 변경되었습니다',
            'message' => "민원 '{$complaint->title}'의 상태가 '{$oldStatus}'에서 '{$newStatus}'로 변경되었습니다.",
            'type' => 'complaint_status_changed',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $changedBy->name,
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);

        // 담당자에게 알림 (변경자가 아닌 경우)
        if ($complaint->assigned_to && $complaint->assigned_to !== $changedBy->id) {
            $this->notificationService->createNotification($complaint->assignedTo, [
                'title' => '할당된 민원의 상태가 변경되었습니다',
                'message' => "민원 '{$complaint->title}'의 상태가 변경되었습니다.",
                'type' => 'complaint_status_changed',
                'data' => [
                    'complaint_id' => $complaint->id,
                    'complaint_title' => $complaint->title,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
                'action_url' => route('complaints.show', $complaint->id)
            ]);
        }
    }

    /**
     * 민원 할당 알림
     */
    public function notifyAssigned(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $data = []
    ): void {
        // 할당받은 사용자에게 알림
        $this->notificationService->createNotification($assignee, [
            'title' => '새로운 민원이 할당되었습니다',
            'message' => "민원 '{$complaint->title}'이 할당되었습니다.",
            'type' => 'complaint_assigned',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'assigned_by' => $assignedBy->name,
                'priority' => $complaint->priority,
                'category' => $complaint->category->name,
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);

        // 민원인에게 알림
        $this->notificationService->createNotification($complaint->complainant, [
            'title' => '민원 담당자가 지정되었습니다',
            'message' => "민원 '{$complaint->title}'의 담당자가 지정되었습니다.",
            'type' => 'complaint_assigned',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'assigned_to' => $assignee->name,
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);
    }

    /**
     * 이메일 알림 발송
     */
    public function sendEmailNotification(User $user, string $type, array $data): void
    {
        try {
            // 이메일 알림 발송 로직
            Log::info('이메일 알림 발송', [
                'user_id' => $user->id,
                'type' => $type,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('이메일 알림 발송 실패', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }
}
