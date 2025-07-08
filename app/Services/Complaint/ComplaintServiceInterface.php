<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

interface ComplaintServiceInterface
{
    /**
     * 민원 목록 조회
     */
    public function getList(array $filters, User $user): LengthAwarePaginator;

    /**
     * 민원 생성
     */
    public function create(array $data, User $user): Complaint;

    /**
     * 민원 조회
     */
    public function find(int $id, User $user): ?Complaint;

    /**
     * 민원 수정
     */
    public function update(Complaint $complaint, array $data, User $user): Complaint;

    /**
     * 민원 삭제
     */
    public function delete(Complaint $complaint, User $user): bool;

    /**
     * 내 민원 목록 조회
     */
    public function getMyComplaints(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * 내 할당 민원 목록 조회
     */
    public function getAssignedComplaints(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * 민원 조회 권한 확인
     */
    public function canView(Complaint $complaint, User $user): bool;

    /**
     * 민원 수정 권한 확인
     */
    public function canUpdate(Complaint $complaint, User $user): bool;

    /**
     * 민원 삭제 권한 확인
     */
    public function canDelete(Complaint $complaint, User $user): bool;

    /**
     * 민원 번호 생성
     */
    public function generateComplaintNumber(): string;

    /**
     * 민원 조회수 증가
     */
    public function incrementViews(Complaint $complaint): void;
}
