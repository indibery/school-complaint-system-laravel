<?php

namespace App\Providers;

use App\Events\ComplaintCreated;
use App\Events\ComplaintStatusChanged;
use App\Events\ComplaintAssigned;
use App\Events\ComplaintCommentAdded;
use App\Events\ComplaintResolved;
use App\Listeners\SendComplaintNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // 민원 관련 이벤트
        ComplaintCreated::class => [
            SendComplaintNotification::class,
        ],
        
        ComplaintStatusChanged::class => [
            SendComplaintNotification::class,
        ],
        
        ComplaintAssigned::class => [
            SendComplaintNotification::class,
        ],
        
        ComplaintCommentAdded::class => [
            SendComplaintNotification::class,
        ],
        
        ComplaintResolved::class => [
            SendComplaintNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
