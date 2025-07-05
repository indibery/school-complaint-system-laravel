{{-- resources/views/complaints/modals/quick-actions.blade.php --}}

<!-- 빠른 상태 변경 모달 -->
<div class="modal fade" id="quickStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">상태 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quick-status-form">
                    <input type="hidden" id="status-complaint-id">
                    <div class="mb-3">
                        <label class="form-label">새로운 상태</label>
                        <select class="form-select" id="new-status" required>
                            <option value="submitted">접수 완료</option>
                            <option value="in_progress">처리 중</option>
                            <option value="resolved">해결 완료</option>
                            <option value="closed">종료</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">변경 사유</label>
                        <textarea class="form-control" id="status-reason" rows="3" placeholder="상태 변경 사유를 입력하세요..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="save-status-change">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 빠른 담당자 할당 모달 (관리자만) -->
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="quickAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">담당자 할당</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quick-assign-form">
                    <input type="hidden" id="assign-complaint-id">
                    <div class="mb-3">
                        <label class="form-label">담당자 선택</label>
                        <select class="form-select" id="new-assignee" required>
                            <option value="">담당자를 선택하세요</option>
                            @foreach($assignableUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">할당 메모</label>
                        <textarea class="form-control" id="assign-reason" rows="3" placeholder="할당 사유나 특이사항을 입력하세요..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="save-assign-change">할당</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
// 모달 관련 JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // 상태 변경 모달 열기
    window.openStatusModal = function(complaintId) {
        document.getElementById('status-complaint-id').value = complaintId;
        const modal = new bootstrap.Modal(document.getElementById('quickStatusModal'));
        modal.show();
    };
    
    // 담당자 할당 모달 열기
    window.openAssignModal = function(complaintId) {
        document.getElementById('assign-complaint-id').value = complaintId;
        const modal = new bootstrap.Modal(document.getElementById('quickAssignModal'));
        modal.show();
    };
    
    // 상태 변경 저장
    document.getElementById('save-status-change')?.addEventListener('click', async function() {
        const complaintId = document.getElementById('status-complaint-id').value;
        const newStatus = document.getElementById('new-status').value;
        const reason = document.getElementById('status-reason').value;
        
        if (!newStatus) {
            alert('새로운 상태를 선택해주세요.');
            return;
        }
        
        try {
            const response = await fetch(`/api/complaints/${complaintId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: newStatus,
                    reason: reason
                })
            });
            
            if (!response.ok) throw new Error('상태 변경 실패');
            
            const result = await response.json();
            
            if (result.success) {
                alert('상태가 성공적으로 변경되었습니다.');
                bootstrap.Modal.getInstance(document.getElementById('quickStatusModal')).hide();
                location.reload(); // 페이지 새로고침
            } else {
                throw new Error(result.message || '상태 변경 실패');
            }
            
        } catch (error) {
            console.error('상태 변경 오류:', error);
            alert('상태 변경 중 오류가 발생했습니다.');
        }
    });
    
    // 담당자 할당 저장
    document.getElementById('save-assign-change')?.addEventListener('click', async function() {
        const complaintId = document.getElementById('assign-complaint-id').value;
        const newAssignee = document.getElementById('new-assignee').value;
        const reason = document.getElementById('assign-reason').value;
        
        if (!newAssignee) {
            alert('담당자를 선택해주세요.');
            return;
        }
        
        try {
            const response = await fetch(`/api/complaints/${complaintId}/assign`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    assigned_to: newAssignee,
                    reason: reason
                })
            });
            
            if (!response.ok) throw new Error('담당자 할당 실패');
            
            const result = await response.json();
            
            if (result.success) {
                alert('담당자가 성공적으로 할당되었습니다.');
                bootstrap.Modal.getInstance(document.getElementById('quickAssignModal')).hide();
                location.reload(); // 페이지 새로고침
            } else {
                throw new Error(result.message || '담당자 할당 실패');
            }
            
        } catch (error) {
            console.error('담당자 할당 오류:', error);
            alert('담당자 할당 중 오류가 발생했습니다.');
        }
    });
});
</script>
