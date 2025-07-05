<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Complaint;
use App\Models\User;
use App\Models\Category;
use App\Models\Department;
use App\Models\Comment;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends BaseApiController
{
    /**
     * Get dashboard overview data.
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cacheKey = "dashboard_overview_{$user->id}";
            
            // 캐시에서 데이터 조회 (5분 캐시)
            $data = Cache::remember($cacheKey, 300, function () use ($user) {
                return $this->getOverviewData($user);
            });

            return $this->successResponse($data, '대시보드 데이터를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '대시보드 데이터 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get complaint statistics.
     */
    public function complaintStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $period = $request->input('period', '30'); // 기본 30일
            
            $stats = [
                'total' => $this->getComplaintCount($user, $period),
                'pending' => $this->getComplaintCount($user, $period, 'pending'),
                'in_progress' => $this->getComplaintCount($user, $period, 'in_progress'),
                'resolved' => $this->getComplaintCount($user, $period, 'resolved'),
                'closed' => $this->getComplaintCount($user, $period, 'closed'),
                'cancelled' => $this->getComplaintCount($user, $period, 'cancelled'),
                'urgent' => $this->getUrgentComplaintCount($user, $period),
                'overdue' => $this->getOverdueComplaintCount($user, $period),
                'daily_trend' => $this->getDailyTrend($user, $period),
                'category_distribution' => $this->getCategoryDistribution($user, $period),
                'department_distribution' => $this->getDepartmentDistribution($user, $period),
                'avg_resolution_time' => $this->getAverageResolutionTime($user, $period),
                'satisfaction_rating' => $this->getAverageSatisfactionRating($user, $period),
            ];

            return $this->successResponse($stats, '민원 통계를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get user performance metrics.
     */
    public function userPerformance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $period = $request->input('period', '30');
            
            $performance = [
                'assigned_complaints' => $this->getAssignedComplaintCount($user, $period),
                'resolved_complaints' => $this->getResolvedComplaintCount($user, $period),
                'resolution_rate' => $this->getResolutionRate($user, $period),
                'avg_resolution_time' => $this->getUserAverageResolutionTime($user, $period),
                'satisfaction_score' => $this->getUserSatisfactionScore($user, $period),
                'workload_distribution' => $this->getWorkloadDistribution($user, $period),
                'monthly_performance' => $this->getMonthlyPerformance($user),
                'achievements' => $this->getUserAchievements($user),
            ];

            return $this->successResponse($performance, '사용자 성과를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '사용자 성과 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get real-time alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $alerts = [
                'urgent_complaints' => $this->getUrgentComplaintAlerts($user),
                'overdue_complaints' => $this->getOverdueComplaintAlerts($user),
                'unassigned_complaints' => $this->getUnassignedComplaintAlerts($user),
                'high_volume_alerts' => $this->getHighVolumeAlerts($user),
                'system_alerts' => $this->getSystemAlerts($user),
            ];

            return $this->successResponse($alerts, '실시간 알림을 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '실시간 알림 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get recent activities.
     */
    public function recentActivities(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = min($request->input('limit', 20), 50);
            
            $activities = $this->getRecentActivities($user, $limit);

            return $this->successResponse($activities, '최근 활동을 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '최근 활동 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get system health metrics.
     */
    public function systemHealth(Request $request): JsonResponse
    {
        try {
            // 관리자만 접근 가능
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '시스템 상태를 조회할 권한이 없습니다.',
                    403
                );
            }

            $health = [
                'database' => $this->checkDatabaseHealth(),
                'storage' => $this->checkStorageHealth(),
                'cache' => $this->checkCacheHealth(),
                'api_response_time' => $this->getApiResponseTime(),
                'error_rate' => $this->getErrorRate(),
                'active_users' => $this->getActiveUsersCount(),
                'system_load' => $this->getSystemLoad(),
                'memory_usage' => $this->getMemoryUsage(),
            ];

            return $this->successResponse($health, '시스템 상태를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '시스템 상태 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get widget data.
     */
    public function getWidget(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'widget_type' => 'required|string|in:complaints_summary,recent_complaints,performance_chart,category_chart,department_workload,user_rankings',
                'period' => 'nullable|integer|min:1|max:365',
            ]);

            $user = $request->user();
            $widgetType = $request->input('widget_type');
            $period = $request->input('period', 30);

            $data = match ($widgetType) {
                'complaints_summary' => $this->getComplaintsSummaryWidget($user, $period),
                'recent_complaints' => $this->getRecentComplaintsWidget($user),
                'performance_chart' => $this->getPerformanceChartWidget($user, $period),
                'category_chart' => $this->getCategoryChartWidget($user, $period),
                'department_workload' => $this->getDepartmentWorkloadWidget($user, $period),
                'user_rankings' => $this->getUserRankingsWidget($user, $period),
                default => null,
            };

            if ($data === null) {
                return $this->errorResponse('지원하지 않는 위젯 유형입니다.', 400);
            }

            return $this->successResponse($data, '위젯 데이터를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '위젯 데이터 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get overview data for user.
     */
    private function getOverviewData(User $user): array
    {
        $query = $this->getBaseQuery($user);
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_complaints' => $query->count(),
            'pending_complaints' => $query->where('status', 'pending')->count(),
            'in_progress_complaints' => $query->where('status', 'in_progress')->count(),
            'resolved_complaints' => $query->where('status', 'resolved')->count(),
            'urgent_complaints' => $query->where('priority', 'urgent')->count(),
            'overdue_complaints' => $query->where('due_date', '<', now())
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->count(),
            'today_complaints' => $query->whereDate('created_at', $today)->count(),
            'this_week_complaints' => $query->where('created_at', '>=', $thisWeek)->count(),
            'this_month_complaints' => $query->where('created_at', '>=', $thisMonth)->count(),
            'my_assigned_complaints' => $user->hasRole(['admin', 'super_admin']) ? 
                null : Complaint::where('assigned_to', $user->id)->count(),
            'satisfaction_rating' => $this->getAverageSatisfactionRating($user, 30),
            'recent_complaints' => $this->getRecentComplaintsWidget($user, 5),
        ];
    }

    /**
     * Get base query for user's accessible complaints.
     */
    private function getBaseQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Complaint::query();

        // 관리자가 아닌 경우 접근 제한
        if (!$user->hasRole(['admin', 'super_admin'])) {
            if ($user->hasRole('parent')) {
                $query->where('created_by', $user->id);
            } elseif ($user->hasRole(['teacher', 'staff'])) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhere('department_id', $user->department_id);
                });
            }
        }

        return $query;
    }

    /**
     * Get complaint count for user in period.
     */
    private function getComplaintCount(User $user, int $period, string $status = null): int
    {
        $query = $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period));

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    /**
     * Get urgent complaint count.
     */
    private function getUrgentComplaintCount(User $user, int $period): int
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
            ->where('priority', 'urgent')
            ->count();
    }

    /**
     * Get overdue complaint count.
     */
    private function getOverdueComplaintCount(User $user, int $period): int
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->count();
    }

    /**
     * Get daily trend data.
     */
    private function getDailyTrend(User $user, int $period): array
    {
        $data = [];
        
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = $this->getBaseQuery($user)
                ->whereDate('created_at', $date)
                ->count();
                
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get category distribution.
     */
    private function getCategoryDistribution(User $user, int $period): array
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
            ->join('categories', 'complaints.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get department distribution.
     */
    private function getDepartmentDistribution(User $user, int $period): array
    {
        return $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
            ->join('departments', 'complaints.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('COUNT(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get average resolution time.
     */
    private function getAverageResolutionTime(User $user, int $period): ?float
    {
        $resolvedComplaints = $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
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
     * Get average satisfaction rating.
     */
    private function getAverageSatisfactionRating(User $user, int $period): ?float
    {
        $avg = $this->getBaseQuery($user)
            ->where('created_at', '>=', now()->subDays($period))
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get recent complaints widget data.
     */
    private function getRecentComplaintsWidget(User $user, int $limit = 10): array
    {
        return $this->getBaseQuery($user)
            ->with(['category', 'complainant'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'status' => $complaint->status,
                    'priority' => $complaint->priority,
                    'category' => $complaint->category?->name,
                    'complainant' => $complaint->complainant?->name,
                    'created_at' => $complaint->created_at->diffForHumans(),
                    'is_urgent' => $complaint->priority === 'urgent',
                    'is_overdue' => $complaint->due_date && $complaint->due_date->isPast(),
                ];
            })
            ->toArray();
    }

    /**
     * Get system health checks.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 2),
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health.
     */
    private function checkStorageHealth(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = round((($totalSpace - $diskSpace) / $totalSpace) * 100, 2);
            
            return [
                'status' => $usedPercentage > 90 ? 'warning' : 'healthy',
                'used_percentage' => $usedPercentage,
                'free_space' => $this->formatBytes($diskSpace),
                'total_space' => $this->formatBytes($totalSpace),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health.
     */
    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $retrieved === $testValue ? 'healthy' : 'unhealthy',
                'message' => $retrieved === $testValue ? 'Cache working properly' : 'Cache not working',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    // 추가 메소드들은 간단히 구현하거나 TODO로 남겨둡니다
    private function getApiResponseTime(): float { return 0.0; }
    private function getErrorRate(): float { return 0.0; }
    private function getActiveUsersCount(): int { return 0; }
    private function getSystemLoad(): float { return 0.0; }
    private function getMemoryUsage(): array { return []; }
    private function getAssignedComplaintCount(User $user, int $period): int { return 0; }
    private function getResolvedComplaintCount(User $user, int $period): int { return 0; }
    private function getResolutionRate(User $user, int $period): float { return 0.0; }
    private function getUserAverageResolutionTime(User $user, int $period): ?float { return null; }
    private function getUserSatisfactionScore(User $user, int $period): ?float { return null; }
    private function getWorkloadDistribution(User $user, int $period): array { return []; }
    private function getMonthlyPerformance(User $user): array { return []; }
    private function getUserAchievements(User $user): array { return []; }
    private function getUrgentComplaintAlerts(User $user): array { return []; }
    private function getOverdueComplaintAlerts(User $user): array { return []; }
    private function getUnassignedComplaintAlerts(User $user): array { return []; }
    private function getHighVolumeAlerts(User $user): array { return []; }
    private function getSystemAlerts(User $user): array { return []; }
    private function getRecentActivities(User $user, int $limit): array { return []; }
    private function getComplaintsSummaryWidget(User $user, int $period): array { return []; }
    private function getPerformanceChartWidget(User $user, int $period): array { return []; }
    private function getCategoryChartWidget(User $user, int $period): array { return []; }
    private function getDepartmentWorkloadWidget(User $user, int $period): array { return []; }
    private function getUserRankingsWidget(User $user, int $period): array { return []; }
}
