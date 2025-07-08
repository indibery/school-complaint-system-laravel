<?php

namespace App\Providers;

use App\Repositories\ComplaintRepositoryInterface;
use App\Repositories\ComplaintRepository;
use App\Services\Complaint\ComplaintServiceInterface;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStatusServiceInterface;
use App\Services\Complaint\ComplaintStatusService;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Services\Complaint\ComplaintAssignmentService;
use App\Services\Complaint\ComplaintFileServiceInterface;
use App\Services\Complaint\ComplaintFileService;
use App\Services\Complaint\ComplaintNotificationServiceInterface;
use App\Services\Complaint\ComplaintNotificationService;
use App\Services\Complaint\ComplaintStatisticsServiceInterface;
use App\Services\Complaint\ComplaintStatisticsService;
use Illuminate\Support\ServiceProvider;

class ComplaintServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository 바인딩
        $this->app->bind(
            ComplaintRepositoryInterface::class,
            ComplaintRepository::class
        );

        // Service 바인딩
        $this->app->bind(
            ComplaintServiceInterface::class,
            ComplaintService::class
        );

        $this->app->bind(
            ComplaintStatusServiceInterface::class,
            ComplaintStatusService::class
        );

        $this->app->bind(
            ComplaintAssignmentServiceInterface::class,
            ComplaintAssignmentService::class
        );

        $this->app->bind(
            ComplaintFileServiceInterface::class,
            ComplaintFileService::class
        );

        $this->app->bind(
            ComplaintNotificationServiceInterface::class,
            ComplaintNotificationService::class
        );

        $this->app->bind(
            ComplaintStatisticsServiceInterface::class,
            ComplaintStatisticsService::class
        );

        // 싱글톤으로 등록할 서비스들
        $this->app->singleton(ComplaintService::class);
        $this->app->singleton(ComplaintStatusService::class);
        $this->app->singleton(ComplaintAssignmentService::class);
        $this->app->singleton(ComplaintFileService::class);
        $this->app->singleton(ComplaintNotificationService::class);
        $this->app->singleton(ComplaintStatisticsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ComplaintRepositoryInterface::class,
            ComplaintServiceInterface::class,
            ComplaintStatusServiceInterface::class,
            ComplaintAssignmentServiceInterface::class,
            ComplaintFileServiceInterface::class,
            ComplaintNotificationServiceInterface::class,
            ComplaintStatisticsServiceInterface::class,
        ];
    }
}
