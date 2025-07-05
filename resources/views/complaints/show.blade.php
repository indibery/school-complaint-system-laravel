{{-- resources/views/complaints/show.blade.php 계속 --}}
                        <div class="status-option-desc">민원이 해결되어 완료된 상태</div>
                    </div>
                    <div class="status-option" data-status="closed">
                        <div class="status-option-title">종료</div>
                        <div class="status-option-desc">민원 처리가 완전히 종료된 상태</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="statusComment" class="form-label">변경 사유 (선택)</label>
                    <textarea class="form-control" id="statusComment" rows="3" 
                              placeholder="상태 변경 사유를 입력하세요..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveStatus">변경 저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 담당자 할당 모달 -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">담당자 할당</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="assignedTo" class="form-label">담당자 선택</label>
                    <select class="form-select" id="assignedTo">
                        <option value="">담당자 없음</option>
                        @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}" {{ $complaint->assigned_to == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->role_text }})
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="assignComment" class="form-label">할당 사유 (선택)</label>
                    <textarea class="form-control" id="assignComment" rows="3" 
                              placeholder="담당자 할당 사유를 입력하세요..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveAssign">할당 저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 우선순위 변경 모달 -->
<div class="modal fade" id="priorityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">우선순위 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">우선순위 선택</label>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary priority-btn" data-priority="low">
                            <i class="bi bi-arrow-down-circle"></i> 낮음 - 일반적인 문의
                        </button>
                        <button type="button" class="btn btn-outline-success priority-btn" data-priority="normal">
                            <i class="bi bi-dash-circle"></i> 보통 - 표준 처리
                        </button>
                        <button type="button" class="btn btn-outline-warning priority-btn" data-priority="high">
                            <i class="bi bi-arrow-up-circle"></i> 높음 - 신속 처리 필요
                        </button>
                        <button type="button" class="btn btn-outline-danger priority-btn" data-priority="urgent">
                            <i class="bi bi-exclamation-triangle"></i> 긴급 - 즉시 처리 필요
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="priorityComment" class="form-label">변경 사유 (선택)</label>
                    <textarea class="form-control" id="priorityComment" rows="3" 
                              placeholder="우선순위 변경 사유를 입력하세요..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="savePriority" disabled>변경 저장</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 전역 변수
const complaintId = {{ $complaint->id }};
let selectedStatus = '{{ $complaint->status }}';
let selectedPriority = '{{ $complaint->priority }}';
let selectedRating = 0;

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeModals();
    initializeCommentForm();
    initializeSatisfactionRating();
    startRealTimeUpdate();
});

// 모달 초기화
function initializeModals() {
    // 상태 옵션 선택
    document.querySelectorAll('.status-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.status-option').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            selectedStatus = this.dataset.status;
        });
    });

    // 우선순위 버튼 선택
    document.querySelectorAll('.priority-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.priority-btn').forEach(el => el.classList.remove('btn-primary'));
            this.classList.add('btn-primary');
            selectedPriority = this.dataset.priority;
            document.getElementById('savePriority').disabled = false;
        });
    });

    // 상태 저장
    document.getElementById('saveStatus').addEventListener('click', saveStatus);
    
    // 담당자 저장
    document.getElementById('saveAssign').addEventListener('click', saveAssign);
    
    // 우선순위 저장
    document.getElementById('savePriority').addEventListener('click', savePriority);

    // 현재 상태/우선순위 선택
    document.querySelector(`[data-status="${selectedStatus}"]`)?.classList.add('selected');
    document.querySelector(`[data-priority="${selectedPriority}"]`)?.classList.add('btn-primary');
}

// 댓글 폼 초기화
function initializeCommentForm() {
    const form = document.getElementById('commentForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const content = document.getElementById('commentContent').value.trim();
        const isPublic = document.getElementById('isPublic').checked;
        
        if (!content) {
            showNotification('댓글 내용을 입력해주세요.', 'warning');
            return;
        }

        submitComment(content, isPublic);
    });
}

// 댓글 제출
function submitComment(content, isPublic) {
    fetch(`/complaints/${complaintId}/comments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            content: content,
            is_public: isPublic
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 댓글 목록에 추가
            addCommentToList(data.comment);
            
            // 폼 초기화
            document.getElementById('commentContent').value = '';
            document.getElementById('isPublic').checked = true;
            
            showNotification('댓글이 등록되었습니다.', 'success');
        } else {
            showNotification(data.message || '댓글 등록에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 댓글 목록에 추가
function addCommentToList(comment) {
    const commentList = document.getElementById('commentList');
    const emptyMessage = commentList.querySelector('.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const commentHTML = `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-avatar">
                ${comment.user.name.substring(0, 1).toUpperCase()}
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <div>
                        <span class="comment-author">${comment.user.name}</span>
                        ${!comment.is_public ? '<span class="comment-badge">내부</span>' : ''}
                        <span class="comment-time">방금 전</span>
                    </div>
                    <div class="comment-actions">
                        <button class="btn btn-sm btn-link text-danger" onclick="deleteComment(${comment.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="comment-text">${comment.content}</div>
            </div>
        </div>
    `;

    commentList.insertAdjacentHTML('beforeend', commentHTML);
    commentList.scrollTop = commentList.scrollHeight;
}

// 댓글 삭제
function deleteComment(commentId) {
    if (!confirm('댓글을 삭제하시겠습니까?')) {
        return;
    }

    fetch(`/comments/${commentId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-comment-id="${commentId}"]`).remove();
            showNotification('댓글이 삭제되었습니다.', 'success');
        } else {
            showNotification('댓글 삭제에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 상태 저장
function saveStatus() {
    const comment = document.getElementById('statusComment').value.trim();
    
    showLoading();

    fetch(`/complaints/${complaintId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            status: selectedStatus,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('상태가 변경되었습니다.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || '상태 변경에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 담당자 저장
function saveAssign() {
    const assignedTo = document.getElementById('assignedTo').value;
    const comment = document.getElementById('assignComment').value.trim();
    
    if (!assignedTo) {
        showNotification('담당자를 선택해주세요.', 'warning');
        return;
    }

    showLoading();

    fetch(`/complaints/${complaintId}/assign`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            assigned_to: assignedTo,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            document.getElementById('assignedToName').textContent = data.assigned_to;
            showNotification('담당자가 할당되었습니다.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
        } else {
            showNotification(data.message || '담당자 할당에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 우선순위 저장
function savePriority() {
    const comment = document.getElementById('priorityComment').value.trim();
    
    showLoading();

    fetch(`/complaints/${complaintId}/priority`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            priority: selectedPriority,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('우선순위가 변경되었습니다.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('priorityModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || '우선순위 변경에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 빠른 상태 변경
function quickStatusChange(status) {
    if (!confirm('상태를 변경하시겠습니까?')) {
        return;
    }

    selectedStatus = status;
    saveStatus();
}

// 만족도 평가 초기화
function initializeSatisfactionRating() {
    const stars = document.querySelectorAll('#satisfactionStars i');
    const submitBtn = document.getElementById('submitSatisfaction');
    
    if (stars.length === 0) return;

    stars.forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            
            // 별점 표시 업데이트
            stars.forEach((s, index) => {
                if (index < selectedRating) {
                    s.classList.remove('bi-star');
                    s.classList.add('bi-star-fill', 'active');
                } else {
                    s.classList.remove('bi-star-fill', 'active');
                    s.classList.add('bi-star');
                }
            });
            
            if (submitBtn) {
                submitBtn.style.display = 'block';
            }
        });

        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#dee2e6';
                }
            });
        });
    });

    document.getElementById('satisfactionStars')?.addEventListener('mouseleave', function() {
        stars.forEach((s, index) => {
            if (index < selectedRating) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#dee2e6';
            }
        });
    });

    submitBtn?.addEventListener('click', submitSatisfaction);
}

// 만족도 제출
function submitSatisfaction() {
    if (selectedRating === 0) {
        showNotification('평가를 선택해주세요.', 'warning');
        return;
    }

    fetch(`/complaints/${complaintId}/satisfaction`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            rating: selectedRating
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('평가가 제출되었습니다. 감사합니다.', 'success');
            document.querySelector('.satisfaction-section').innerHTML = 
                '<p class="text-success"><i class="bi bi-check-circle"></i> 평가가 완료되었습니다.</p>';
        } else {
            showNotification('평가 제출에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 실시간 업데이트
function startRealTimeUpdate() {
    setInterval(function() {
        fetch(`/complaints/${complaintId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 댓글 수 업데이트
                const commentCount = data.comments_count;
                const currentCount = document.querySelectorAll('.comment-item').length;
                
                if (commentCount > currentCount) {
                    // 새 댓글이 있으면 리로드
                    location.reload();
                }
            }
        })
        .catch(error => console.error('Update error:', error));
    }, 30000); // 30초마다
}

// 민원 삭제
function deleteComplaint() {
    if (!confirm('정말로 이 민원을 삭제하시겠습니까?\n삭제된 민원은 복구할 수 없습니다.')) {
        return;
    }

    showLoading();

    fetch(`/complaints/${complaintId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification('민원이 삭제되었습니다.', 'success');
            setTimeout(() => {
                window.location.href = '/complaints';
            }, 1500);
        } else {
            showNotification('민원 삭제에 실패했습니다.', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showNotification('네트워크 오류가 발생했습니다.', 'error');
    });
}

// 인쇄
function printComplaint() {
    window.print();
}

// 댓글로 스크롤
function scrollToComment() {
    document.getElementById('commentForm')?.scrollIntoView({ behavior: 'smooth' });
    document.getElementById('commentContent')?.focus();
}

// 로딩 표시
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

// 로딩 숨김
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

// 알림 표시
function showNotification(message, type = 'info') {
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
</script>
@endpush
