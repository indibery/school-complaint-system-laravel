<?php

namespace App\Repositories;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    /**
     * 모든 민원 조회
     */
    public function all(): Collection;

    /**
     * ID로 민원 조회
     */
    public function find(int $id): ?Complaint;

    /**
     * 조건으로 민원 조회
     */
    public function findBy(array $criteria): ?Complaint;

    /**
     * 조건으로 민원 목록 조회
     */
    public function findAllBy(array $criteria): Collection;

    /**
     * 민원 생성
     */
    public function create(array $data): Complaint;

    /**
     * 민원 수정
     */
    public function update(Complaint $complaint, array $data): Complaint;

    /**
     * 민원 삭제
     */
    public function delete(Complaint $complaint): bool;

    /**
     * 필터링된 민원 목록 조회
     */
    public function getFilteredList(array $filters, User $user): LengthAwarePaginator;

    /**
     * 사용자별 민원 목록 조회
     */
    public function getUserComplaints(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * 할당된 민원 목록 조회
     */
    public function getAssignedComplaints(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * 검색 쿼리 빌더
     */
    public function getSearchQuery(array $filters): Builder;

    /**
     * 권한 기반 접근 제어 적용
     */
    public function applyAccessControl(Builder $query, User $user): Builder;

    /**
     * 필터 적용
     */
    public function applyFilters(Builder $query, array $filters): Builder;

    /**
     * 정렬 적용
     */
    public function applySorting(Builder $query, string $sortBy, string $sortOrder): Builder;

    /**
     * 관계 데이터 로드
     */
    public function withRelations(Builder $query, array $relations = []): Builder;

    /**
     * 민원 번호로 조회
     */
    public function findByComplaintNumber(string $complaintNumber): ?Complaint;

    /**
     * 상태별 민원 개수 조회
     */
    public function getCountByStatus(User $user): array;

    /**
     * 우선순위별 민원 개수 조회
     */
    public function getCountByPriority(User $user): array;

    /**
     * 기간별 민원 개수 조회
     */
    public function getCountByPeriod(User $user, string $period): array;

    /**
     * 만료된 민원 조회
     */
    public function getOverdueComplaints(User $user): Collection;

    /**
     * 최근 민원 조회
     */
    public function getRecentComplaints(User $user, int $limit = 10): Collection;

    /**
     * 대량 업데이트
     */
    public function bulkUpdate(array $ids, array $data): bool;

    /**
     * 통계 쿼리
     */
    public function getStatistics(User $user, array $filters = []): array;

    /**
     * 마지막 민원 번호 조회
     */
    public function getLastComplaintNumber(string $prefix): ?string;

    /**
     * 민원 조회수 증가
     */
    public function incrementViews(Complaint $complaint): void;

    /**
     * 중복 민원 검사
     */
    public function findDuplicates(array $criteria): Collection;

    /**
     * 아카이브된 민원 조회
     */
    public function getArchivedComplaints(User $user): LengthAwarePaginator;

    /**
     * 민원 복원
     */
    public function restore(int $id): bool;

    /**
     * 완전 삭제
     */
    public function forceDelete(int $id): bool;
}
