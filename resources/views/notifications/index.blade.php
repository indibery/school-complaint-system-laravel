@extends('layouts.app')

@section('title', '알림')

@push('styles')
<style>
    .notification-list {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .notification-item {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: start;
        gap: 1rem;
        transition: background-color 0.15s ease-in-out;
        cursor: pointer;
    }
    
    .notification-item:hover {
        background-color: #f9fafb;
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-item.unread {
        background-color: #eff6ff;
    }
    
    .notification-icon {
        flex-shrink: 0;
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        background-color: #f3f4f6;
    }
    
    .notification-icon i {
        font-size: 1.25rem;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-title {
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.25rem;
    }
    
    .notification-message {
        font-size: 0.875rem;
        color: #4b5563;
        margin-bottom: 0.25rem;
    }
    
    .notification-time {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .notification-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .notification-actions button {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .toolbar {
        padding: 1rem;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .filter-tabs {
        display: flex;
        gap: 1rem;
    }
    
    .filter-tab {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    
    .filter-tab.active {
        color: #1d4ed8;
        background: white;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    
    .unread-dot {
        width: 0.5rem;
        height: 0.5rem;
        background-color: #3b82f6;
        border-radius: 9999px;
        flex-shrink: 0;
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">알림</h1>
            <p class="mt-2 text-sm text-gray-600">시스템에서 발생한 알림을 확인하세요.</p>
        </div>
        
        <div class="notification-list">
            <div class="toolbar">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">
                        전체
                        @if($notifications->total() > 0)
                        <span class="text-gray-500">({{ $notifications->total() }})</span>
                        @endif
                    </button>
                    <button class="filter-tab" data-filter="unread">
                        읽지 않음
                        @if(Auth::user()->unreadNotifications()->count() > 0)
                        <span class="text-blue-600">({{ Auth::user()->unreadNotifications()->count() }})</span>
                        @endif
                    </button>
                    <button class="filter-tab" data-filter="read">
                        읽음
                        @if(Auth::user()->readNotifications()->count() > 0)
                        <span class="text-gray-500">({{ Auth::user()->readNotifications()->count() }})</span>
                        @endif
                    </button>
                </div>
                
                <div class="actions">
                    @if(Auth::user()->unreadNotifications()->count() > 0)
                    <button type="button" class="text-sm text-blue-600 hover:text-blue-800" onclick="markAllAsRead()">
                        모두 읽음 표시
                    </button>
                    @endif
                    
                    @if(Auth::user()->readNotifications()->count() > 0)
                    <button type="button" class="text-sm text-red-600 hover:text-red-800 ml-3" onclick="clearReadNotifications()">
                        읽은 알림 삭제
                    </button>
                    @endif
                </div>
            </div>
            
            <div id="notificationsList">
                @forelse($notifications as $notification)
                <div class="notification-item {{ !$notification->read_at ? 'unread' : '' }}" 
                     data-id="{{ $notification->id }}"
                     data-url="{{ $notification->data['url'] ?? '#' }}"
                     onclick="handleNotificationClick('{{ $notification->id }}', '{{ $notification->data['url'] ?? '#' }}')">
                    
                    @if(!$notification->read_at)
                    <span class="unread-dot"></span>
                    @endif
                    
                    <div class="notification-icon">
                        @php
                            $icons = [
                                'complaint_created' => 'bi-file-earmark-plus text-blue-600',
                                'complaint_assigned' => 'bi-person-check text-purple-600',
                                'complaint_commented' => 'bi-chat-dots text-green-600',
                                'complaint_status_changed' => 'bi-arrow-repeat text-orange-600',
                                'complaint_deadline' => 'bi-clock-history text-red-600'
                            ];
                            $iconClass = $icons[$notification->data['type'] ?? 'default'] ?? 'bi-bell text-gray-600';
                        @endphp
                        <i class="bi {{ $iconClass }}"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-title">
                            {{ $notification->data['title'] ?? '알림' }}
                        </div>
                        <div class="notification-message">
                            {{ $notification->data['message'] ?? '' }}
                        </div>
                        <div class="notification-time">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                    
                    <div class="notification-actions" onclick="event.stopPropagation()">
                        @if(!$notification->read_at)
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="markAsRead('{{ $notification->id }}')">
                            읽음
                        </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteNotification('{{ $notification->id }}')">
                            삭제
                        </button>
                    </div>
                </div>
                @empty
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">알림이 없습니다</h4>
                    <p class="text-sm text-gray-500">새로운 알림이 도착하면 여기에 표시됩니다.</p>
                </div>
                @endforelse
            </div>
            
            @if($notifications->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common.js') }}"></script>
<script>
// 필터 탭 전환
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // 활성 탭 변경
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // 필터링 적용
        const filter = this.dataset.filter;
        filterNotifications(filter);
    });
});

// 알림 필터링
function filterNotifications(filter) {
    const url = new URL(window.location);
    
    if (filter === 'unread') {
        url.searchParams.set('filter', 'unread');
    } else if (filter === 'read') {
        url.searchParams.set('filter', 'read');
    } else {
        url.searchParams.delete('filter');
    }
    
    window.location.href = url.toString();
}

// 알림 클릭 처리
async function handleNotificationClick(notificationId, url) {
    try {
        // 읽지 않은 알림인 경우 읽음 처리
        const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
        if (notificationItem.classList.contains('unread')) {
            await markAsRead(notificationId, false);
        }
        
        // URL로 이동
        if (url && url !== '#') {
            window.location.href = url;
        }
    } catch (error) {
        console.error('알림 클릭 처리 실패:', error);
    }
}

// 알림 읽음 처리
async function markAsRead(notificationId, showMessage = true) {
    try {
        const response = await fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // UI 업데이트
            const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
            notificationItem.classList.remove('unread');
            notificationItem.querySelector('.unread-dot')?.remove();
            
            // 읽음 버튼 제거
            const readBtn = notificationItem.querySelector('button[onclick*="markAsRead"]');
            if (readBtn) {
                readBtn.remove();
            }
            
            if (showMessage) {
                showNotification('알림을 읽음으로 표시했습니다.', 'success');
            }
        }
    } catch (error) {
        showNotification('읽음 처리에 실패했습니다.', 'error');
    }
}

// 모든 알림 읽음 처리
async function markAllAsRead() {
    if (!confirm('모든 알림을 읽음으로 표시하시겠습니까?')) {
        return;
    }
    
    try {
        const response = await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('모든 알림을 읽음으로 표시했습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showNotification('읽음 처리에 실패했습니다.', 'error');
    }
}

// 알림 삭제
async function deleteNotification(notificationId) {
    if (!confirm('이 알림을 삭제하시겠습니까?')) {
        return;
    }
    
    try {
        const response = await fetch(`/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // UI에서 제거
            const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
            notificationItem.remove();
            
            showNotification('알림이 삭제되었습니다.', 'success');
            
            // 알림이 모두 삭제된 경우 빈 상태 표시
            if (document.querySelectorAll('.notification-item').length === 0) {
                document.getElementById('notificationsList').innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-bell-slash"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">알림이 없습니다</h4>
                        <p class="text-sm text-gray-500">새로운 알림이 도착하면 여기에 표시됩니다.</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        showNotification('알림 삭제에 실패했습니다.', 'error');
    }
}

// 읽은 알림 모두 삭제
async function clearReadNotifications() {
    if (!confirm('읽은 알림을 모두 삭제하시겠습니까?')) {
        return;
    }
    
    try {
        const response = await fetch('/notifications/clear-read', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('읽은 알림이 모두 삭제되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showNotification('알림 삭제에 실패했습니다.', 'error');
    }
}

// URL 파라미터 확인하여 필터 적용
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const filter = urlParams.get('filter');
    
    if (filter) {
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.remove('active');
            if (tab.dataset.filter === filter) {
                tab.classList.add('active');
            }
        });
    }
});
</script>
@endpush
