<?php

namespace App\Repositories;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComplaintStatisticsRepository
{
    public function __construct(
        private Complaint $model
    ) {}

    /**
     * 대시보드 기본 통계
     */
    public function getDashboardStats(User $user): array
    {
        $cacheKey = "dashboard_stats_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            return [
                'total_complaints' => $baseQuery->count(),
                'pending_complaints' => $baseQuery->where('status', 'pending')->count(),
                'in_progress_complaints' => $baseQuery->where('status', 'in_progress')->count(),
                'resolved_complaints' => $baseQuery->where('status', 'resolved')->count(),
                'urgent_complaints' => $baseQuery->where('priority', 'urgent')->count(),
                'overdue_complaints' => $baseQuery->where('due_date', '<', now())
                    ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                    ->count(),
                'today_complaints' => $baseQuery->whereDate('created_at', today())->count(),
                'this_week_complaints' => $baseQuery->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month_complaints' => $baseQuery->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];
        });
    }

    /**
     * 상태별 통계
     */
    public function getStatusBreakdown(User $user): array
    {
        $cacheKey = "status_breakdown_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            return $baseQuery->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();
        });
    }

    /**
     * 우선순위별 통계
     */
    public function getPriorityBreakdown(User $user): array
    {
        $cacheKey = "priority_breakdown_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            return $baseQuery->groupBy('priority')
                ->selectRaw('priority, count(*) as count')
                ->pluck('count', 'priority')
                ->toArray();
        });
    }

    /**
     * 카테고리별 통계
     */
    public function getCategoryBreakdown(User $user): array
    {
        $cacheKey = "category_breakdown_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            return $baseQuery->join('complaint_categories', 'complaints.category_id', '=', 'complaint_categories.id')
                ->groupBy('complaint_categories.name')
                ->selectRaw('complaint_categories.name, count(*) as count')
                ->pluck('count', 'name')
                ->toArray();
        });
    }

    /**
     * 부서별 통계
     */
    public function getDepartmentBreakdown(User $user): array
    {
        $cacheKey = "department_breakdown_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            return $baseQuery->join('departments', 'complaints.department_id', '=', 'departments.id')
                ->groupBy('departments.name')
                ->selectRaw('departments.name, count(*) as count')
                ->pluck('count', 'name')
                ->toArray();
        });
    }

    /**
     * 월별 트렌드 분석
     */
    public function getMonthlyTrends(User $user, int $months = 12): array
    {
        $cacheKey = "monthly_trends_{$user->id}_{$months}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user, $months) {
            $baseQuery = $this->getBaseQuery($user);
            
            $startDate = now()->subMonths($months)->startOfMonth();
            
            return $baseQuery->where('created_at', '>=', $startDate)
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, count(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => "{$item->year}-{$item->month}",
                        'count' => $item->count,
                        'year' => $item->year,
                        'month' => $item->month
                    ];
                })
                ->toArray();
        });
    }

    /**
     * 처리 시간 통계
     */
    public function getProcessingTimeStats(User $user): array
    {
        $cacheKey = "processing_time_stats_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            $resolvedComplaints = $baseQuery->whereNotNull('resolved_at')
                ->selectRaw('
                    AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours,
                    MIN(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as min_hours,
                    MAX(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as max_hours,
                    COUNT(*) as total_resolved
                ')
                ->first();
            
            return [
                'average_resolution_time' => round($resolvedComplaints->avg_hours ?? 0, 2),
                'fastest_resolution_time' => $resolvedComplaints->min_hours ?? 0,
                'slowest_resolution_time' => $resolvedComplaints->max_hours ?? 0,
                'total_resolved' => $resolvedComplaints->total_resolved ?? 0,
            ];
        });
    }

    /**
     * 사용자별 성과 통계
     */
    public function getUserPerformanceStats(User $user): array
    {
        $cacheKey = "user_performance_stats_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            // 담당자별 통계
            $assigneeStats = $baseQuery->whereNotNull('assigned_to')
                ->join('users', 'complaints.assigned_to', '=', 'users.id')
                ->groupBy('users.name')
                ->selectRaw('
                    users.name,
                    count(*) as total_assigned,
                    sum(case when status = "resolved" then 1 else 0 end) as resolved_count,
                    avg(case when resolved_at is not null then TIMESTAMPDIFF(HOUR, created_at, resolved_at) end) as avg_resolution_time
                ')
                ->having('total_assigned', '>', 0)
                ->orderBy('total_assigned', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            // 작성자별 통계
            $submitterStats = $baseQuery->join('users', 'complaints.created_by', '=', 'users.id')
                ->groupBy('users.name')
                ->selectRaw('
                    users.name,
                    count(*) as total_submitted,
                    sum(case when status = "resolved" then 1 else 0 end) as resolved_count
                ')
                ->having('total_submitted', '>', 0)
                ->orderBy('total_submitted', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            return [
                'top_assignees' => $assigneeStats,
                'top_submitters' => $submitterStats,
            ];
        });
    }

    /**
     * 만족도 통계
     */
    public function getSatisfactionStats(User $user): array
    {
        $cacheKey = "satisfaction_stats_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            // 만족도 데이터가 있는 경우
            $satisfactionData = $baseQuery->whereNotNull('satisfaction_rating')
                ->selectRaw('
                    AVG(satisfaction_rating) as average_rating,
                    COUNT(*) as total_ratings,
                    SUM(CASE WHEN satisfaction_rating >= 4 THEN 1 ELSE 0 END) as positive_ratings,
                    SUM(CASE WHEN satisfaction_rating <= 2 THEN 1 ELSE 0 END) as negative_ratings
                ')
                ->first();
            
            $totalRatings = $satisfactionData->total_ratings ?? 0;
            
            return [
                'average_satisfaction' => round($satisfactionData->average_rating ?? 0, 2),
                'total_ratings' => $totalRatings,
                'positive_rate' => $totalRatings > 0 ? round(($satisfactionData->positive_ratings / $totalRatings) * 100, 2) : 0,
                'negative_rate' => $totalRatings > 0 ? round(($satisfactionData->negative_ratings / $totalRatings) * 100, 2) : 0,
            ];
        });
    }

    /**
     * 성과 지표 KPI
     */
    public function getKPIs(User $user): array
    {
        $cacheKey = "kpis_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $baseQuery = $this->getBaseQuery($user);
            
            $totalComplaints = $baseQuery->count();
            $resolvedComplaints = $baseQuery->where('status', 'resolved')->count();
            $overdueComplaints = $baseQuery->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->count();
            
            return [
                'resolution_rate' => $totalComplaints > 0 ? round(($resolvedComplaints / $totalComplaints) * 100, 2) : 0,
                'overdue_rate' => $totalComplaints > 0 ? round(($overdueComplaints / $totalComplaints) * 100, 2) : 0,
                'total_complaints' => $totalComplaints,
                'resolved_complaints' => $resolvedComplaints,
                'overdue_complaints' => $overdueComplaints,
            ];
        });
    }

    /**
     * 기간별 비교 분석
     */
    public function getComparisonAnalysis(User $user, string $currentPeriod, string $previousPeriod): array
    {
        $cacheKey = "comparison_analysis_{$user->id}_{$currentPeriod}_{$previousPeriod}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user, $currentPeriod, $previousPeriod) {
            $baseQuery = $this->getBaseQuery($user);
            
            $currentData = $this->getPeriodStats($baseQuery, $currentPeriod);
            $previousData = $this->getPeriodStats($baseQuery, $previousPeriod);
            
            return [
                'current_period' => $currentData,
                'previous_period' => $previousData,
                'growth_rate' => $previousData['total'] > 0 ? 
                    round((($currentData['total'] - $previousData['total']) / $previousData['total']) * 100, 2) : 0,
                'resolution_rate_change' => $currentData['resolution_rate'] - $previousData['resolution_rate'],
            ];
        });
    }

    /**
     * 사용자 권한 기반 기본 쿼리
     */
    private function getBaseQuery(User $user): Builder
    {
        $query = $this->model->newQuery();
        
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        // 교사/직원은 할당받은 민원과 본인이 작성한 민원, 동일 부서 민원 조회 가능
        if ($user->hasRole(['teacher', 'staff'])) {
            return $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('department_id', $user->department_id);
            });
        }
        
        // 학부모는 본인과 자녀 관련 민원만 조회 가능
        if ($user->hasRole('parent')) {
            $studentIds = $user->children()->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $studentIds) {
                $q->where('created_by', $user->id);
                if (!empty($studentIds)) {
                    $q->orWhereIn('student_id', $studentIds);
                }
            });
        }
        
        // 학생은 본인 관련 민원만 조회 가능
        if ($user->hasRole('student')) {
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('student_id', $user->id);
            });
        }
        
        // 기본적으로 본인이 작성한 민원만 조회 가능
        return $query->where('created_by', $user->id);
    }

    /**
     * 기간별 통계 계산
     */
    private function getPeriodStats(Builder $baseQuery, string $period): array
    {
        $query = clone $baseQuery;
        
        switch ($period) {
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'last_week':
                $query->whereBetween('created_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'last_month':
                $query->whereMonth('created_at', now()->subMonth()->month)
                      ->whereYear('created_at', now()->subMonth()->year);
                break;
            case 'this_year':
                $query->whereYear('created_at', now()->year);
                break;
            case 'last_year':
                $query->whereYear('created_at', now()->subYear()->year);
                break;
        }
        
        $total = $query->count();
        $resolved = $query->where('status', 'resolved')->count();
        
        return [
            'total' => $total,
            'resolved' => $resolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
        ];
    }

    /**
     * 통계 캐시 무효화
     */
    public function clearStatsCache(User $user): void
    {
        Cache::forget("dashboard_stats_{$user->id}");
        Cache::forget("status_breakdown_{$user->id}");
        Cache::forget("priority_breakdown_{$user->id}");
        Cache::forget("category_breakdown_{$user->id}");
        Cache::forget("department_breakdown_{$user->id}");
        Cache::forget("monthly_trends_{$user->id}_12");
        Cache::forget("processing_time_stats_{$user->id}");
        Cache::forget("user_performance_stats_{$user->id}");
        Cache::forget("satisfaction_stats_{$user->id}");
        Cache::forget("kpis_{$user->id}");
    }
}
