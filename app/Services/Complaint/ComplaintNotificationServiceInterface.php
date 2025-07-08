<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;

interface ComplaintNotificationServiceInterface
{
    /**
     * 민원 생성 알림
     */
    public function notifyComplaintCreated(Complaint $complaint): void;

    /**
     * 상태 변경 알림
     */
    public function notifyStatusChanged(
        Complaint $complaint,
        string $oldStatus,
        string $newStatus,
        User $changedBy,
        array $data = []
    ): void;

    /**
     * 할당 알림
     */
    public function notifyAssigned(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $data = []
    ): void;

    /**
     * 댓글 알림
     */
    public function notifyCommentAdded(
        Complaint $complaint,
        User $commenter,
        string $comment
    ): void;

    /**
     * 만료 임박 알림
     */
    public function notifyDueDateApproaching(Complaint $complaint): void;

    /**
     * 만료 알림
     */
    public function notifyOverdue(Complaint $complaint): void;

    /**
     * 알림 대상 사용자 결정
     */
    public function getNotificationRecipients(Complaint $complaint, string $type): array;

    /**
     * 알림 템플릿 생성
     */
    public function createNotificationTemplate(
        string $type,
        Complaint $complaint,
        array $data = []
    ): array;

    /**
     * 알림 전송
     */
    public function sendNotification(
        array $recipients,
        string $type,
        array $data
    ): void;

    /**
     * 이메일 알림 전송
     */
    public function sendEmailNotification(
        User $recipient,
        string $subject,
        string $message,
        array $data = []
    ): void;

    /**
     * 시스템 알림 생성
     */
    public function createSystemNotification(
        User $recipient,
        string $title,
        string $message,
        array $data = []
    ): void;

    /**
     * 알림 설정 확인
     */
    public function shouldSendNotification(User $user, string $type): bool;

    /**
     * 알림 이력 저장
     */
    public function logNotificationHistory(
        Complaint $complaint,
        array $recipients,
        string $type,
        array $data = []
    ): void;
}
