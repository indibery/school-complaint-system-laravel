<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('대시보드') }}
                    </x-nav-link>
                    
                    @can('viewAny', App\Models\Complaint::class)
                    <x-nav-link :href="route('complaints.index')" :active="request()->routeIs('complaints.*')">
                        {{ __('민원 관리') }}
                    </x-nav-link>
                    @endcan
                    
                    @role('admin')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('사용자 관리') }}
                    </x-nav-link>
                    @endrole
                </div>
            </div>

            <!-- Settings and Notifications -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Notifications Dropdown -->
                <div class="relative me-3">
                    <x-dropdown align="right" width="96">
                        <x-slot name="trigger">
                            <button id="notificationBtn" class="relative inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span id="notificationBadge" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full" style="display: none;">0</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="notification-dropdown">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <h6 class="text-sm font-semibold text-gray-700">알림</h6>
                                        <div class="flex space-x-2">
                                            <button id="markAllNotificationsRead" class="text-xs text-blue-600 hover:text-blue-800">
                                                모두 읽음
                                            </button>
                                            <button id="notificationSettings" class="text-xs text-gray-600 hover:text-gray-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="notificationDropdown" class="max-h-96 overflow-y-auto">
                                    <!-- 알림 항목들이 여기에 동적으로 추가됩니다 -->
                                    <div class="notification-empty p-8 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="mt-2 text-sm">새로운 알림이 없습니다</p>
                                    </div>
                                </div>
                                
                                <div class="px-4 py-3 border-t border-gray-200">
                                    <a href="{{ route('notifications.index') }}" class="text-sm text-center text-blue-600 hover:text-blue-800 block">
                                        모든 알림 보기
                                    </a>
                                </div>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
                
                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('프로필') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('로그아웃') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('대시보드') }}
            </x-responsive-nav-link>
            
            @can('viewAny', App\Models\Complaint::class)
            <x-responsive-nav-link :href="route('complaints.index')" :active="request()->routeIs('complaints.*')">
                {{ __('민원 관리') }}
            </x-responsive-nav-link>
            @endcan
            
            @role('admin')
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                {{ __('사용자 관리') }}
            </x-responsive-nav-link>
            @endrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('프로필') }}
                </x-responsive-nav-link>
                
                <x-responsive-nav-link :href="route('notifications.index')">
                    {{ __('알림') }}
                    <span id="mobileNotificationBadge" class="inline-flex items-center justify-center px-2 py-1 ms-2 text-xs font-bold leading-none text-white bg-red-600 rounded-full" style="display: none;">0</span>
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('로그아웃') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

@push('styles')
<style>
    .notification-dropdown {
        min-width: 384px;
    }
    
    .notification-item {
        padding: 0.75rem 1rem;
        display: flex;
        align-items: start;
        gap: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    
    .notification-item:hover {
        background-color: #f9fafb;
    }
    
    .notification-item.unread {
        background-color: #eff6ff;
    }
    
    .notification-icon {
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        background-color: #f3f4f6;
    }
    
    .notification-icon i {
        font-size: 1rem;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-message {
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #374151;
        margin: 0;
    }
    
    .notification-time {
        font-size: 0.75rem;
        line-height: 1rem;
        color: #6b7280;
    }
    
    .notification-unread-dot {
        width: 0.5rem;
        height: 0.5rem;
        background-color: #3b82f6;
        border-radius: 9999px;
        flex-shrink: 0;
        margin-top: 0.25rem;
    }
    
    .notification-empty {
        padding: 2rem;
        text-align: center;
        color: #6b7280;
    }
</style>
@endpush

@push('scripts')
<!-- 알림 관련 JavaScript 파일 -->
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/notifications.js') }}"></script>

<script>
// 전역 NotificationManager 인스턴스
let notificationManager;

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 인증된 사용자인 경우에만 알림 매니저 초기화
    @auth
    notificationManager = new NotificationManager({
        updateInterval: 30000, // 30초
        soundEnabled: true,
        desktopNotificationEnabled: false,
        apiEndpoint: '/api/v1/notifications'
    });
    @endauth
});

// 페이지 종료 시 정리
window.addEventListener('beforeunload', function() {
    if (notificationManager) {
        notificationManager.destroy();
    }
});
</script>
@endpush
