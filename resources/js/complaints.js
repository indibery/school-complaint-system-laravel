// resources/js/complaints.js

// 전역 설정
window.ComplaintSystem = {
    updateInterval: 30000, // 30초
    isUpdating: false,
    selectedItems: new Set(),
    currentPage: 1,
    hasMoreData: true,
    isLoading: false
};

// 공통 유틸리티 함수들
const Utils = {
    // 디바운스 함수
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // 알림 표시
    showNotification: function(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show notification-toast" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // 알림 컨테이너가 없으면 생성
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1050; max-width: 350px;';
            document.body.appendChild(container);
        }
        
        container.insertAdjacentHTML('beforeend', alertHTML);
        
        // 5초 후 자동 제거
        const alert = container.lastElementChild;
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    },

    // CSRF 토큰 가져오기
    getCsrfToken: function() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    },

    // Ajax 요청 래퍼
    ajax: function(url, options = {}) {
        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': Utils.getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (options.body && !(options.body instanceof FormData)) {
            defaultOptions.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        return fetch(url, { ...defaultOptions, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Ajax error:', error);
                Utils.showNotification('네트워크 오류가 발생했습니다.', 'error');
                throw error;
            });
    },

    // 날짜 포맷
    formatDate: function(date, format = 'Y-m-d H:i') {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');

        return format
            .replace('Y', year)
            .replace('m', month)
            .replace('d', day)
            .replace('H', hours)
            .replace('i', minutes)
            .replace('s', seconds);
    },

    // 파일 크기 포맷
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // 파일 아이콘 결정
    getFileIcon: function(mimeType) {
        if (mimeType.startsWith('image/')) return 'bi-file-image';
        if (mimeType.includes('pdf')) return 'bi-file-pdf';
        if (mimeType.includes('word')) return 'bi-file-word';
        if (mimeType.includes('excel')) return 'bi-file-excel';
        if (mimeType.includes('powerpoint')) return 'bi-file-ppt';
        return 'bi-file-earmark';
    }
};

// 민원 목록 관련 함수들
const ComplaintList = {
    // 초기화
    init: function() {
        this.initializeFilters();
        this.initializeRealTimeUpdate();
        this.initializeInfiniteScroll();
        this.initializeBulkActions();
        this.initializeEventListeners();
    },

    // 필터 초기화
    initializeFilters: function() {
        const filterInputs = ['searchInput', 'statusFilter', 'priorityFilter', 'sortFilter', 
                             'categoryFilter', 'assignedFilter', 'dateFromFilter', 'dateToFilter'];
        
        filterInputs.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                const eventType = id === 'searchInput' ? 'input' : 'change';
                const handler = id === 'searchInput' 
                    ? Utils.debounce(() => this.applyFilters(), 300)
                    : () => this.applyFilters();
                
                element.addEventListener(eventType, handler);
            }
        });
    },

    // 실시간 업데이트 초기화
    initializeRealTimeUpdate: function() {
        // 초기 실행
        this.checkForUpdates();
        
        // 주기적 실행
        setInterval(() => {
            this.checkForUpdates();
        }, window.ComplaintSystem.updateInterval);
    },

    // 업데이트 확인
    checkForUpdates: function() {
        if (window.ComplaintSystem.isUpdating) return;
        
        window.ComplaintSystem.isUpdating = true;
        const indicator = document.getElementById('realTimeIndicator');
        if (indicator) {
            indicator.style.display = 'block';
            indicator.classList.add('updating');
        }

        // 현재 필터 파라미터 가져오기
        const params = this.getCurrentFilters();
        params.append('check_updates', 'true');

        Utils.ajax(`/api/complaints?${params.toString()}`)
            .then(data => {
                if (data.success && data.has_updates) {
                    this.updateComplaintsList(false);
                    Utils.showNotification('새로운 민원이 업데이트되었습니다.', 'info');
                }
            })
            .finally(() => {
                window.ComplaintSystem.isUpdating = false;
                if (indicator) {
                    setTimeout(() => {
                        indicator.style.display = 'none';
                        indicator.classList.remove('updating');
                    }, 2000);
                }
            });
    },

    // 무한스크롤 초기화
    initializeInfiniteScroll: function() {
        let scrollTimeout;
        
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                if (window.ComplaintSystem.isLoading || !window.ComplaintSystem.hasMoreData) return;
                
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                
                // 페이지 하단 1000px 전에 로드 시작
                if (scrollTop + windowHeight >= documentHeight - 1000) {
                    this.loadMoreComplaints();
                }
            }, 100);
        });
    },

    // 더 많은 민원 로드
    loadMoreComplaints: function() {
        if (window.ComplaintSystem.isLoading || !window.ComplaintSystem.hasMoreData) return;
        
        window.ComplaintSystem.currentPage++;
        this.updateComplaintsList(true);
    },

    // 현재 필터 가져오기
    getCurrentFilters: function() {
        const params = new URLSearchParams();
        const filterForm = document.getElementById('filterForm');
        
        if (filterForm) {
            const formData = new FormData(filterForm);
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    params.append(key, value);
                }
            }
        }
        
        params.append('page', window.ComplaintSystem.currentPage);
        params.append('per_page', 20);
        
        return params;
    },

    // 필터 적용
    applyFilters: function() {
        window.ComplaintSystem.currentPage = 1;
        window.ComplaintSystem.hasMoreData = true;
        this.updateComplaintsList(false);
        this.updateFilterTags();
    },

    // 민원 목록 업데이트
    updateComplaintsList: function(append = false) {
        if (window.ComplaintSystem.isLoading) return;
        
        window.ComplaintSystem.isLoading = true;
        document.getElementById('loadingSpinner').style.display = 'block';
        
        const params = this.getCurrentFilters();
        
        Utils.ajax(`/api/complaints?${params.toString()}`)
            .then(data => {
                if (data.success) {
                    if (!append) {
                        document.getElementById('complaintsContainer').innerHTML = '';
                        window.ComplaintSystem.selectedItems.clear();
                        this.updateBulkActions();
                    }
                    
                    if (data.data.data.length > 0) {
                        this.renderComplaints(data.data.data);
                    } else if (!append) {
                        this.renderEmptyState();
                    }
                    
                    if (data.data.data.length < 20) {
                        window.ComplaintSystem.hasMoreData = false;
                        document.getElementById('noMoreData').style.display = 'block';
                    }
                    
                    this.updateStats(data.data.meta);
                } else {
                    Utils.showNotification('민원 목록을 불러오는데 실패했습니다.', 'error');
                }
            })
            .finally(() => {
                window.ComplaintSystem.isLoading = false;
                document.getElementById('loadingSpinner').style.display = 'none';
            });
    },

    // 민원 렌더링
    renderComplaints: function(complaints) {
        const container = document.getElementById('complaintsContainer');
        
        complaints.forEach(complaint => {
            const html = this.createComplaintHTML(complaint);
            container.insertAdjacentHTML('beforeend', html);
        });
        
        // 체크박스 이벤트 재바인딩
        container.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const complaintId = e.target.value;
                if (e.target.checked) {
                    window.ComplaintSystem.selectedItems.add(complaintId);
                } else {
                    window.ComplaintSystem.selectedItems.delete(complaintId);
                }
                this.updateBulkActions();
            });
        });
    },

    // 민원 HTML 생성
    createComplaintHTML: function(complaint) {
        const statusClass = {
            'pending': 'bg-warning text-dark',
            'in_progress': 'bg-info',
            'resolved': 'bg-success',
            'closed': 'bg-secondary'
        }[complaint.status] || 'bg-secondary';
        
        const priorityClass = {
            'urgent': 'bg-danger',
            'high': 'bg-warning text-dark',
            'normal': 'bg-success',
            'low': 'bg-secondary'
        }[complaint.priority] || 'bg-secondary';
        
        return `
            <div class="col-12 mb-3 complaint-item" data-id="${complaint.id}">
                <div class="card complaint-card ${complaint.priority}" onclick="window.location='/complaints/${complaint.id}'">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" class="checkbox-custom complaint-checkbox me-2" 
                                       value="${complaint.id}" onclick="event.stopPropagation()">
                                <div>
                                    <h6 class="card-title mb-1">${this.escapeHtml(complaint.title)}</h6>
                                    <p class="text-muted mb-0 small">
                                        ${complaint.category?.name || ''} · 
                                        ${complaint.complainant?.name || complaint.user?.name || ''} · 
                                        ${Utils.formatDate(complaint.created_at)}
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-end">
                                <span class="status-badge badge ${statusClass}">
                                    ${complaint.status_text || complaint.status}
                                </span>
                                <span class="priority-badge badge ${priorityClass} mt-1">
                                    ${complaint.priority_text || complaint.priority}
                                </span>
                            </div>
                        </div>
                        
                        <p class="card-text text-truncate mb-2">${this.escapeHtml(complaint.content || complaint.description || '')}</p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center text-muted small">
                                ${complaint.attachments_count > 0 ? `
                                    <i class="bi bi-paperclip me-1"></i>
                                    <span class="me-2">${complaint.attachments_count}</span>
                                ` : ''}
                                ${complaint.comments_count > 0 ? `
                                    <i class="bi bi-chat-dots me-1"></i>
                                    <span class="me-2">${complaint.comments_count}</span>
                                ` : ''}
                                ${complaint.assigned_to ? `
                                    <i class="bi bi-person-check me-1"></i>
                                    <span>${complaint.assigned_to.name || ''}</span>
                                ` : ''}
                            </div>
                            
                            <div class="complaint-actions" onclick="event.stopPropagation()">
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="ComplaintList.quickEdit(${complaint.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="ComplaintList.confirmDelete(${complaint.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    // 빈 상태 렌더링
    renderEmptyState: function() {
        const container = document.getElementById('complaintsContainer');
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>민원이 없습니다</h4>
                    <p>등록된 민원이 없거나 검색 조건에 맞는 민원이 없습니다.</p>
                    <a href="/complaints/create" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> 첫 번째 민원 등록하기
                    </a>
                </div>
            </div>
        `;
    },

    // HTML 이스케이프
    escapeHtml: function(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    // 통계 업데이트
    updateStats: function(meta) {
        if (meta && meta.statistics) {
            ['total', 'pending', 'in_progress', 'resolved', 'urgent'].forEach(stat => {
                const element = document.getElementById(`stat${stat.charAt(0).toUpperCase() + stat.slice(1)}`);
                if (element && meta.statistics[stat] !== undefined) {
                    element.textContent = meta.statistics[stat];
                }
            });
            
            const totalCount = document.getElementById('totalCount');
            if (totalCount) {
                totalCount.textContent = meta.statistics.total || 0;
            }
        }
    },

    // 대량 작업 초기화
    initializeBulkActions: function() {
        const applyBtn = document.getElementById('applyBulkAction');
        const clearBtn = document.getElementById('clearSelection');
        
        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.applyBulkAction());
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearSelection());
        }
    },

    // 대량 작업 UI 업데이트
    updateBulkActions: function() {
        const count = window.ComplaintSystem.selectedItems.size;
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (bulkActions) {
            if (count > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }
    },

    // 대량 작업 적용
    applyBulkAction: function() {
        const status = document.getElementById('bulkStatusSelect')?.value;
        const assignedTo = document.getElementById('bulkAssignSelect')?.value;
        
        if (!status && !assignedTo) {
            Utils.showNotification('변경할 상태 또는 담당자를 선택해주세요.', 'warning');
            return;
        }
        
        if (window.ComplaintSystem.selectedItems.size === 0) {
            Utils.showNotification('선택된 민원이 없습니다.', 'warning');
            return;
        }
        
        if (!confirm(`선택된 ${window.ComplaintSystem.selectedItems.size}개의 민원을 수정하시겠습니까?`)) {
            return;
        }
        
        const data = {
            complaint_ids: Array.from(window.ComplaintSystem.selectedItems),
            status: status || null,
            assigned_to: assignedTo || null
        };
        
        Utils.ajax('/api/complaints/bulk-update', {
            method: 'POST',
            body: data
        })
        .then(data => {
            if (data.success) {
                Utils.showNotification('대량 작업이 완료되었습니다.', 'success');
                this.clearSelection();
                this.updateComplaintsList(false);
            } else {
                Utils.showNotification('대량 작업에 실패했습니다.', 'error');
            }
        });
    },

    // 선택 해제
    clearSelection: function() {
        window.ComplaintSystem.selectedItems.clear();
        document.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateBulkActions();
    },

    // 빠른 수정
    quickEdit: function(complaintId) {
        Utils.ajax(`/api/complaints/${complaintId}`)
            .then(data => {
                if (data.success) {
                    const complaint = data.data;
                    document.getElementById('quickEditId').value = complaint.id;
                    document.getElementById('quickEditStatus').value = complaint.status;
                    document.getElementById('quickEditPriority').value = complaint.priority;
                    document.getElementById('quickEditAssigned').value = complaint.assigned_to || '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('quickEditModal'));
                    modal.show();
                } else {
                    Utils.showNotification('민원 정보를 불러오는데 실패했습니다.', 'error');
                }
            });
    },

    // 삭제 확인
    confirmDelete: function(complaintId) {
        if (!confirm('정말로 이 민원을 삭제하시겠습니까?')) {
            return;
        }
        
        Utils.ajax(`/api/complaints/${complaintId}`, {
            method: 'DELETE'
        })
        .then(data => {
            if (data.success) {
                Utils.showNotification('민원이 삭제되었습니다.', 'success');
                document.querySelector(`[data-id="${complaintId}"]`)?.remove();
                this.updateComplaintsList(false);
            } else {
                Utils.showNotification('민원 삭제에 실패했습니다.', 'error');
            }
        });
    },

    // 이벤트 리스너 초기화
    initializeEventListeners: function() {
        // 새로고침 버튼
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            location.reload();
        });
        
        // 고급 필터 토글
        document.getElementById('toggleAdvancedFilters')?.addEventListener('click', function() {
            const advancedFilters = document.getElementById('advancedFilters');
            advancedFilters?.classList.toggle('show');
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = advancedFilters?.classList.contains('show') 
                    ? 'bi bi-funnel-fill' 
                    : 'bi bi-funnel';
            }
        });
        
        // 필터 초기화
        document.getElementById('clearFilters')?.addEventListener('click', () => {
            document.getElementById('filterForm')?.reset();
            this.applyFilters();
        });
        
        // 빠른 수정 저장
        document.getElementById('saveQuickEdit')?.addEventListener('click', () => {
            this.saveQuickEdit();
        });
    },

    // 빠른 수정 저장
    saveQuickEdit: function() {
        const complaintId = document.getElementById('quickEditId')?.value;
        const status = document.getElementById('quickEditStatus')?.value;
        const priority = document.getElementById('quickEditPriority')?.value;
        const assignedTo = document.getElementById('quickEditAssigned')?.value;
        
        if (!complaintId) return;
        
        const data = {
            status: status,
            priority: priority,
            assigned_to: assignedTo || null
        };
        
        Utils.ajax(`/api/complaints/${complaintId}`, {
            method: 'PUT',
            body: data
        })
        .then(data => {
            if (data.success) {
                Utils.showNotification('민원이 수정되었습니다.', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickEditModal'));
                modal?.hide();
                this.updateComplaintsList(false);
            } else {
                Utils.showNotification('민원 수정에 실패했습니다.', 'error');
            }
        });
    },

    // 필터 태그 업데이트
    updateFilterTags: function() {
        const filterTagsContainer = document.getElementById('filterTags');
        if (!filterTagsContainer) return;
        
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
        
        const formData = new FormData(document.getElementById('filterForm'));
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                const tag = document.createElement('div');
                tag.className = 'filter-tag';
                tag.innerHTML = `
                    ${filterLabels[key] || key}: ${value}
                    <span class="remove-filter" data-filter="${key}">&times;</span>
                `;
                filterTagsContainer.appendChild(tag);
            }
        }
        
        // 필터 제거 이벤트
        filterTagsContainer.querySelectorAll('.remove-filter').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filterKey = e.target.dataset.filter;
                const element = document.querySelector(`[name="${filterKey}"]`);
                if (element) {
                    element.value = '';
                    this.applyFilters();
                }
            });
        });
    }
};

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 민원 목록 페이지인 경우
    if (document.getElementById('complaintsContainer')) {
        ComplaintList.init();
    }
});

// 전역 함수로 노출 (onclick 이벤트용)
window.ComplaintList = ComplaintList;
window.Utils = Utils;
