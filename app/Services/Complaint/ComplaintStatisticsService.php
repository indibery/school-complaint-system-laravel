<?php

namespace App\Services\Complaint;

use App\Models\User;
use App\Repositories\ComplaintStatisticsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ComplaintStatisticsService implements ComplaintStatisticsServiceInterface
{
    public function __construct(
        private ComplaintStatisticsRepository $statisticsRepository
    ) {}

    /**
     * 대시보드 통계 조회
     */
    public function getDashboardStats(User $user): array
    {
        try {
            return $this->statisticsRepository->getDashboardStats($user);
        } catch (\Exception $e) {
            Log::error('대시보드 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // 오류 발생 시 기본값 반환
            return $this->getDefaultStats();
        }
    }

    /**
     * 상태별 통계
     */
    public function getStatusBreakdown(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getStatusBreakdown($user);
        } catch (\Exception $e) {
            Log::error('상태별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 우선순위별 통계
     */
    public function getPriorityBreakdown(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getPriorityBreakdown($user);
        } catch (\Exception $e) {
            Log::error('우선순위별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 카테고리별 통계
     */
    public function getCategoryBreakdown(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getCategoryBreakdown($user);
        } catch (\Exception $e) {
            Log::error('카테고리별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 부서별 통계
     */
    public function getDepartmentBreakdown(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getDepartmentBreakdown($user);
        } catch (\Exception $e) {
            Log::error('부서별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 기간별 통계
     */
    public function getTimeSeriesStats(
        User $user,
        string $period = 'month',
        int $months = 12
    ): array {
        try {
            return $this->statisticsRepository->getMonthlyTrends($user, $months);
        } catch (\Exception $e) {
            Log::error('기간별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'period' => $period,
                'months' => $months,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 처리 시간 통계
     */
    public function getProcessingTimeStats(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getProcessingTimeStats($user);
        } catch (\Exception $e) {
            Log::error('처리 시간 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'average_resolution_time' => 0,
                'fastest_resolution_time' => 0,
                'slowest_resolution_time' => 0,
                'total_resolved' => 0,
            ];
        }
    }

    /**
     * 사용자별 통계
     */
    public function getUserStats(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getUserPerformanceStats($user);
        } catch (\Exception $e) {
            Log::error('사용자별 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'top_assignees' => [],
                'top_submitters' => [],
            ];
        }
    }

    /**
     * 만족도 통계
     */
    public function getSatisfactionStats(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getSatisfactionStats($user);
        } catch (\Exception $e) {
            Log::error('만족도 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'average_satisfaction' => 0,
                'total_ratings' => 0,
                'positive_rate' => 0,
                'negative_rate' => 0,
            ];
        }
    }

    /**
     * 성과 지표
     */
    public function getPerformanceMetrics(User $user, array $filters = []): array
    {
        try {
            return $this->statisticsRepository->getKPIs($user);
        } catch (\Exception $e) {
            Log::error('성과 지표 조회 중 오류 발생', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'resolution_rate' => 0,
                'overdue_rate' => 0,
                'total_complaints' => 0,
                'resolved_complaints' => 0,
                'overdue_complaints' => 0,
            ];
        }
    }

    /**
     * 트렌드 분석
     */
    public function getTrendAnalysis(User $user, string $metric = 'count'): array
    {
        try {
            $trends = $this->statisticsRepository->getMonthlyTrends($user, 12);
            
            // 트렌드 분석 로직
            if (count($trends) < 2) {
                return [
                    'trend_direction' => 'stable',
                    'trend_percentage' => 0,
                    'data' => $trends,
                ];
            }

            $recent = array_slice($trends, -3); // 최근 3개월
            $previous = array_slice($trends, -6, 3); // 이전 3개월

            $recentAvg = array_sum(array_column($recent, 'count')) / count($recent);
            $previousAvg = array_sum(array_column($previous, 'count')) / count($previous);

            $trendPercentage = $previousAvg > 0 ? 
                round((($recentAvg - $previousAvg) / $previousAvg) * 100, 2) : 0;

            $trendDirection = $trendPercentage > 5 ? 'increasing' : 
                            ($trendPercentage < -5 ? 'decreasing' : 'stable');

            return [
                'trend_direction' => $trendDirection,
                'trend_percentage' => $trendPercentage,
                'recent_average' => round($recentAvg, 2),
                'previous_average' => round($previousAvg, 2),
                'data' => $trends,
            ];

        } catch (\Exception $e) {
            Log::error('트렌드 분석 중 오류 발생', [
                'user_id' => $user->id,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);
            
            return [
                'trend_direction' => 'stable',
                'trend_percentage' => 0,
                'data' => [],
            ];
        }
    }

    /**
     * 비교 분석
     */
    public function getComparisonAnalysis(
        User $user,
        string $currentPeriod,
        string $previousPeriod
    ): array {
        try {
            return $this->statisticsRepository->getComparisonAnalysis(
                $user,
                $currentPeriod,
                $previousPeriod
            );
        } catch (\Exception $e) {
            Log::error('비교 분석 중 오류 발생', [
                'user_id' => $user->id,
                'current_period' => $currentPeriod,
                'previous_period' => $previousPeriod,
                'error' => $e->getMessage()
            ]);
            
            return [
                'current_period' => ['total' => 0, 'resolved' => 0, 'resolution_rate' => 0],
                'previous_period' => ['total' => 0, 'resolved' => 0, 'resolution_rate' => 0],
                'growth_rate' => 0,
                'resolution_rate_change' => 0,
            ];
        }
    }

    /**
     * 상위 N개 통계
     */
    public function getTopStats(User $user, string $type, int $limit = 10): array
    {
        try {
            switch ($type) {
                case 'categories':
                    $data = $this->getCategoryBreakdown($user);
                    break;
                case 'departments':
                    $data = $this->getDepartmentBreakdown($user);
                    break;
                case 'assignees':
                    $userData = $this->getUserStats($user);
                    $data = $userData['top_assignees'] ?? [];
                    break;
                case 'submitters':
                    $userData = $this->getUserStats($user);
                    $data = $userData['top_submitters'] ?? [];
                    break;
                default:
                    $data = [];
            }

            // 상위 N개 추출
            if (is_array($data) && !empty($data)) {
                arsort($data);
                return array_slice($data, 0, $limit, true);
            }

            return [];

        } catch (\Exception $e) {
            Log::error('상위 통계 조회 중 오류 발생', [
                'user_id' => $user->id,
                'type' => $type,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 통계 데이터 캐싱
     */
    public function getCachedStats(string $key, callable $callback, int $minutes = 60): mixed
    {
        try {
            return Cache::remember($key, now()->addMinutes($minutes), $callback);
        } catch (\Exception $e) {
            Log::error('통계 캐싱 중 오류 발생', [
                'cache_key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // 캐싱 실패 시 직접 실행
            return $callback();
        }
    }

    /**
     * 통계 데이터 내보내기
     */
    public function exportStats(User $user, array $filters = [], string $format = 'excel'): string
    {
        try {
            $stats = [
                'dashboard' => $this->getDashboardStats($user),
                'status_breakdown' => $this->getStatusBreakdown($user, $filters),
                'priority_breakdown' => $this->getPriorityBreakdown($user, $filters),
                'category_breakdown' => $this->getCategoryBreakdown($user, $filters),
                'department_breakdown' => $this->getDepartmentBreakdown($user, $filters),
                'processing_time' => $this->getProcessingTimeStats($user, $filters),
                'satisfaction' => $this->getSatisfactionStats($user, $filters),
                'performance' => $this->getPerformanceMetrics($user, $filters),
                'trends' => $this->getTimeSeriesStats($user, 'month', 12),
                'generated_at' => now()->toDateTimeString(),
                'generated_by' => $user->name,
            ];

            // 파일 저장 경로 생성
            $filename = 'complaint_stats_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
            $filepath = storage_path('app/exports/' . $filename);

            // 디렉토리 생성
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // 형식에 따라 내보내기
            switch ($format) {
                case 'json':
                    file_put_contents($filepath, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;
                case 'csv':
                    $this->exportToCsv($stats, $filepath);
                    break;
                case 'excel':
                default:
                    $this->exportToExcel($stats, $filepath);
                    break;
            }

            Log::info('통계 데이터 내보내기 완료', [
                'user_id' => $user->id,
                'format' => $format,
                'filepath' => $filepath
            ]);

            return $filepath;

        } catch (\Exception $e) {
            Log::error('통계 데이터 내보내기 중 오류 발생', [
                'user_id' => $user->id,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 기본 통계 데이터 반환
     */
    private function getDefaultStats(): array
    {
        return [
            'total_complaints' => 0,
            'pending_complaints' => 0,
            'in_progress_complaints' => 0,
            'resolved_complaints' => 0,
            'urgent_complaints' => 0,
            'overdue_complaints' => 0,
            'today_complaints' => 0,
            'this_week_complaints' => 0,
            'this_month_complaints' => 0,
        ];
    }

    /**
     * CSV 내보내기
     */
    private function exportToCsv(array $stats, string $filepath): void
    {
        $handle = fopen($filepath, 'w');
        
        // BOM 추가 (엑셀에서 한글 깨짐 방지)
        fwrite($handle, "\xEF\xBB\xBF");
        
        // 헤더 추가
        fputcsv($handle, ['구분', '항목', '값']);
        
        // 데이터 추가
        foreach ($stats as $section => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    fputcsv($handle, [$section, $key, $value]);
                }
            }
        }
        
        fclose($handle);
    }

    /**
     * Excel 내보내기 (간단한 구현)
     */
    private function exportToExcel(array $stats, string $filepath): void
    {
        // 간단한 Excel 형식으로 내보내기
        // 실제 구현에서는 PhpSpreadsheet 등을 사용
        $this->exportToCsv($stats, $filepath);
    }
}
