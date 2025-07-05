{{-- resources/views/complaints/index.blade.php --}}
@extends('layouts.app')

@section('title', '민원 목록')

@push('styles')
<style>
    .complaint-card {
        transition: all 0.3s ease;
        border-left: 4px solid #dee2e6;
        cursor: pointer;
    }
    .complaint-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .complaint-card.urgent {
        border-left-color: #dc3545;
    }
    .complaint-card.high {
        border-left-color: #fd7e14;
    }
    .complaint-card.normal {
        border-left-color: #28a745;
    }
    .complaint-card.low {
        border-left-color: #6c757d;
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        position: sticky;
        top: 20px;
        z-index: 100;
    }
    
    .search-box {
        position: relative;
    }
    .search-box .form-control {
        padding-left: 45px;
        border-radius: 25px;
    }
    .search-box .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    .priority-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 0.2rem;
        font-weight: 600;
    }
    
    .bulk-actions {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        display: none;
    }
    
    .bulk-actions.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 20px;
    }
    
    .no-more-data {
        text-align: center;
        color: #6c757d;
        padding: 20px;
        font-style: italic;
    }
    
    .real-time-indicator {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.8rem;
        display: none;
        z-index: 1000;
    }
    
    .real-time-indicator.updating {
        background: #ffc107;
        color: #000;
    }
    
    .checkbox-custom {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .complaint-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .complaint-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.25rem;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        text-align: center;
    }
    
    .stat-card h5 {
        margin: 0;
        color: #495057;
        font-size: 1.5rem;
    }
    
    .stat-card p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .advanced-filters {
        display: none;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .advanced-filters.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    .filter-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 10px;
    }
    
    .filter-tag {
        background: #007bff;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .filter-tag .remove-filter {
        cursor: pointer;
        font-weight: bold;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .badge-counter {
        background: #dc3545;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 0.7rem;
        margin-left: 5px;
    }
    
    @media (max-width: 768px) {
        .filter-section {
            position: static;
            padding: 15px;
        }
        
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .complaint-actions {
            flex-direction: column;
        }
        
        .advanced-filters .row {
            margin: 0;
        }
        
        .advanced-filters .col-md-3 {
            padding: 5px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 실시간 업데이트 표시기 -->
    <div class="real-time-indicator" id="realTimeIndicator">
        <i class="bi bi-circle-fill"></i> 실시간 업데이트 중...
    </div>
    
    <!-- 페이지 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                민원 목록
                <span class="badge-counter" id="totalCount">{{ $complaints->total() }}</span>
            </h1>
            <p class="text-muted mb-0">등록된 민원을 확인하고 관리하세요</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="refreshBtn">
                <i class="bi bi-arrow-clockwise"></i> 새로고침
            </button>
            @can('create', App\Models\Complaint::class)
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> 민원 등록
            </a>
            @endcan
        </div>
    </div>
    
    <!-- 통계 카드 -->
    <div class="stats-cards">
        <div class="stat-card">
            <h5 id="statTotal">{{ $stats['total'] ?? 0 }}</h5>
            <p>전체 민원</p>
        </div>
        <div class="stat-card">
            <h5 id="statPending">{{ $stats['pending'] ?? 0 }}</h5>
            <p>대기 중</p>
        </div>
        <div class="stat-card">
            <h5 id="statInProgress">{{ $stats['in_progress'] ?? 0 }}</h5>
            <p>처리 중</p>
        </div>
        <div class="stat-card">
            <h5 id="statResolved">{{ $stats['resolved'] ?? 0 }}</h5>
            <p>해결됨</p>
        </div>
        <div class="stat-card">
            <h5 id="statUrgent">{{ $stats['urgent'] ?? 0 }}</h5>
            <p>긴급</p>
        </div>
    </div>
    
    <!-- 필터 섹션 -->
    <div class="filter-section">
        <form id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control" name="search" id="searchInput" 
                               placeholder="민원 제목, 내용, 작성자 검색..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status" id="statusFilter">
                        <option value="">전체 상태</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>대기</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>처리중</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>해결됨</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>종료</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="priority" id="priorityFilter">
                        <option value="">전체 우선순위</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>낮음</option>
                        <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>보통</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>높음</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>긴급</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="sort" id="sortFilter">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>최신순</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>오래된순</option>
                        <option value="priority" {{ request('sort') == 'priority' ? 'selected' : '' }}>우선순위순</option>
                        <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>상태순</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="toggleAdvancedFilters">
                            <i class="bi bi-funnel"></i> 고급 필터
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="clearFilters">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 고급 필터 -->
            <div class="advanced-filters" id="advancedFilters">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">카테고리</label>
                        <select class="form-select" name="category_id" id="categoryFilter">
                            <option value="">전체 카테고리</option>
                            @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">담당자</label>
                        <select class="form-select" name="assigned_to" id="assignedFilter">
                            <option value="">전체 담당자</option>
                            @foreach($assignees ?? [] as $assignee)
                            <option value="{{ $assignee->id }}" {{ request('assigned_to') == $assignee->id ? 'selected' : '' }}>
                                {{ $assignee->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">등록일 (시작)</label>
                        <input type="date" class="form-control" name="date_from" id="dateFromFilter" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">등록일 (끝)</label>
                        <input type="date" class="form-control" name="date_to" id="dateToFilter" value="{{ request('date_to') }}">
                    </div>
                </div>
            </div>
            
            <!-- 활성 필터 태그 -->
            <div class="filter-tags" id="filterTags"></div>
        </form>
    </div>
    
    <!-- 대량 작업 -->
    <div class="bulk-actions" id="bulkActions">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span id="selectedCount">0</span>개 민원이 선택되었습니다.
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="bulkStatusSelect" style="width: auto;">
                    <option value="">상태 변경</option>
                    <option value="pending">대기</option>
                    <option value="in_progress">처리중</option>
                    <option value="resolved">해결됨</option>
                    <option value="closed">종료</option>
                </select>
                <select class="form-select form-select-sm" id="bulkAssignSelect" style="width: auto;">
                    <option value="">담당자 할당</option>
                    @foreach($assignees ?? [] as $assignee)
                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary" id="applyBulkAction">적용</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelection">선택 해제</button>
            </div>
        </div>
    </div>
    
    <!-- 민원 목록 -->
    <div class="row" id="complaintsContainer">
        @forelse($complaints as $complaint)
        <div class="col-12 mb-3 complaint-item" data-id="{{ $complaint->id }}">
            <div class="card complaint-card {{ $complaint->priority }}" onclick="window.location='{{ route('complaints.show', $complaint) }}'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" class="checkbox-custom complaint-checkbox me-2" 
                                   value="{{ $complaint->id }}" onclick="event.stopPropagation()">
                            <div>
                                <h6 class="card-title mb-1">{{ $complaint->title }}</h6>
                                <p class="text-muted mb-0 small">
                                    {{ $complaint->category->name }} · 
                                    {{ $complaint->complainant->name }} · 
                                    {{ $complaint->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end">
                            <span class="status-badge badge 
                                @if($complaint->status == 'pending') bg-warning text-dark
                                @elseif($complaint->status == 'in_progress') bg-info
                                @elseif($complaint->status == 'resolved') bg-success
                                @else bg-secondary
                                @endif">
                                {{ $complaint->status_text }}
                            </span>
                            <span class="priority-badge badge 
                                @if($complaint->priority == 'urgent') bg-danger
                                @elseif($complaint->priority == 'high') bg-warning text-dark
                                @elseif($complaint->priority == 'normal') bg-success
                                @else bg-secondary
                                @endif mt-1">
                                {{ $complaint->priority_text }}
                            </span>
                        </div>
                    </div>
                    
                    <p class="card-text text-truncate mb-2">{{ $complaint->content }}</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center text-muted small">
                            @if($complaint->attachments_count > 0)
                            <i class="bi bi-paperclip me-1"></i>
                            <span class="me-2">{{ $complaint->attachments_count }}</span>
                            @endif
                            @if($complaint->comments_count > 0)
                            <i class="bi bi-chat-dots me-1"></i>
                            <span class="me-2">{{ $complaint->comments_count }}</span>
                            @endif
                            @if($complaint->assigned_to)
                            <i class="bi bi-person-check me-1"></i>
                            <span>{{ $complaint->assignedTo->name }}</span>
                            @endif
                        </div>
                        
                        <div class="complaint-actions" onclick="event.stopPropagation()">
                            @can('update', $complaint)
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="quickEdit({{ $complaint->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                            @can('delete', $complaint)
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="confirmDelete({{ $complaint->id }})">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>민원이 없습니다</h4>
                <p>등록된 민원이 없거나 검색 조건에 맞는 민원이 없습니다.</p>
                @can('create', App\Models\Complaint::class)
                <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> 첫 번째 민원 등록하기
                </a>
                @endcan
            </div>
        </div>
        @endforelse
    </div>
    
    <!-- 로딩 스피너 -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">민원을 불러오는 중...</p>
    </div>
    
    <!-- 더 이상 데이터가 없을 때 -->
    <div class="no-more-data" id="noMoreData" style="display: none;">
        모든 민원을 불러왔습니다.
    </div>
    
    <!-- 기존 페이지네이션 (무한스크롤과 함께 사용) -->
    <div class="d-flex justify-content-center mt-4" id="paginationContainer">
        {{ $complaints->withQueryString()->links() }}
    </div>
</div>

<!-- 빠른 수정 모달 -->
<div class="modal fade" id="quickEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">빠른 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickEditForm">
                    <input type="hidden" id="quickEditId">
                    <div class="mb-3">
                        <label class="form-label">상태</label>
                        <select class="form-select" id="quickEditStatus">
                            <option value="pending">대기</option>
                            <option value="in_progress">처리중</option>
                            <option value="resolved">해결됨</option>
                            <option value="closed">종료</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">우선순위</label>
                        <select class="form-select" id="quickEditPriority">
                            <option value="low">낮음</option>
                            <option value="normal">보통</option>
                            <option value="high">높음</option>
                            <option value="urgent">긴급</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">담당자</label>
                        <select class="form-select" id="quickEditAssigned">
                            <option value="">담당자 없음</option>
                            @foreach($assignees ?? [] as $assignee)
                            <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveQuickEdit">저장</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 전역 변수
let currentPage = 1;
let isLoading = false;
let hasMoreData = true;
let realTimeUpdateInterval;
let selectedComplaints = new Set();
let currentFilters = {};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeRealTimeUpdate();
    initializeInfiniteScroll();
    initializeBulkActions();
    initializeQuickEdit();
    initializeEventListeners();
});

// 필터 초기화
function initializeFilters() {
    // 현재 필터 상태 저장
    updateCurrentFilters();
    
    // 필터 태그 표시
    updateFilterTags();
    
    // 필터 변경 이벤트 리스너
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('priorityFilter').addEventListener('change', applyFilters);
    document.getElementById('sortFilter').addEventListener('change', applyFilters);
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('assignedFilter').addEventListener('change', applyFilters);
    document.getElementById('dateFromFilter').addEventListener('change', applyFilters);
    document.getElementById('dateToFilter').addEventListener('change', applyFilters);
}

// 실시간 업데이트 초기화
function initializeRealTimeUpdate() {
    realTimeUpdateInterval = setInterval(function() {
        updateComplaintsList(true);
    }, 30000); // 30초마다 업데이트
}

// 무한스크롤 초기화
function initializeInfiniteScroll() {
    window.addEventListener('scroll', function() {
        if (isLoading || !hasMoreData) return;
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        if (scrollTop + windowHeight >= documentHeight - 1000) {
            loadMoreComplaints();
        }
    });
}

// 대량 작업 초기화
function initializeBulkActions() {
    // 전체 선택 체크박스
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('complaint-checkbox')) {
            const complaintId = e.target.value;
            if (e.target.checked) {
                selectedComplaints.add(complaintId);
            } else {
                selectedComplaints.delete(complaintId);
            }
            updateBulkActions();
        }
    });
    
    // 대량 작업 적용
    document.getElementById('applyBulkAction').addEventListener('click', applyBulkAction);
    document.getElementById('clearSelection').addEventListener('click', clearSelection);
}

// 빠른 수정 초기화
function initializeQuickEdit() {
    document.getElementById('saveQuickEdit').addEventListener('click', saveQuickEdit);
}

// 이벤트 리스너 초기화
function initializeEventListeners() {
    // 새로고침 버튼
    document.getElementById('refreshBtn').addEventListener('click', function() {
        location.reload();
    });
    
    // 고급 필터 토글
    document.getElementById('toggleAdvancedFilters').addEventListener('click', function() {
        const advancedFilters = document.getElementById('advancedFilters');
        advancedFilters.classList.toggle('show');
        
        const icon = this.querySelector('i');
        if (advancedFilters.classList.contains('show')) {
            icon.className = 'bi bi-funnel-fill';
        } else {
            icon.className = 'bi bi-funnel';
        }
    });
    
    // 필터 초기화
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        currentFilters = {};
        updateFilterTags();
        applyFilters();
    });
}

// 현재 필터 상태 업데이트
function updateCurrentFilters() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    
    currentFilters = {};
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            currentFilters[key] = value;
        }
    }
}

// 필터 태그 업데이트
function updateFilterTags() {
    const filterTagsContainer = document.getElementById('filterTags');
    filterTagsContainer.innerHTML = '';
    
    const filterLabels = {
        search: '검색',
        status: '상태',
        priority: '우선순위',
        sort: '정렬',
        category_id: '카테고리',
        assigned_to: '담당자',
        date_from: '시작일',
        date_to: '종료일'
    };
    
    for (let [key, value] of Object.entries(currentFilters)) {
        const tag = document.createElement('div');
        tag.className = 'filter-tag';
        tag.innerHTML = `
            ${filterLabels[key] || key}: ${value}
            <span class="remove-filter" onclick="removeFilter('${key}')">&times;</span>
        `;
        filterTagsContainer.appendChild(tag);
    }
}

// 필터 제거
function removeFilter(filterKey) {
    const element = document.querySelector(`[name="${filterKey}"]`);
    if (element) {
        element.value = '';
        delete currentFilters[filterKey];
        updateFilterTags();
        applyFilters();
    }
}

// 필터 적용
function applyFilters() {
    updateCurrentFilters();
    updateFilterTags();
    currentPage = 1;
    hasMoreData = true;
    updateComplaintsList(false);
}

// 민원 목록 업데이트
function updateComplaintsList(isRealTimeUpdate = false) {
    if (isLoading) return;
    
    isLoading = true;
    
    if (isRealTimeUpdate) {
        document.getElementById('realTimeIndicator').style.display = 'block';
        document.getElementById('realTimeIndicator').classList.add('updating');
    } else {
        document.getElementById('loadingSpinner').style.display = 'block';
    }
    
    // API 호출
    const params = new URLSearchParams(currentFilters);
    params.append('page', currentPage);
    params.append('per_page', 20);
    
    fetch(`/api/complaints?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (currentPage === 1) {
                document.getElementById('complaintsContainer').innerHTML = '';
            }
            
            appendComplaints(data.data.data);
            updateStats(data.data.meta);
            
            if (data.data.data.length < 20) {
                hasMoreData = false;
                document.getElementById('noMoreData').style.display = 'block';
            }
            
            if (isRealTimeUpdate) {
                showNotification('민원 목록이 업데이트되었습니다.', 'success');
            }
        } else {
            showNotification('민원 목록을 불러오는데 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    })
    .finally(() => {
        isLoading = false;
        document.getElementById('loadingSpinner').style.display = 'none';
        
        if (isRealTimeUpdate) {
            setTimeout(() => {
                document.getElementById('realTimeIndicator').style.display = 'none';
                document.getElementById('realTimeIndicator').classList.remove('updating');
            }, 2000);
        }
    });
}

// 더 많은 민원 로드
function loadMoreComplaints() {
    if (isLoading || !hasMoreData) return;
    
    currentPage++;
    updateComplaintsList(false);
}

// 민원 추가
function appendComplaints(complaints) {
    const container = document.getElementById('complaintsContainer');
    
    complaints.forEach(complaint => {
        const complaintHTML = createComplaintHTML(complaint);
        container.insertAdjacentHTML('beforeend', complaintHTML);
    });
}

// 민원 HTML 생성
function createComplaintHTML(complaint) {
    const statusBadgeClass = {
        'pending': 'bg-warning text-dark',
        'in_progress': 'bg-info',
        'resolved': 'bg-success',
        'closed': 'bg-secondary'
    };
    
    const priorityBadgeClass = {
        'urgent': 'bg-danger',
        'high': 'bg-warning text-dark',
        'normal': 'bg-success',
        'low': 'bg-secondary'
    };
    
    return `
        <div class="col-12 mb-3 complaint-item" data-id="${complaint.id}">
            <div class="card complaint-card ${complaint.priority}" onclick="window.location='/complaints/${complaint.id}'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" class="checkbox-custom complaint-checkbox me-2" 
                                   value="${complaint.id}" onclick="event.stopPropagation()">
                            <div>
                                <h6 class="card-title mb-1">${complaint.title}</h6>
                                <p class="text-muted mb-0 small">
                                    ${complaint.category.name} · 
                                    ${complaint.complainant.name} · 
                                    ${new Date(complaint.created_at).toLocaleDateString('ko-KR')}
                                </p>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end">
                            <span class="status-badge badge ${statusBadgeClass[complaint.status] || 'bg-secondary'}">
                                ${complaint.status_text}
                            </span>
                            <span class="priority-badge badge ${priorityBadgeClass[complaint.priority] || 'bg-secondary'} mt-1">
                                ${complaint.priority_text}
                            </span>
                        </div>
                    </div>
                    
                    <p class="card-text text-truncate mb-2">${complaint.content}</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center text-muted small">
                            ${complaint.attachments_count > 0 ? `<i class="bi bi-paperclip me-1"></i><span class="me-2">${complaint.attachments_count}</span>` : ''}
                            ${complaint.comments_count > 0 ? `<i class="bi bi-chat-dots me-1"></i><span class="me-2">${complaint.comments_count}</span>` : ''}
                            ${complaint.assigned_to ? `<i class="bi bi-person-check me-1"></i><span>${complaint.assigned_to.name}</span>` : ''}
                        </div>
                        
                        <div class="complaint-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="quickEdit(${complaint.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="confirmDelete(${complaint.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// 통계 업데이트
function updateStats(meta) {
    if (meta.statistics) {
        document.getElementById('statTotal').textContent = meta.statistics.total || 0;
        document.getElementById('statPending').textContent = meta.statistics.pending || 0;
        document.getElementById('statInProgress').textContent = meta.statistics.in_progress || 0;
        document.getElementById('statResolved').textContent = meta.statistics.resolved || 0;
        document.getElementById('statUrgent').textContent = meta.statistics.urgent || 0;
        document.getElementById('totalCount').textContent = meta.statistics.total || 0;
    }
}

// 대량 작업 UI 업데이트
function updateBulkActions() {
    const count = selectedComplaints.size;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = count;
    
    if (count > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
}

// 대량 작업 적용
function applyBulkAction() {
    const status = document.getElementById('bulkStatusSelect').value;
    const assignedTo = document.getElementById('bulkAssignSelect').value;
    
    if (!status && !assignedTo) {
        showNotification('변경할 상태 또는 담당자를 선택해주세요.', 'warning');
        return;
    }
    
    if (selectedComplaints.size === 0) {
        showNotification('선택된 민원이 없습니다.', 'warning');
        return;
    }
    
    if (!confirm(`선택된 ${selectedComplaints.size}개의 민원을 수정하시겠습니까?`)) {
        return;
    }
    
    const data = {
        complaint_ids: Array.from(selectedComplaints),
        status: status,
        assigned_to: assignedTo
    };
    
    fetch('/api/complaints/bulk-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('대량 작업이 완료되었습니다.', 'success');
            clearSelection();
            updateComplaintsList(false);
        } else {
            showNotification('대량 작업에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 선택 해제
function clearSelection() {
    selectedComplaints.clear();
    document.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

// 빠른 수정
function quickEdit(complaintId) {
    // 민원 정보 가져오기
    fetch(`/api/complaints/${complaintId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const complaint = data.data;
            document.getElementById('quickEditId').value = complaint.id;
            document.getElementById('quickEditStatus').value = complaint.status;
            document.getElementById('quickEditPriority').value = complaint.priority;
            document.getElementById('quickEditAssigned').value = complaint.assigned_to || '';
            
            new bootstrap.Modal(document.getElementById('quickEditModal')).show();
        } else {
            showNotification('민원 정보를 불러오는데 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 빠른 수정 저장
function saveQuickEdit() {
    const complaintId = document.getElementById('quickEditId').value;
    const status = document.getElementById('quickEditStatus').value;
    const priority = document.getElementById('quickEditPriority').value;
    const assignedTo = document.getElementById('quickEditAssigned').value;
    
    const data = {
        status: status,
        priority: priority,
        assigned_to: assignedTo || null
    };
    
    fetch(`/api/complaints/${complaintId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('민원이 수정되었습니다.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('quickEditModal')).hide();
            updateComplaintsList(false);
        } else {
            showNotification('민원 수정에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 삭제 확인
function confirmDelete(complaintId) {
    if (!confirm('정말로 이 민원을 삭제하시겠습니까?')) {
        return;
    }
    
    fetch(`/api/complaints/${complaintId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('민원이 삭제되었습니다.', 'success');
            document.querySelector(`[data-id="${complaintId}"]`).remove();
            updateComplaintsList(false);
        } else {
            showNotification('민원 삭제에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 알림 표시
function showNotification(message, type = 'info') {
    // 간단한 알림 표시 (나중에 Toast로 개선 가능)
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // 5초 후 자동 제거
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// 디바운스 함수
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 페이지 종료 시 정리
window.addEventListener('beforeunload', function() {
    if (realTimeUpdateInterval) {
        clearInterval(realTimeUpdateInterval);
    }
});
</script>
@endpush