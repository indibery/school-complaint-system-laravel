<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;

interface ComplaintStatusServiceInterface
{
    /**
     * 상태 변경
     */
    public function updateStatus(
        Complaint $complaint,
        string $newStatus,
        User $user,
        array $data = []
    ): Complaint;

    /**
     * 상태 변경 권한 확인
     */
    public function canUpdateStatus(Complaint $complaint, string $newStatus, User $user): bool;

    /**
     * 유효한 상태 전환인지 확인
     */
    public function isValidStatusTransition(string $currentStatus, string $newStatus): bool;

    /**
     * 상태별 후속 처리
     */
    public function handleStatusChange(
        Complaint $complaint,
        string $oldStatus,
        string $newStatus,
        array $data = []
    ): void;

    /**
     * 상태 이력 저장
     */
    public function logStatusHistory(
        Complaint $complaint,
        string $action,
        string $description,
        User $user,
        array $metadata = []
    ): void;

    /**
     * 상태 표시명 가져오기
     */
    public function getStatusLabel(string $status): string;

    /**
     * 사용 가능한 상태 목록
     */
    public function getAvailableStatuses(): array;

    /**
     * 상태별 색상 클래스
     */
    public function getStatusColor(string $status): string;

    /**
     * 상태 변경 시 필요한 필드들
     */
    public function getRequiredFieldsForStatus(string $status): array;
}
