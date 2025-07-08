<?php

namespace App\Services\Complaint;

use App\Models\User;

interface ComplaintStatisticsServiceInterface
{
    /**
     * 대시보드 통계 조회
     */
    public function getDashboardStats(User $user): array;

    /**
     * 상태별 통계
     */
    public function getStatusBreakdown(User $user, array $filters = []): array;

    /**
     * 우선순위별 통계
     */
    public function getPriorityBreakdown(User $user, array $filters = []): array;

    /**
     * 카테고리별 통계
     */
    public function getCategoryBreakdown(User $user, array $filters = []): array;

    /**
     * 부서별 통계
     */
    public function getDepartmentBreakdown(User $user, array $filters = []): array;

    /**
     * 기간별 통계
     */
    public function getTimeSeriesStats(
        User $user,
        string $period = 'month',
        int $months = 12
    ): array;

    /**
     * 처리 시간 통계
     */
    public function getProcessingTimeStats(User $user, array $filters = []): array;

    /**
     * 사용자별 통계
     */
    public function getUserStats(User $user, array $filters = []): array;

    /**
     * 만족도 통계
     */
    public function getSatisfactionStats(User $user, array $filters = []): array;

    /**
     * 성과 지표
     */
    public function getPerformanceMetrics(User $user, array $filters = []): array;

    /**
     * 트렌드 분석
     */
    public function getTrendAnalysis(User $user, string $metric = 'count'): array;

    /**
     * 비교 분석
     */
    public function getComparisonAnalysis(
        User $user,
        string $currentPeriod,
        string $previousPeriod
    ): array;

    /**
     * 상위 N개 통계
     */
    public function getTopStats(User $user, string $type, int $limit = 10): array;

    /**
     * 통계 데이터 캐싱
     */
    public function getCachedStats(string $key, callable $callback, int $minutes = 60): mixed;

    /**
     * 통계 데이터 내보내기
     */
    public function exportStats(User $user, array $filters = [], string $format = 'excel'): string;
}
