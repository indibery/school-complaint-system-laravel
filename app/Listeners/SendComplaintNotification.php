<?php

namespace App\Listeners;

use App\Events\ComplaintCreated;
use App\Events\ComplaintStatusChanged;
use App\Events\ComplaintAssigned;
use App\Events\ComplaintCommentAdded;
use App\Events\ComplaintResolved;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendComplaintNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            match (get_class($event)) {
                ComplaintCreated::class => $this->handleComplaintCreated($event),
                ComplaintStatusChanged::class => $this->handleComplaintStatusChanged($event),
                ComplaintAssigned::class => $this->handleComplaintAssigned($event),
                ComplaintCommentAdded::class => $this->handleComplaintCommentAdded($event),
                ComplaintResolved::class => $this->handleComplaintResolved($event),
                default => Log::warning('Unknown event type: ' . get_class($event))
            };
        } catch (\Exception $e) {
            Log::error('알림 처리 중 오류 발생', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 민원 생성 알림 처리
     */
    private function handleComplaintCreated(ComplaintCreated $event): void
    {
        $complaint = $event->complaint;
        
        // 관리자들에게 알림
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $this->createNotification($admin, [
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
                $this->createNotification($staff, [
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

        // 이메일 알림 (설정에 따라)
        if (config('app.notifications.email_enabled', false)) {
            $this->sendEmailNotification($complaint->complainant, 'complaint_created', $complaint);
        }
    }

    /**
     * 민원 상태 변경 알림 처리
     */
    private function handleComplaintStatusChanged(ComplaintStatusChanged $event): void
    {
        $complaint = $event->complaint;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;
        $changedBy = $event->changedBy;

        // 민원인에게 알림
        $this->createNotification($complaint->complainant, [
            'title' => '민원 상태가 변경되었습니다',
            'message' => "민원 '{$complaint->title}'의 상태가 '{$this->getStatusLabel($oldStatus)}'에서 '{$this->getStatusLabel($newStatus)}'로 변경되었습니다.",
            'type' => 'complaint_status_changed',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $changedBy?->name ?? '시스템',
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);

        // 담당자에게 알림 (변경자가 아닌 경우)
        if ($complaint->assigned_to && $complaint->assigned_to !== $changedBy?->id) {
            $this->createNotification($complaint->assignedTo, [
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

        // 특정 상태에 따른 추가 알림
        if ($newStatus === 'resolved') {
            $this->sendResolutionNotification($complaint);
        }
    }

    /**
     * 민원 할당 알림 처리
     */
    private function handleComplaintAssigned(ComplaintAssigned $event): void
    {
        $complaint = $event->complaint;
        $assignedTo = $event->assignedTo;
        $assignedBy = $event->assignedBy;

        // 할당받은 사용자에게 알림
        $this->createNotification($assignedTo, [
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
        $this->createNotification($complaint->complainant, [
            'title' => '민원 담당자가 지정되었습니다',
            'message' => "민원 '{$complaint->title}'의 담당자가 지정되었습니다.",
            'type' => 'complaint_assigned',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'assigned_to' => $assignedTo->name,
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);
    }

    /**
     * 민원 댓글 추가 알림 처리
     */
    private function handleComplaintCommentAdded(ComplaintCommentAdded $event): void
    {
        $complaint = $event->complaint;
        $comment = $event->comment;
        $author = $event->author;

        // 민원인에게 알림 (댓글 작성자가 아닌 경우)
        if ($complaint->complainant_id !== $author->id) {
            $this->createNotification($complaint->complainant, [
                'title' => '민원에 새로운 댓글이 등록되었습니다',
                'message' => "민원 '{$complaint->title}'에 새로운 댓글이 등록되었습니다.",
                'type' => 'complaint_comment_added',
                'data' => [
                    'complaint_id' => $complaint->id,
                    'complaint_title' => $complaint->title,
                    'comment_author' => $author->name,
                ],
                'action_url' => route('complaints.show', $complaint->id)
            ]);
        }

        // 담당자에게 알림 (댓글 작성자가 아닌 경우)
        if ($complaint->assigned_to && $complaint->assigned_to !== $author->id) {
            $this->createNotification($complaint->assignedTo, [
                'title' => '할당된 민원에 새로운 댓글이 등록되었습니다',
                'message' => "민원 '{$complaint->title}'에 새로운 댓글이 등록되었습니다.",
                'type' => 'complaint_comment_added',
                'data' => [
                    'complaint_id' => $complaint->id,
                    'complaint_title' => $complaint->title,
                    'comment_author' => $author->name,
                ],
                'action_url' => route('complaints.show', $complaint->id)
            ]);
        }
    }

    /**
     * 민원 해결 알림 처리
     */
    private function handleComplaintResolved(ComplaintResolved $event): void
    {
        $complaint = $event->complaint;
        $resolvedBy = $event->resolvedBy;
        $resolutionNote = $event->resolutionNote;

        // 민원인에게 알림
        $this->createNotification($complaint->complainant, [
            'title' => '민원이 해결되었습니다',
            'message' => "민원 '{$complaint->title}'이 해결되었습니다.",
            'type' => 'complaint_resolved',
            'data' => [
                'complaint_id' => $complaint->id,
                'complaint_title' => $complaint->title,
                'resolved_by' => $resolvedBy->name,
                'resolution_note' => $resolutionNote,
            ],
            'action_url' => route('complaints.show', $complaint->id)
        ]);

        // 만족도 조사 알림 (24시간 후)
        $this->scheduleSatisfactionSurvey($complaint);
    }

    /**
     * 알림 생성
     */
    private function createNotification(User $user, array $data): void
    {
        Notification::create([
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
     * 이메일 알림 발송
     */
    private function sendEmailNotification(User $user, string $type, $data): void
    {
        try {
            // 이메일 발송 로직 구현
            // Mail::to($user->email)->send(new ComplaintNotificationMail($type, $data));
        } catch (\Exception $e) {
            Log::error('이메일 알림 발송 실패', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 해결 알림 발송
     */
    private function sendResolutionNotification($complaint): void
    {
        // 해결 완료에 대한 추가 알림 로직
    }

    /**
     * 만족도 조사 예약
     */
    private function scheduleSatisfactionSurvey($complaint): void
    {
        // 만족도 조사 예약 로직
    }

    /**
     * 상태 라벨 가져오기
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => '대기',
            'assigned' => '할당됨',
            'in_progress' => '처리중',
            'resolved' => '해결됨',
            'closed' => '종료',
            'cancelled' => '취소됨',
        ];

        return $labels[$status] ?? $status;
    }
}
