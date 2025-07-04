<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_id',
        'status',
        'contact_email',
        'contact_phone',
        'location',
        'budget',
        'established_date',
        'metadata',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'established_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 부서장
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    /**
     * 부서원들
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    /**
     * 부서 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 활성 민원들
     */
    public function activeComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class)
            ->whereNotIn('status', ['closed', 'cancelled']);
    }

    /**
     * 스코프: 활성 부서
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 스코프: 비활성 부서
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * 스코프: 부서명으로 검색
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * 부서원 수 조회
     */
    public function getMembersCount(): int
    {
        return $this->members()->count();
    }

    /**
     * 활성 부서원 수 조회
     */
    public function getActiveMembersCount(): int
    {
        return $this->members()->where('status', 'active')->count();
    }

    /**
     * 부서 민원 수 조회
     */
    public function getComplaintsCount(): int
    {
        return $this->complaints()->count();
    }

    /**
     * 활성 민원 수 조회
     */
    public function getActiveComplaintsCount(): int
    {
        return $this->activeComplaints()->count();
    }

    /**
     * 부서장 여부 확인
     */
    public function hasHead(): bool
    {
        return !is_null($this->head_id);
    }

    /**
     * 부서원 존재 여부 확인
     */
    public function hasMembers(): bool
    {
        return $this->members()->exists();
    }

    /**
     * 민원 존재 여부 확인
     */
    public function hasComplaints(): bool
    {
        return $this->complaints()->exists();
    }

    /**
     * 사용자가 부서장인지 확인
     */
    public function isHead(User $user): bool
    {
        return $this->head_id === $user->id;
    }

    /**
     * 사용자가 부서원인지 확인
     */
    public function isMember(User $user): bool
    {
        return $user->department_id === $this->id;
    }

    /**
     * 부서장 설정
     */
    public function setHead(User $user): void
    {
        // 기존 부서장 해제
        if ($this->head_id) {
            $oldHead = $this->head;
            if ($oldHead) {
                $oldHead->update(['department_id' => null]);
            }
        }

        // 새 부서장 설정
        $this->update(['head_id' => $user->id]);
        $user->update(['department_id' => $this->id]);
    }

    /**
     * 부서원 추가
     */
    public function addMember(User $user): void
    {
        $user->update(['department_id' => $this->id]);
    }

    /**
     * 부서원 제거
     */
    public function removeMember(User $user): void
    {
        if ($this->isHead($user)) {
            throw new \Exception('부서장은 부서에서 제거할 수 없습니다.');
        }

        $user->update(['department_id' => null]);
    }

    /**
     * 부서 활성화
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * 부서 비활성화
     */
    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    /**
     * 부서 상태 토글
     */
    public function toggleStatus(): void
    {
        $newStatus = $this->status === 'active' ? 'inactive' : 'active';
        $this->update(['status' => $newStatus]);
    }

    /**
     * 부서 통계 조회
     */
    public function getStatistics(): array
    {
        return [
            'members_count' => $this->getMembersCount(),
            'active_members_count' => $this->getActiveMembersCount(),
            'complaints_count' => $this->getComplaintsCount(),
            'active_complaints_count' => $this->getActiveComplaintsCount(),
            'completed_complaints_count' => $this->complaints()->where('status', 'closed')->count(),
            'pending_complaints_count' => $this->complaints()->where('status', 'pending')->count(),
            'in_progress_complaints_count' => $this->complaints()->where('status', 'in_progress')->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'satisfaction_rating' => $this->getAverageSatisfactionRating(),
        ];
    }

    /**
     * 평균 해결 시간 계산 (시간 단위)
     */
    public function getAverageResolutionTime(): ?float
    {
        $resolvedComplaints = $this->complaints()
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolvedComplaints->isEmpty()) {
            return null;
        }

        $totalHours = $resolvedComplaints->sum(function ($complaint) {
            return $complaint->created_at->diffInHours($complaint->resolved_at);
        });

        return round($totalHours / $resolvedComplaints->count(), 1);
    }

    /**
     * 평균 만족도 계산
     */
    public function getAverageSatisfactionRating(): ?float
    {
        $avg = $this->complaints()
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * 월별 민원 데이터 조회
     */
    public function getMonthlyComplaintsData(int $months = 12): array
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = $this->complaints()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $data[] = [
                'month' => $date->format('Y-m'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * 카테고리별 민원 분포
     */
    public function getComplaintsByCategory(): array
    {
        return $this->complaints()
            ->join('categories', 'complaints.category_id', '=', 'categories.id')
            ->select('categories.name', \DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * 부서 최고 성과자 조회
     */
    public function getTopPerformers(int $limit = 5): array
    {
        return $this->members()
            ->join('complaints', 'users.id', '=', 'complaints.assigned_to')
            ->where('complaints.status', 'closed')
            ->select('users.name', \DB::raw('COUNT(*) as resolved_count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 부서 삭제 가능 여부 확인
     */
    public function canDelete(): bool
    {
        return !$this->hasMembers() && !$this->hasComplaints();
    }

    /**
     * 부서 정보 검증
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->hasHead()) {
            $errors[] = '부서장이 지정되지 않았습니다.';
        }

        if (!$this->hasMembers()) {
            $errors[] = '부서원이 없습니다.';
        }

        if ($this->status === 'inactive' && $this->hasComplaints()) {
            $errors[] = '처리 중인 민원이 있는 부서는 비활성화할 수 없습니다.';
        }

        return $errors;
    }
}
