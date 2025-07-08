@extends('layouts.app')

@section('title', '알림')

@push('styles')
<style>
    .notification-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .notification-item:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .notification-item.unread {
        background-color: #f8f9ff;
        border-left: 4px solid #007bff;
    }
    
    .notification-item.read {
        background-color: #f8f9fa;
        opacity: 0.8;
    }
    
    .notification-type {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .notification-type.complaint_created {
        color: #28a745;
    }
    
    .notification-type.complaint_assigned {
        color: #007bff;
    }
    
    .notification-type.complaint_status_changed {
        color: #ffc107;
    }
    
    .notification-type.complaint_resolved {
        color: #17a2b8;
    }
    
    .notification-type.complaint_comment_added {
        color: #6f42c1;
    }
    
    .notification-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .notification-message {
        color: #6c757d;
        margin-bottom: 10px;
    }
    
    .notification-time {
        font-size: 0.85rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .notification-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .stats-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #007bff;
    }
    
    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .filter-section {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">알림</h1>
            <p class="text-muted mb-0">받은 알림을 확인하고 관리합니다</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="markAllAsRead()">
                <i class="bi bi-check-all"></i> 모두 읽음 처리
            </button>
            <button class="btn btn-outline-danger" onclick="clearReadNotifications()">
                <i class="bi bi-trash"></i> 읽은 알림 삭제
            </button>
        </div>
    </div>

    <div class="row">
        <!-- 통계 카드 -->
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <div class="stats-number">{{ $stats['total'] }}</div>
                <div class="stats-label">총 알림</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <div class="stats-number text-primary">{{ $stats['unread'] }}</div>
                <div class="stats-label">읽지 않음</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <div class="stats-number text-success">{{ $stats['read'] }}</div>
                <div class="stats-label">읽음</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <div class="stats-number text-warning">{{ $stats['by_type']['complaint_created'] ?? 0 }}</div>
                <div class="stats-label">민원 생성</div>
            </div>
        </div>
    </div>

    <!-- 필터 섹션 -->
    <div class="filter-section">
        <form method="GET" action="{{ route('notifications.index') }}">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">읽음 상태</label>
                    <select class="form-select" name="read_status">
                        <option value="">전체</option>
                        <option value="unread" {{ request('read_status') === 'unread' ? 'selected' : '' }}>읽지 않음</option>
                        <option value="read" {{ request('read_status') === 'read' ? 'selected' : '' }}>읽음</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">알림 타입</label>
                    <select class="form-select" name="type">
                        <option value="">전체</option>
                        <option value="complaint_created" {{ request('type') === 'complaint_created' ? 'selected' : '' }}>민원 생성</option>
                        <option value="complaint_assigned" {{ request('type') === 'complaint_assigned' ? 'selected' : '' }}>민원 할당</option>
                        <option value="complaint_status_changed" {{ request('type') === 'complaint_status_changed' ? 'selected' : '' }}>상태 변경</option>
                        <option value="complaint_resolved" {{ request('type') === 'complaint_resolved' ? 'selected' : '' }}>민원 해결</option>
                        <option value="complaint_comment_added" {{ request('type') === 'complaint_comment_added' ? 'selected' : '' }}>댓글 추가</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">시작일</label>
                    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">종료일</label>
                    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> 필터 적용
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 알림 목록 -->
    <div class="row">
        <div class="col-12">
            @if($notifications->count() > 0)
                @foreach($notifications as $notification)
                <div class="notification-item {{ $notification->read_at ? 'read' : 'unread' }}" 
                     onclick="handleNotificationClick({{ $notification->id }}, '{{ $notification->action_url }}')">
                    <div class="notification-type {{ $notification->type }}">
                        {{ $notification->type }}
                    </div>
                    <div class="notification-title">
                        {{ $notification->title }}
                    </div>
                    <div class="notification-message">
                        {{ $notification->message }}
                    </div>
                    <div class="notification-time">
                        <i class="bi bi-clock"></i>
                        {{ $notification->created_at->diffForHumans() }}
                        @if(!$notification->read_at)
                            <span class="badge bg-primary ms-2">새로운 알림</span>
                        @endif
                    </div>
                    <div class="notification-actions">
                        @if(!$notification->read_at)
                            <button class="btn btn-sm btn-outline-primary" onclick="markAsRead({{ $notification->id }}, event)">
                                <i class="bi bi-check"></i> 읽음 처리
                            </button>
                        @endif
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification({{ $notification->id }}, event)">
                            <i class="bi bi-trash"></i> 삭제
                        </button>
                        @if($notification->action_url)
                            <a href="{{ $notification->action_url }}" class="btn btn-sm btn-primary" onclick="event.stopPropagation()">
                                <i class="bi bi-arrow-right"></i> 바로가기
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach

                <!-- 페이지네이션 -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h4>알림이 없습니다</h4>
                    <p>새로운 알림이 오면 여기에 표시됩니다.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 알림 클릭 처리
function handleNotificationClick(notificationId, actionUrl) {
    // 읽음 처리
    if (actionUrl) {
        markAsRead(notificationId, null, function() {
            window.location.href = actionUrl;
        });
    } else {
        markAsRead(notificationId);
    }
}

// 알림을 읽음으로 표시
function markAsRead(notificationId, event = null, callback = null) {
    if (event) {
        event.stopPropagation();
    }

    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 알림 상태 업데이트
            const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
                
                // 새로운 알림 뱃지 제거
                const badge = notificationElement.querySelector('.badge');
                if (badge) {
                    badge.remove();
                }
                
                // 읽음 처리 버튼 제거
                const readButton = notificationElement.querySelector('.btn-outline-primary');
                if (readButton) {
                    readButton.remove();
                }
            }
            
            if (callback) {
                callback();
            }
        } else {
            alert('알림 처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    });
}

// 모든 알림을 읽음으로 표시
function markAllAsRead() {
    if (!confirm('모든 알림을 읽음으로 표시하시겠습니까?')) {
        return;
    }

    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    });
}

// 알림 삭제
function deleteNotification(notificationId, event) {
    event.stopPropagation();
    
    if (!confirm('이 알림을 삭제하시겠습니까?')) {
        return;
    }

    fetch(`/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.remove();
            }
        } else {
            alert('삭제 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    });
}

// 읽은 알림 모두 삭제
function clearReadNotifications() {
    if (!confirm('읽은 알림을 모두 삭제하시겠습니까?')) {
        return;
    }

    fetch('/notifications/clear-read', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    });
}
</script>
@endpush
