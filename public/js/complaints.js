/**
 * 민원 관련 JavaScript 함수 모음
 * 민원 목록, 상세보기, 생성, 수정 등에서 사용하는 함수들
 */

// 민원 목록 관리 클래스
class ComplaintListManager {
    constructor(options = {}) {
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMoreData = true;
        this.selectedComplaints = new Set();
        this.currentFilters = {};
        this.realTimeUpdateInterval = null;
        
        // 옵션 설정
        this.options = {
            container: '#complaintsContainer',
            perPage: 20,
            realTimeUpdate: true,
            realTimeInterval: 30000, // 30초
            infiniteScroll: true,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.initializeFilters();
        if (this.options.realTimeUpdate) {
            this.initializeRealTimeUpdate();
        }
        if (this.options.infiniteScroll) {
            this.initializeInfiniteScroll();
        }
        this.initializeBulkActions();
    }
    
    initializeFilters() {
        this.updateCurrentFilters();
        this.updateFilterTags();
        
        // 필터 이벤트 리스너
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => this.applyFilters(), 300));
        }
        
        ['statusFilter', 'priorityFilter', 'sortFilter', 'categoryFilter', 
         'assignedFilter', 'dateFromFilter', 'dateToFilter'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => this.applyFilters());
            }
        });
    }
    
    initializeRealTimeUpdate() {
        this.realTimeUpdateInterval = setInterval(() => {
            this.updateComplaintsList(true);
        }, this.options.realTimeInterval);
    }
    
    initializeInfiniteScroll() {
        window.addEventListener('scroll', throttle(() => {
            if (this.isLoading || !this.hasMoreData) return;
            
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                this.loadMoreComplaints();
            }
        }, 200));
    }
    
    initializeBulkActions() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('complaint-checkbox')) {
                const complaintId = e.target.value;
                if (e.target.checked) {
                    this.selectedComplaints.add(complaintId);
                } else {
                    this.selectedComplaints.delete(complaintId);
                }
                this.updateBulkActions();
            }
        });
    }
    
    updateCurrentFilters() {
        const form = document.getElementById('filterForm');
        if (!form) return;
        
        const formData = new FormData(form);
        this.currentFilters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                this.currentFilters[key] = value;
            }
        }
    }
    
    updateFilterTags() {
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
        
        for (let [key, value] of Object.entries(this.currentFilters)) {
            const tag = document.createElement('div');
            tag.className = 'filter-tag';
            tag.innerHTML = `
                ${filterLabels[key] || key}: ${value}
                <span class="remove-filter" data-filter="${key}">&times;</span>
            `;
            filterTagsContainer.appendChild(tag);
        }
        
        // 필터 제거 이벤트
        filterTagsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-filter')) {
                this.removeFilter(e.target.dataset.filter);
            }
        });
    }
    
    removeFilter(filterKey) {
        const element = document.querySelector(`[name="${filterKey}"]`);
        if (element) {
            element.value = '';
            delete this.currentFilters[filterKey];
            this.updateFilterTags();
            this.applyFilters();
        }
    }
    
    applyFilters() {
        this.updateCurrentFilters();
        this.updateFilterTags();
        this.currentPage = 1;
        this.hasMoreData = true;
        this.updateComplaintsList(false);
    }
    
    async updateComplaintsList(isRealTimeUpdate = false) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        if (isRealTimeUpdate) {
            const indicator = document.getElementById('realTimeIndicator');
            if (indicator) {
                indicator.style.display = 'block';
                indicator.classList.add('updating');
            }
        } else {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.style.display = 'block';
        }
        
        try {
            const params = new URLSearchParams(this.currentFilters);
            params.append('page', this.currentPage);
            params.append('per_page', this.options.perPage);
            
            const data = await apiRequest(`/api/complaints?${params.toString()}`);
            
            if (data.success) {
                if (this.currentPage === 1) {
                    document.querySelector(this.options.container).innerHTML = '';
                }
                
                this.appendComplaints(data.data.data);
                this.updateStats(data.data.meta);
                
                if (data.data.data.length < this.options.perPage) {
                    this.hasMoreData = false;
                    const noMoreData = document.getElementById('noMoreData');
                    if (noMoreData) noMoreData.style.display = 'block';
                }
                
                if (isRealTimeUpdate) {
                    showNotification('민원 목록이 업데이트되었습니다.', 'success');
                }
            }
        } catch (error) {
            showNotification('민원 목록을 불러오는데 실패했습니다.', 'error');
        } finally {
            this.isLoading = false;
            
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.style.display = 'none';
            
            if (isRealTimeUpdate) {
                const indicator = document.getElementById('realTimeIndicator');
                if (indicator) {
                    setTimeout(() => {
                        indicator.style.display = 'none';
                        indicator.classList.remove('updating');
                    }, 2000);
                }
            }
        }
    }
    
    loadMoreComplaints() {
        if (this.isLoading || !this.hasMoreData) return;
        
        this.currentPage++;
        this.updateComplaintsList(false);
    }
    
    appendComplaints(complaints) {
        const container = document.querySelector(this.options.container);
        
        complaints.forEach(complaint => {
            const complaintHTML = this.createComplaintHTML(complaint);
            container.insertAdjacentHTML('beforeend', complaintHTML);
        });
    }
    
    createComplaintHTML(complaint) {
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
                                        ${formatDate(complaint.created_at)}
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
                                        onclick="complaintManager.quickEdit(${complaint.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="complaintManager.confirmDelete(${complaint.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    updateStats(meta) {
        if (meta.statistics) {
            ['total', 'pending', 'in_progress', 'resolved', 'urgent'].forEach(stat => {
                const element = document.getElementById(`stat${stat.charAt(0).toUpperCase() + stat.slice(1).replace('_', '')}`);
                if (element) {
                    element.textContent = meta.statistics[stat] || 0;
                }
            });
            
            const totalCount = document.getElementById('totalCount');
            if (totalCount) {
                totalCount.textContent = meta.statistics.total || 0;
            }
        }
    }
    
    updateBulkActions() {
        const count = this.selectedComplaints.size;
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedCount) selectedCount.textContent = count;
        
        if (bulkActions) {
            if (count > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }
    }
    
    async applyBulkAction() {
        const status = document.getElementById('bulkStatusSelect')?.value;
        const assignedTo = document.getElementById('bulkAssignSelect')?.value;
        
        if (!status && !assignedTo) {
            showNotification('변경할 상태 또는 담당자를 선택해주세요.', 'warning');
            return;
        }
        
        if (this.selectedComplaints.size === 0) {
            showNotification('선택된 민원이 없습니다.', 'warning');
            return;
        }
        
        if (!await confirmDialog(`선택된 ${this.selectedComplaints.size}개의 민원을 수정하시겠습니까?`)) {
            return;
        }
        
        try {
            const data = await apiRequest('/api/complaints/bulk-update', {
                method: 'POST',
                body: JSON.stringify({
                    complaint_ids: Array.from(this.selectedComplaints),
                    status: status,
                    assigned_to: assignedTo
                })
            });
            
            if (data.success) {
                showNotification('대량 작업이 완료되었습니다.', 'success');
                this.clearSelection();
                this.updateComplaintsList(false);
            }
        } catch (error) {
            showNotification('대량 작업에 실패했습니다.', 'error');
        }
    }
    
    clearSelection() {
        this.selectedComplaints.clear();
        document.querySelectorAll('.complaint-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateBulkActions();
    }
    
    async quickEdit(complaintId) {
        try {
            const data = await apiRequest(`/api/complaints/${complaintId}`);
            
            if (data.success) {
                const complaint = data.data;
                document.getElementById('quickEditId').value = complaint.id;
                document.getElementById('quickEditStatus').value = complaint.status;
                document.getElementById('quickEditPriority').value = complaint.priority;
                document.getElementById('quickEditAssigned').value = complaint.assigned_to || '';
                
                new bootstrap.Modal(document.getElementById('quickEditModal')).show();
            }
        } catch (error) {
            showNotification('민원 정보를 불러오는데 실패했습니다.', 'error');
        }
    }
    
    async saveQuickEdit() {
        const complaintId = document.getElementById('quickEditId').value;
        const status = document.getElementById('quickEditStatus').value;
        const priority = document.getElementById('quickEditPriority').value;
        const assignedTo = document.getElementById('quickEditAssigned').value;
        
        try {
            const data = await apiRequest(`/api/complaints/${complaintId}`, {
                method: 'PUT',
                body: JSON.stringify({
                    status: status,
                    priority: priority,
                    assigned_to: assignedTo || null
                })
            });
            
            if (data.success) {
                showNotification('민원이 수정되었습니다.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickEditModal')).hide();
                this.updateComplaintsList(false);
            }
        } catch (error) {
            showNotification('민원 수정에 실패했습니다.', 'error');
        }
    }
    
    async confirmDelete(complaintId) {
        if (!await confirmDialog('정말로 이 민원을 삭제하시겠습니까?')) {
            return;
        }
        
        try {
            const data = await apiRequest(`/api/complaints/${complaintId}`, {
                method: 'DELETE'
            });
            
            if (data.success) {
                showNotification('민원이 삭제되었습니다.', 'success');
                document.querySelector(`[data-id="${complaintId}"]`)?.remove();
                this.updateComplaintsList(false);
            }
        } catch (error) {
            showNotification('민원 삭제에 실패했습니다.', 'error');
        }
    }
    
    destroy() {
        if (this.realTimeUpdateInterval) {
            clearInterval(this.realTimeUpdateInterval);
        }
    }
}

// 민원 폼 관리 클래스
class ComplaintFormManager {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            attachmentMaxSize: 10485760, // 10MB
            attachmentAllowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ...options
        };
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        this.initializeAttachments();
        this.initializeValidation();
        this.initializeSubmit();
    }
    
    initializeAttachments() {
        const attachmentInput = this.form.querySelector('input[type="file"]');
        if (!attachmentInput) return;
        
        attachmentInput.addEventListener('change', (e) => {
            this.validateAttachments(e.target.files);
        });
    }
    
    validateAttachments(files) {
        const errors = [];
        
        Array.from(files).forEach(file => {
            if (file.size > this.options.attachmentMaxSize) {
                errors.push(`${file.name}: 파일 크기가 너무 큽니다. (최대 ${formatFileSize(this.options.attachmentMaxSize)})`);
            }
            
            if (!this.options.attachmentAllowedTypes.includes(file.type)) {
                errors.push(`${file.name}: 허용되지 않는 파일 형식입니다.`);
            }
        });
        
        if (errors.length > 0) {
            showNotification(errors.join('<br>'), 'error');
            return false;
        }
        
        return true;
    }
    
    initializeValidation() {
        this.form.addEventListener('input', (e) => {
            if (e.target.hasAttribute('required') && e.target.value.trim()) {
                e.target.classList.remove('is-invalid');
            }
        });
    }
    
    initializeSubmit() {
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm(this.form)) {
                showNotification('필수 항목을 모두 입력해주세요.', 'warning');
                return;
            }
            
            const formData = new FormData(this.form);
            const submitButton = this.form.querySelector('[type="submit"]');
            
            submitButton.disabled = true;
            showLoading(this.form);
            
            try {
                const response = await fetch(this.form.action, {
                    method: this.form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || '저장되었습니다.', 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    showNotification(data.message || '저장에 실패했습니다.', 'error');
                }
            } catch (error) {
                showNotification('네트워크 오류가 발생했습니다.', 'error');
            } finally {
                submitButton.disabled = false;
                hideLoading(this.form);
            }
        });
    }
}

// 민원 상세 관리 클래스
class ComplaintDetailManager {
    constructor(complaintId, options = {}) {
        this.complaintId = complaintId;
        this.options = options;
        this.init();
    }
    
    init() {
        this.initializeComments();
        this.initializeStatusChange();
        this.initializeAttachmentDownload();
    }
    
    initializeComments() {
        const commentForm = document.getElementById('commentForm');
        if (!commentForm) return;
        
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const content = commentForm.querySelector('textarea[name="content"]').value;
            if (!content.trim()) {
                showNotification('댓글 내용을 입력해주세요.', 'warning');
                return;
            }
            
            try {
                const data = await apiRequest(`/api/complaints/${this.complaintId}/comments`, {
                    method: 'POST',
                    body: JSON.stringify({ content: content })
                });
                
                if (data.success) {
                    showNotification('댓글이 등록되었습니다.', 'success');
                    this.appendComment(data.data);
                    commentForm.reset();
                }
            } catch (error) {
                showNotification('댓글 등록에 실패했습니다.', 'error');
            }
        });
    }
    
    appendComment(comment) {
        const commentsContainer = document.getElementById('commentsContainer');
        if (!commentsContainer) return;
        
        const commentHTML = `
            <div class="comment-item mb-3" data-comment-id="${comment.id}">
                <div class="d-flex align-items-start">
                    <div class="avatar me-3">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="comment-header mb-1">
                            <strong>${comment.user.name}</strong>
                            <span class="text-muted ms-2">${formatDate(comment.created_at, 'relative')}</span>
                        </div>
                        <div class="comment-content">${comment.content}</div>
                    </div>
                </div>
            </div>
        `;
        
        commentsContainer.insertAdjacentHTML('afterbegin', commentHTML);
    }
    
    initializeStatusChange() {
        const statusSelect = document.getElementById('statusSelect');
        if (!statusSelect) return;
        
        statusSelect.addEventListener('change', async (e) => {
            const newStatus = e.target.value;
            
            if (!await confirmDialog('민원 상태를 변경하시겠습니까?')) {
                e.target.value = e.target.dataset.originalValue;
                return;
            }
            
            try {
                const data = await apiRequest(`/api/complaints/${this.complaintId}/status`, {
                    method: 'PUT',
                    body: JSON.stringify({ status: newStatus })
                });
                
                if (data.success) {
                    showNotification('상태가 변경되었습니다.', 'success');
                    e.target.dataset.originalValue = newStatus;
                    this.updateStatusBadge(newStatus);
                }
            } catch (error) {
                showNotification('상태 변경에 실패했습니다.', 'error');
                e.target.value = e.target.dataset.originalValue;
            }
        });
    }
    
    updateStatusBadge(status) {
        const statusBadge = document.querySelector('.status-badge');
        if (!statusBadge) return;
        
        const statusClasses = {
            'pending': 'bg-warning text-dark',
            'in_progress': 'bg-info',
            'resolved': 'bg-success',
            'closed': 'bg-secondary'
        };
        
        statusBadge.className = `status-badge badge ${statusClasses[status] || 'bg-secondary'}`;
    }
    
    initializeAttachmentDownload() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.download-attachment')) {
                e.preventDefault();
                const attachmentId = e.target.closest('.download-attachment').dataset.attachmentId;
                this.downloadAttachment(attachmentId);
            }
        });
    }
    
    async downloadAttachment(attachmentId) {
        try {
            const response = await fetch(`/api/complaints/${this.complaintId}/attachments/${attachmentId}/download`, {
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const filename = response.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g, '') || 'download';
                
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(a.href);
            } else {
                showNotification('파일 다운로드에 실패했습니다.', 'error');
            }
        } catch (error) {
            showNotification('네트워크 오류가 발생했습니다.', 'error');
        }
    }
}

// Export 클래스들
window.ComplaintListManager = ComplaintListManager;
window.ComplaintFormManager = ComplaintFormManager;
window.ComplaintDetailManager = ComplaintDetailManager;
