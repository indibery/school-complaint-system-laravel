<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Policies\ComplaintPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Complaint::class => ComplaintPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // 추가 권한 정의
        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('teacher', function ($user) {
            return $user->role === 'teacher';
        });

        Gate::define('parent', function ($user) {
            return $user->role === 'parent';
        });

        Gate::define('staff', function ($user) {
            return in_array($user->role, ['security_staff', 'ops_staff']);
        });

        Gate::define('can-manage-users', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('can-view-reports', function ($user) {
            return in_array($user->role, ['admin', 'teacher']);
        });
    }
}
