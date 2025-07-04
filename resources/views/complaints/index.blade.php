{{-- resources/views/complaints/index.blade.php --}}
@extends('layouts.app')

@section('title', '민원 목록')

@push('styles')
<style>
    .complaint-card {
        transition: all 0.3s ease;
        border-left: 4px solid #dee2e6;
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
    }
    
    .search-box {
        position: relative;
    }
    .search-box .form-control {
        padding-left: 45px;
    }
    .search-box .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .bulk-actions {
        background: #e3f2fd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        display: none;
    }
    
    .table-responsive {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .floating-refresh {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
    }
    
    .priority-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 100;
    }
    
    @media (max-width: 768px) {
        .filter-section {
            padding: 15px;
        }
        .table-responsive {
            font-size: 0.9rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">민원 목록</h2>
            <p class="text-muted mb-0">
                <span id="total-count">{{ $complaints->total() }}</span>개의 민원 
                <span class="text-primary" id="filtered-count" style="display: none;"></span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" id="refresh-btn" title="새로고침">
                <i class="fas fa-sync-alt"></i>
            </button>
            @if(auth()->user()->role === 'parent')
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 새 민원 등록
            </a>
            @endif
        </div>
    </div>

    <!-- 필터 섹션 -->
    <div class="filter-section">
        <form id="filter-form">
            <div class="row g-3">
                <!-- 검색 -->
                <div class="col-md-4">
                    <label class="form-label">검색</label>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="제목, 내용, 민원인명으로 검색..." value="{{ request('search') }}">
                    </div>
                </div>
                
                <!-- 상태 필터 -->
                <div class="col-md-2">
                    <label class="form-label">상태</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">전체</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>접수 완료</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>처리 중</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>해결 완료</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>종료</option>
                    </select>
                </div>
                
                <!-- 카테고리 필터 -->
                <div class="col-md-2">
                    <label class="form-label">카테고리</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">전체</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- 우선순위 필터 -->
                <div class="col-md-2">
                    <label class="form-label">우선순위</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">전체</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>낮음</option>
                        <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>보통</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>높음</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>긴급</option>
                    </select>
                </div>
                
                <!-- 담당자 필터 -->
                @if(auth()->user()->role === 'admin')
                <div class="col-md-2">
                    <label class="form-label">담당자</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">전체</option>
                        <option value="unassigned" {{ request('assigned_to') == 'unassigned' ? 'selected' : '' }}>미할당</option>
                        @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            
            <!-- 날짜 범위 및 정렬 -->
            <div class="row g-3 mt-3">
                <div class="col-md-3">
                    <label class="form-label">시작일</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">종료일</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">정렬 기준</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>등록일</option>
                        <option value="updated_at" {{ request('sort_by') == 'updated_at' ? 'selected' : '' }}>수정일</option>
                        <option value="priority" {{ request('sort_by') == 'priority' ? 'selected' : '' }}>우선순위</option>
                        <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>상태</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">정렬 순서</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>내림차순</option>
                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>오름차순</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" id="reset-filters">
                        <i class="fas fa-undo"></i> 초기화
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 대량 작업 패널 (관리자만) -->
    @if(in_array(auth()->user()->role, ['admin', 'teacher', 'security_staff', 'ops_staff']))
    <div class="bulk-actions" id="bulk-actions">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><span id="selected-count">0</span>개의 민원이 선택됨</strong>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="bulk-status" style="width: auto;">
                    <option value="">상태 변경</option>
                    <option value="in_progress">처리 중</option>
                    <option value="resolved">해결 완료</option>
                    <option value="closed">종료</option>
                </select>
                @if(auth()->user()->role === 'admin')
                <select class="form-select form-select-sm" id="bulk-assign" style="width: auto;">
                    <option value="">담당자 할당</option>
                    @foreach($assignableUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @endif
                <button type="button" class="btn btn-sm btn-primary" id="apply-bulk-actions">적용</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cancel-bulk-selection">취소</button>
            </div>
        </div>
    </div>
    @endif

    <!-- 민원 목록 테이블 -->
    <div class="position-relative">
        <div class="loading-overlay" id="loading-overlay" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        @if(in_array(auth()->user()->role, ['admin', 'teacher', 'security_staff', 'ops_staff']))
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="select-all">
                        </th>
                        @endif
                        <th width="120">민원번호</th>
                        <th>제목</th>
                        <th width="100">민원인</th>
                        <th width="120">카테고리</th>
                        <th width="100">상태</th>
                        <th width="80">우선순위</th>
                        @if(auth()->user()->role === 'admin')
                        <th width="100">담당자</th>
                        @endif
                        <th width="120">등록일</th>
                        <th width="80">작업</th>
                    </tr>
                </thead>
                <tbody id="complaints-tbody">
                    @include('complaints.partials.table-rows')
                </tbody>
            </table>
        </div>
    </div>

    <!-- 페이지네이션 -->
    @if($complaints->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $complaints->withQueryString()->links() }}
    </div>
    @endif

    <!-- 플로팅 새로고침 버튼 -->
    <div class="floating-refresh">
        <button class="btn btn-primary rounded-circle" id="floating-refresh" title="실시간 업데이트" style="width: 50px; height: 50px;">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

@include('complaints.modals.quick-actions')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 전역 변수
    let refreshInterval;
    let isLoading = false;
    
    // DOM 요소들
    const filterForm = document.getElementById('filter-form');
    const loadingOverlay = document.getElementById('loading-overlay');
    const complaintsTable = document.getElementById('complaints-tbody');
    const selectAllCheckbox = document.getElementById('select-all');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    // 초기화
    initializeEventListeners();
    startAutoRefresh();
    
    // 이벤트 리스너 초기화
    function initializeEventListeners() {
        // 필터 폼 이벤트
        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('change', debounce(applyFilters, 300));
        });
        
        // 검색 입력 실시간 처리
        document.getElementById('search').addEventListener('input', debounce(applyFilters, 500));
        
        // 필터 초기화
        document.getElementById('reset-filters').addEventListener('click', resetFilters);
        
        // 새로고침 버튼
        document.getElementById('refresh-btn').addEventListener('click', refreshData);
        document.getElementById('floating-refresh').addEventListener('click', refreshData);
        
        // 체크박스 이벤트 (관리자만)
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', toggleSelectAll);
        }
        
        // 대량 작업 이벤트
        const applyBulkBtn = document.getElementById('apply-bulk-actions');
        const cancelBulkBtn = document.getElementById('cancel-bulk-selection');
        
        if (applyBulkBtn) {
            applyBulkBtn.addEventListener('click', applyBulkActions);
        }
        if (cancelBulkBtn) {
            cancelBulkBtn.addEventListener('click', cancelBulkSelection);
        }
        
        // 빠른 작업 이벤트
        document.addEventListener('click', handleQuickActions);
    }
    
    // 필터 적용
    function applyFilters() {
        if (isLoading) return;
        
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        // AJAX 요청으로 필터된 데이터 가져오기
        fetchFilteredData(params.toString());
    }
    
    // 필터된 데이터 가져오기
    async function fetchFilteredData(queryString) {
        showLoading();
        
        try {
            const response = await fetch(`{{ route('complaints.index') }}?${queryString}&ajax=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('네트워크 오류');
            
            const data = await response.json();
            updateTable(data.html);
            updatePagination(data.pagination);
            updateCounts(data.total, data.filtered);
            
            // URL 업데이트
            const newUrl = `{{ route('complaints.index') }}?${queryString}`;
            window.history.pushState({}, '', newUrl);
            
        } catch (error) {
            console.error('데이터 로딩 오류:', error);
            showError('데이터를 불러오는 중 오류가 발생했습니다.');
        } finally {
            hideLoading();
        }
    }
    
    // 테이블 업데이트
    function updateTable(html) {
        complaintsTable.innerHTML = html;
        
        // 체크박스 이벤트 재등록
        const checkboxes = complaintsTable.querySelectorAll('.complaint-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });
    }
    
    // 페이지네이션 업데이트
    function updatePagination(paginationHtml) {
        const paginationContainer = document.querySelector('.pagination')?.parentElement;
        if (paginationContainer) {
            paginationContainer.innerHTML = paginationHtml;
        }
    }
    
    // 카운트 업데이트
    function updateCounts(total, filtered) {
        document.getElementById('total-count').textContent = total;
        const filteredElement = document.getElementById('filtered-count');
        
        if (filtered !== total) {
            filteredElement.textContent = `(필터링: ${filtered}개)`;
            filteredElement.style.display = 'inline';
        } else {
            filteredElement.style.display = 'none';
        }
    }
    
    // 필터 초기화
    function resetFilters() {
        filterForm.reset();
        window.location.href = '{{ route('complaints.index') }}';
    }
    
    // 데이터 새로고침
    async function refreshData() {
        const currentParams = new URLSearchParams(window.location.search);
        await fetchFilteredData(currentParams.toString());
        
        // 새로고침 버튼 애니메이션
        const refreshBtn = document.getElementById('floating-refresh');
        refreshBtn.querySelector('i').classList.add('fa-spin');
        setTimeout(() => {
            refreshBtn.querySelector('i').classList.remove('fa-spin');
        }, 1000);
        
        showSuccess('데이터가 업데이트되었습니다.');
    }
    
    // 자동 새로고침 시작
    function startAutoRefresh() {
        refreshInterval = setInterval(refreshData, 30000); // 30초마다
    }
    
    // 전체 선택/해제
    function toggleSelectAll() {
        const checkboxes = document.querySelectorAll('.complaint-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateBulkActions();
    }
    
    // 대량 작업 UI 업데이트
    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.complaint-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (bulkActions) {
            if (count > 0) {
                bulkActions.style.display = 'block';
            } else {
                bulkActions.style.display = 'none';
            }
        }
        
        // 전체 선택 체크박스 상태 업데이트
        if (selectAllCheckbox) {
            const totalCheckboxes = document.querySelectorAll('.complaint-checkbox');
            selectAllCheckbox.checked = count === totalCheckboxes.length && count > 0;
            selectAllCheckbox.indeterminate = count > 0 && count < totalCheckboxes.length;
        }
    }
    
    // 대량 작업 적용
    async function applyBulkActions() {
        const checkedBoxes = document.querySelectorAll('.complaint-checkbox:checked');
        const complaintIds = Array.from(checkedBoxes).map(cb => cb.value);
        
        const bulkStatus = document.getElementById('bulk-status').value;
        const bulkAssignElement = document.getElementById('bulk-assign');
        const bulkAssign = bulkAssignElement ? bulkAssignElement.value : '';
        
        if (!bulkStatus && !bulkAssign) {
            showError('변경할 상태나 담당자를 선택해주세요.');
            return;
        }
        
        if (!confirm(`선택된 ${complaintIds.length}개의 민원에 변경사항을 적용하시겠습니까?`)) {
            return;
        }
        
        showLoading();
        
        try {
            const response = await fetch('{{ route('complaints.bulk-update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    complaint_ids: complaintIds,
                    status: bulkStatus,
                    assigned_to: bulkAssign
                })
            });
            
            if (!response.ok) throw new Error('요청 실패');
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess(`${complaintIds.length}개의 민원이 성공적으로 업데이트되었습니다.`);
                cancelBulkSelection();
                refreshData();
            } else {
                throw new Error(result.message || '업데이트 실패');
            }
            
        } catch (error) {
            console.error('대량 업데이트 오류:', error);
            showError('대량 업데이트 중 오류가 발생했습니다.');
        } finally {
            hideLoading();
        }
    }
    
    // 대량 선택 취소
    function cancelBulkSelection() {
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        document.querySelectorAll('.complaint-checkbox').forEach(cb => cb.checked = false);
        
        const bulkStatusEl = document.getElementById('bulk-status');
        const bulkAssignEl = document.getElementById('bulk-assign');
        
        if (bulkStatusEl) bulkStatusEl.value = '';
        if (bulkAssignEl) bulkAssignEl.value = '';
        
        updateBulkActions();
    }
    
    // 빠른 작업 처리
    function handleQuickActions(e) {
        if (e.target.closest('.quick-status')) {
            e.preventDefault();
            const complaintId = e.target.closest('.quick-status').dataset.id;
            openStatusModal(complaintId);
        } else if (e.target.closest('.quick-assign')) {
            e.preventDefault();
            const complaintId = e.target.closest('.quick-assign').dataset.id;
            openAssignModal(complaintId);
        }
    }
    
    // 유틸리티 함수들
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
    
    function showLoading() {
        isLoading = true;
        loadingOverlay.style.display = 'flex';
    }
    
    function hideLoading() {
        isLoading = false;
        loadingOverlay.style.display = 'none';
    }
    
    function showSuccess(message) {
        const toast = createToast(message, 'success');
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    function showError(message) {
        const toast = createToast(message, 'error');
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
    
    function createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        return toast;
    }
    
    // 페이지 언로드 시 자동 새로고침 정리
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
    
    // 체크박스 이벤트 초기 등록
    document.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
});
</script>
@endpush
