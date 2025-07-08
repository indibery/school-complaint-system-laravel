<?php

namespace App\Repositories;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\Traits\HasAccessControl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    use HasAccessControl;

    protected Complaint $model;
    protected const DEFAULT_PER_PAGE = 20;
    protected const MAX_PER_PAGE = 100;

    public function __construct(Complaint $model)
    {
        $this->model = $model;
    }

    /**
     * 모든 민원 조회
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * ID로 민원 조회
     */
    public function find(int $id): ?Complaint
    {
        return $this->model->find($id);
    }

    /**
     * 조건으로 민원 조회
     */
    public function findBy(array $criteria): ?Complaint
    {
        return $this->buildQuery($criteria)->first();
    }

    /**
     * 조건으로 민원 목록 조회
     */
    public function findAllBy(array $criteria): Collection
    {
        return $this->buildQuery($criteria)->get();
    }

    /**
     * 민원 생성
     */
    public function create(array $data): Complaint
    {
        return $this->model->create($data);
    }

    /**
     * 민원 수정
     */
    public function update(Complaint $complaint, array $data): Complaint
    {
        $complaint->update($data);
        return $complaint->fresh();
    }

    /**
     * 민원 삭제
     */
    public function delete(Complaint $complaint): bool
    {
        return $complaint->delete();
    }

    /**
     * 쿼리 빌더 생성
     */
    protected function buildQuery(array $criteria): Builder
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query;
    }

    /**
     * 새로운 쿼리 빌더 인스턴스 반환
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 모델 인스턴스 반환
     */
    protected function getModel(): Complaint
    {
        return $this->model;
    }
    public function getFilteredList(array $filters, User $user): LengthAwarePaginator
    {
        $query = $this->getSearchQuery($filters);
        
        // 권한 기반 접근 제어 적용
        $query = $this->applyAccessControl($query, $user);
        
        // 필터 적용
        $query = $this->applyFilters($query, $filters);
        
        // 관계 데이터 로드
        $query = $this->withRelations($query, [
            'category', 'department', 'complainant', 'assignedTo', 'student'
        ]);
        
        // 정렬 적용
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query = $this->applySorting($query, $sortBy, $sortOrder);
        
        // 페이지네이션
        $perPage = min($filters['per_page'] ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        
        return $query->paginate($perPage);
    }

    /**
     * 사용자별 민원 목록 조회
     */
    public function getUserComplaints(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['category', 'department', 'assignedTo'])
            ->where('created_by', $user->id);
            
        // 기본 필터 적용
        $query = $this->applyBasicFilters($query, $filters);
        
        // 정렬
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query = $this->applySorting($query, $sortBy, $sortOrder);
        
        $perPage = min($filters['per_page'] ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        
        return $query->paginate($perPage);
    }

    /**
     * 할당된 민원 목록 조회
     */
    public function getAssignedComplaints(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(['category', 'department', 'complainant', 'student'])
            ->where('assigned_to', $user->id);
            
        // 기본 필터 적용
        $query = $this->applyBasicFilters($query, $filters);
        
        // 정렬
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query = $this->applySorting($query, $sortBy, $sortOrder);
        
        $perPage = min($filters['per_page'] ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        
        return $query->paginate($perPage);
    }

    /**
     * 검색 쿼리 빌더
     */
    public function getSearchQuery(array $filters): Builder
    {
        $query = $this->newQuery();
        
        // 기본 검색어 처리
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('complaint_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * 필터 적용
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // 상태 필터
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 우선순위 필터
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // 카테고리 필터
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // 부서 필터
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // 담당자 필터
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // 작성자 필터
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // 공개 여부 필터
        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        // 익명 여부 필터
        if (isset($filters['is_anonymous'])) {
            $query->where('is_anonymous', $filters['is_anonymous']);
        }

        // 첨부파일 여부 필터
        if (isset($filters['has_attachments'])) {
            if ($filters['has_attachments']) {
                $query->whereHas('attachments');
            } else {
                $query->whereDoesntHave('attachments');
            }
        }

        // 기한 초과 필터
        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('due_date', '<', now())
                  ->whereNotIn('status', ['resolved', 'closed', 'cancelled']);
        }

        // 태그 필터
        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // 위치 필터
        if (!empty($filters['location'])) {
            $query->where('location', 'like', "%{$filters['location']}%");
        }

        // 사건 날짜 필터
        if (!empty($filters['incident_date_from'])) {
            $query->whereDate('incident_date', '>=', $filters['incident_date_from']);
        }

        if (!empty($filters['incident_date_to'])) {
            $query->whereDate('incident_date', '<=', $filters['incident_date_to']);
        }

        // 생성일 범위 필터
        if (!empty($filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_at_from']);
        }

        if (!empty($filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_at_to']);
        }

        // 피해 금액 필터
        if (!empty($filters['damage_amount_min'])) {
            $query->whereJsonContains('metadata->damage_amount', '>=', $filters['damage_amount_min']);
        }

        if (!empty($filters['damage_amount_max'])) {
            $query->whereJsonContains('metadata->damage_amount', '<=', $filters['damage_amount_max']);
        }

        return $query;
    }

    /**
     * 정렬 적용
     */
    public function applySorting(Builder $query, string $sortBy, string $sortOrder): Builder
    {
        $availableColumns = [
            'id', 'complaint_number', 'title', 'status', 'priority',
            'created_at', 'updated_at', 'due_date', 'resolved_at',
            'category_id', 'department_id', 'assigned_to', 'created_by'
        ];
        
        $sortBy = in_array($sortBy, $availableColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * 관계 데이터 로드
     */
    public function withRelations(Builder $query, array $relations = []): Builder
    {
        if (!empty($relations)) {
            return $query->with($relations);
        }
        
        return $query;
    }

    /**
     * 기본 필터 적용
     */
    private function applyBasicFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query;
    }

    /**
     * 민원 번호로 조회
     */
    public function findByComplaintNumber(string $complaintNumber): ?Complaint
    {
        return $this->getModel()->where('complaint_number', $complaintNumber)->first();
    }

    /**
     * 상태별 민원 개수 조회
     */
    public function getCountByStatus(User $user): array
    {
        $query = $this->newQuery();
        $query = $this->applyAccessControl($query, $user);
        
        return $query->groupBy('status')
                    ->selectRaw('status, count(*) as count')
                    ->pluck('count', 'status')
                    ->toArray();
    }

    /**
     * 우선순위별 민원 개수 조회
     */
    public function getCountByPriority(User $user): array
    {
        $query = $this->newQuery();
        $query = $this->applyAccessControl($query, $user);
        
        return $query->groupBy('priority')
                    ->selectRaw('priority, count(*) as count')
                    ->pluck('count', 'priority')
                    ->toArray();
    }

    /**
     * 기간별 민원 개수 조회
     */
    public function getCountByPeriod(User $user, string $period): array
    {
        $query = $this->newQuery();
        $query = $this->applyAccessControl($query, $user);
        
        switch ($period) {
            case 'daily':
                return $query->selectRaw('DATE(created_at) as date, count(*) as count')
                            ->groupBy('date')
                            ->orderBy('date')
                            ->pluck('count', 'date')
                            ->toArray();
            
            case 'weekly':
                return $query->selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, count(*) as count')
                            ->groupBy('year', 'week')
                            ->orderBy('year')
                            ->orderBy('week')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'period' => "{$item->year}-W{$item->week}",
                                    'count' => $item->count
                                ];
                            })
                            ->pluck('count', 'period')
                            ->toArray();
            
            case 'monthly':
            default:
                return $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, count(*) as count')
                            ->groupBy('year', 'month')
                            ->orderBy('year')
                            ->orderBy('month')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'period' => "{$item->year}-{$item->month}",
                                    'count' => $item->count
                                ];
                            })
                            ->pluck('count', 'period')
                            ->toArray();
        }
    }

    /**
     * 만료된 민원 조회
     */
    public function getOverdueComplaints(User $user): Collection
    {
        $query = $this->newQuery()
            ->with(['category', 'department', 'complainant', 'assignedTo'])
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled']);
            
        $query = $this->applyAccessControl($query, $user);
        
        return $query->get();
    }

    /**
     * 최근 민원 조회
     */
    public function getRecentComplaints(User $user, int $limit = 10): Collection
    {
        $query = $this->newQuery()
            ->with(['category', 'department', 'complainant', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
            
        $query = $this->applyAccessControl($query, $user);
        
        return $query->get();
    }

    /**
     * 대량 업데이트
     */
    public function bulkUpdate(array $ids, array $data): bool
    {
        return $this->model->whereIn('id', $ids)->update($data) > 0;
    }

    /**
     * 통계 쿼리
     */
    public function getStatistics(User $user, array $filters = []): array
    {
        $baseQuery = $this->newQuery();
        $baseQuery = $this->applyAccessControl($baseQuery, $user);
        $baseQuery = $this->applyFilters($baseQuery, $filters);
        
        return [
            'total_complaints' => $baseQuery->count(),
            'status_breakdown' => $this->getCountByStatus($user),
            'priority_breakdown' => $this->getCountByPriority($user),
            'overdue_complaints' => $this->getOverdueComplaints($user)->count(),
            'recent_complaints' => $baseQuery->where('created_at', '>=', now()->subDays(7))->count(),
            'resolved_this_month' => $baseQuery->where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)
                ->count(),
        ];
    }

    /**
     * 마지막 민원 번호 조회
     */
    public function getLastComplaintNumber(string $prefix): ?string
    {
        return $this->getModel()->where('complaint_number', 'like', $prefix . '%')
            ->orderBy('complaint_number', 'desc')
            ->value('complaint_number');
    }

    /**
     * 민원 조회수 증가
     */
    public function incrementViews(Complaint $complaint): void
    {
        $complaint->increment('views');
    }

    /**
     * 중복 민원 검사
     */
    public function findDuplicates(array $criteria): Collection
    {
        $query = $this->newQuery();
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->get();
    }

    /**
     * 아카이브된 민원 조회
     */
    public function getArchivedComplaints(User $user): LengthAwarePaginator
    {
        $query = $this->getModel()->onlyTrashed()
            ->with(['category', 'department', 'complainant', 'assignedTo']);
            
        $query = $this->applyAccessControl($query, $user);
        
        return $query->paginate(self::DEFAULT_PER_PAGE);
    }

    /**
     * 민원 복원
     */
    public function restore(int $id): bool
    {
        $complaint = $this->getModel()->withTrashed()->find($id);
        
        return $complaint ? $complaint->restore() : false;
    }

    /**
     * 완전 삭제
     */
    public function forceDelete(int $id): bool
    {
        $complaint = $this->getModel()->withTrashed()->find($id);
        
        return $complaint ? $complaint->forceDelete() : false;
    }
}
