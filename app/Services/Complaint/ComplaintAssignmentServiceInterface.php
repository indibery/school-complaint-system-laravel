<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;

interface ComplaintAssignmentServiceInterface
{
    /**
     * 민원 할당
     */
    public function assign(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $data = []
    ): Complaint;

    /**
     * 자동 할당
     */
    public function autoAssign(Complaint $complaint): ?Complaint;

    /**
     * 할당 권한 확인
     */
    public function canAssign(Complaint $complaint, User $user): bool;

    /**
     * 할당 가능한 사용자 목록
     */
    public function getAssignableUsers(Complaint $complaint): array;

    /**
     * 할당 이력 저장
     */
    public function logAssignmentHistory(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $metadata = []
    ): void;

    /**
     * 할당 해제
     */
    public function unassign(Complaint $complaint, User $user): Complaint;

    /**
     * 재할당
     */
    public function reassign(
        Complaint $complaint,
        User $newAssignee,
        User $reassignedBy,
        array $data = []
    ): Complaint;

    /**
     * 자동 할당 규칙 적용
     */
    public function applyAutoAssignmentRules(Complaint $complaint): ?User;

    /**
     * 할당 알림 발송
     */
    public function sendAssignmentNotification(
        Complaint $complaint,
        User $assignee,
        array $data = []
    ): void;
}
