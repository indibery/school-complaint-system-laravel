{{-- resources/views/complaints/show.blade.php --}}
@extends('layouts.app')

@section('title', '민원 상세 - ' . $complaint->title)

@push('styles')
<style>
    .complaint-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .complaint-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .priority-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .priority-urgent {
        background: #ff4757;
        color: white;
        box-shadow: 0 0 20px rgba(255, 71, 87, 0.5);
        animation: pulse 2s infinite;
    }

    .priority-high {
        background: #ff9f43;
        color: white;
    }

    .priority-normal {
        background: #26de81;
        color: white;
    }

    .priority-low {
        background: #6c757d;
        color: white;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 71, 87, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
    }

    .info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
    }

    .status-timeline {
        position: relative;
        padding-left: 30px;
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #667eea, #764ba2);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid #667eea;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -37px;
        top: 20px;
        width: 12px;
        height: 12px;
        background: #667eea;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px #667eea;
    }

    .comments-section {
        max-height: 600px;
        overflow-y: auto;
    }

    .comment-item {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .comment-item:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .comment-item.staff-comment {
        border-left-color: #667eea;
        background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
    }

    .comment-item.parent-comment {
        border-left-color: #26de81;
        background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    }

    .comment-form {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-top: 20px;
    }

    .attachment-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: background-color 0.3s ease;
    }

    .attachment-item:hover {
        background: #e9ecef;
    }

    .file-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: white;
        font-weight: bold;
    }

    .file-icon.pdf { background: #dc3545; }
    .file-icon.doc { background: #0d6efd; }
    .file-icon.img { background: #198754; }
    .file-icon.etc { background: #6c757d; }

    .dropzone {
        border: 2px dashed #d1ecf1;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        background: #f8fdff;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .dropzone.dragover {
        border-color: #667eea;
        background: #f0f8ff;
        transform: scale(1.02);
    }

    .action-buttons {
        position: sticky;
        top: 20px;
        z-index: 100;
    }

    .quick-actions {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .progress-indicator {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    @media (max-width: 768px) {
        .complaint-header {
            padding: 20px;
        }
        .priority-badge {
            position: static;
            margin-top: 15px;
            display: inline-block;
        }
        .info-card {
            padding: 15px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 민원 헤더 -->
    <div class="complaint-header">
        <div class="priority-badge priority-{{ $complaint->priority }}">
            {{ $complaint->priority_label }}
        </div>
        <div class="row">
            <div class="col-md-8">
                <h1 class="mb-3">{{ $complaint->title }}</h1>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-hashtag"></i> {{ $complaint->id }}
                    </span>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-folder"></i> {{ $complaint->category->name }}
                    </span>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-user"></i> {{ $complaint->user->name }}
                    </span>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-calendar"></i> {{ $complaint->created_at->format('Y-m-d H:i') }}
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="progress-indicator">
                    @php
                        $progressPercentage = match($complaint->status) {
                            'submitted' => 20,
                            'in_progress' => 50,
                            'resolved' => 80,
                            'closed' => 100,
                            default => 0
                        };
                    @endphp
                    <div class="progress-fill" style="width: {{ $progressPercentage }}%"></div>
                </div>
                <h4 class="mb-0">{{ $complaint->status_label }}</h4>
                @if($complaint->assignedTo)
                <p class="mb-0 opacity-75">담당자: {{ $complaint->assignedTo->name }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 메인 콘텐츠 -->
        <div class="col-lg-8">
            <!-- 민원 내용 -->
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-file-alt text-primary"></i> 민원 내용
                </h5>
                <div class="content-area">
                    {!! nl2br(e($complaint->content)) !!}
                </div>
                
                @if($complaint->student)
                <hr>
                <div class="student-info">
                    <h6><i class="fas fa-user-graduate text-info"></i> 관련 학생</h6>
                    <p class="mb-0">{{ $complaint->student->name }} ({{ $complaint->student->grade }}학년 {{ $complaint->student->class }}반)</p>
                </div>
                @endif
            </div>

            <!-- 첨부파일 -->
            @if($complaint->attachments->count() > 0)
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-paperclip text-warning"></i> 첨부파일 ({{ $complaint->attachments->count() }}개)
                </h5>
                <div class="attachments-list">
                    @foreach($complaint->attachments as $attachment)
                    <div class="attachment-item">
                        @php
                            $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
                            $fileType = match($extension) {
                                'pdf' => 'pdf',
                                'doc', 'docx' => 'doc',
                                'jpg', 'jpeg', 'png', 'gif' => 'img',
                                default => 'etc'
                            };
                            $fileSize = $attachment->file_size;
                            $formattedSize = $fileSize >= 1048576 ? 
                                round($fileSize / 1048576, 2) . ' MB' : 
                                round($fileSize / 1024, 2) . ' KB';
                        @endphp
                        <div class="file-icon {{ $fileType }}">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $attachment->original_name }}</h6>
                            <small class="text-muted">
                                {{ $formattedSize }} • 
                                {{ $attachment->created_at->format('Y-m-d H:i') }}
                            </small>
                        </div>
                        <div class="ms-auto">
                            <a href="{{ route('complaints.attachments.download', $attachment) }}" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> 다운로드
                            </a>
                            @can('update', $complaint)
                            <button class="btn btn-sm btn-outline-danger ms-2" 
                                    onclick="deleteAttachment({{ $attachment->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- 새 첨부파일 업로드 -->
            @can('uploadAttachment', $complaint)
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-upload text-success"></i> 파일 첨부
                </h5>
                <div class="dropzone" id="fileDropzone">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <p class="mb-2">파일을 여기에 드래그하거나 클릭하여 선택하세요</p>
                    <small class="text-muted">최대 10MB, PDF, DOC, DOCX, JPG, PNG 파일만 가능</small>
                    <input type="file" id="fileInput" class="d-none" multiple 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                </div>
                <div id="uploadProgress" class="mt-3" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            @endcan

            <!-- 댓글 시스템 -->
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-comments text-info"></i> 
                    댓글 <span class="badge bg-secondary">{{ $complaint->comments->count() }}</span>
                </h5>
                
                <div class="comments-section" id="commentsSection">
                    @forelse($complaint->comments as $comment)
                    <div class="comment-item {{ $comment->user->role === 'parent' ? 'parent-comment' : 'staff-comment' }}" 
                         data-comment-id="{{ $comment->id }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <div class="comment-avatar me-3">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $comment->user->name }}</h6>
                                    <small class="text-muted">
                                        {{ $comment->user->role_label }} • 
                                        {{ $comment->created_at->format('Y-m-d H:i') }}
                                        @if(!$comment->is_public)
                                        <span class="badge bg-warning ms-1">내부용</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                            @can('delete', $comment)
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteComment({{ $comment->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endcan
                        </div>
                        <div class="comment-content">
                            {!! nl2br(e($comment->content)) !!}
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-comment-slash fa-2x mb-3"></i>
                        <p>아직 댓글이 없습니다.</p>
                    </div>
                    @endforelse
                </div>

                <!-- 댓글 작성 폼 -->
                @can('comment', $complaint)
                <div class="comment-form">
                    <form id="commentForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">댓글 작성</label>
                            <textarea class="form-control" id="commentContent" name="content" rows="4" 
                                      placeholder="댓글을 입력하세요..." required></textarea>
                        </div>
                        @if(auth()->user()->role !== 'parent')
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="isPublic" name="is_public" checked>
                            <label class="form-check-label" for="isPublic">
                                공개 댓글 (민원인에게 보임)
                            </label>
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> 댓글 등록
                            </button>
                        </div>
                    </form>
                </div>
                @endcan
            </div>
        </div>

        <!-- 사이드바 -->
        <div class="col-lg-4">
            <div class="action-buttons">
                <!-- 빠른 작업 -->
                <div class="quick-actions mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-bolt text-warning"></i> 빠른 작업
                    </h5>
                    
                    <!-- 상태 변경 -->
                    @can('updateStatus', $complaint)
                    <div class="mb-3">
                        <label class="form-label">상태 변경</label>
                        <select class="form-select" id="quickStatus">
                            <option value="">상태 선택</option>
                            <option value="submitted" {{ $complaint->status === 'submitted' ? 'selected' : '' }}>접수 완료</option>
                            <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>처리 중</option>
                            <option value="resolved" {{ $complaint->status === 'resolved' ? 'selected' : '' }}>해결 완료</option>
                            <option value="closed" {{ $complaint->status === 'closed' ? 'selected' : '' }}>종료</option>
                        </select>
                        <button class="btn btn-outline-primary btn-sm mt-2 w-100" onclick="changeStatus()">
                            <i class="fas fa-exchange-alt"></i> 상태 변경
                        </button>
                    </div>
                    @endcan

                    <!-- 담당자 할당 -->
                    @can('assign', $complaint)
                    <div class="mb-3">
                        <label class="form-label">담당자 할당</label>
                        <select class="form-select" id="quickAssign">
                            <option value="">담당자 선택</option>
                            @foreach($assignableUsers as $user)
                            <option value="{{ $user->id }}" {{ $complaint->assigned_to == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->role_label }})
                            </option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-success btn-sm mt-2 w-100" onclick="assignUser()">
                            <i class="fas fa-user-plus"></i> 담당자 할당
                        </button>
                    </div>
                    @endcan

                    <!-- 우선순위 변경 -->
                    @can('updateStatus', $complaint)
                    <div class="mb-3">
                        <label class="form-label">우선순위</label>
                        <select class="form-select" id="quickPriority">
                            <option value="low" {{ $complaint->priority === 'low' ? 'selected' : '' }}>낮음</option>
                            <option value="normal" {{ $complaint->priority === 'normal' ? 'selected' : '' }}>보통</option>
                            <option value="high" {{ $complaint->priority === 'high' ? 'selected' : '' }}>높음</option>
                            <option value="urgent" {{ $complaint->priority === 'urgent' ? 'selected' : '' }}>긴급</option>
                        </select>
                        <button class="btn btn-outline-warning btn-sm mt-2 w-100" onclick="changePriority()">
                            <i class="fas fa-exclamation-triangle"></i> 우선순위 변경
                        </button>
                    </div>
                    @endcan

                    <hr>

                    <!-- 기타 액션 -->
                    <div class="d-grid gap-2">
                        @can('update', $complaint)
                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> 민원 수정
                        </a>
                        @endcan
                        
                        <button class="btn btn-outline-info btn-sm" onclick="printComplaint()">
                            <i class="fas fa-print"></i> 인쇄
                        </button>
                        
                        <button class="btn btn-outline-secondary btn-sm" onclick="shareComplaint()">
                            <i class="fas fa-share"></i> 공유
                        </button>
                    </div>
                </div>

                <!-- 처리 이력 -->
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-history text-info"></i> 처리 이력
                    </h5>
                    <div class="status-timeline">
                        @forelse($complaint->statusLogs as $log)
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $log->to_status }}</h6>
                                    <small class="text-muted">{{ $log->user->name }}</small>
                                    @if($log->notes)
                                    <p class="mb-0 mt-1 small">{{ $log->notes }}</p>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $log->created_at->format('m/d H:i') }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-clock"></i>
                            <p class="mb-0 small">처리 이력이 없습니다</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- 민원 정보 -->
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle text-primary"></i> 민원 정보
                    </h5>
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">민원번호</td>
                            <td><strong>{{ $complaint->id }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">등록일</td>
                            <td>{{ $complaint->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">수정일</td>
                            <td>{{ $complaint->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @if($complaint->expected_completion_at)
                        <tr>
                            <td class="text-muted">예상완료일</td>
                            <td>{{ $complaint->expected_completion_at->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                        @if($complaint->completed_at)
                        <tr>
                            <td class="text-muted">완료일</td>
                            <td>{{ $complaint->completed_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">공개여부</td>
                            <td>
                                @if($complaint->is_public)
                                <span class="badge bg-success">공개</span>
                                @else
                                <span class="badge bg-secondary">비공개</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 만족도 평가 -->
                @if($complaint->status === 'resolved' || $complaint->status === 'closed')
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-star text-warning"></i> 만족도 평가
                    </h5>
                    @if($complaint->satisfaction_rating)
                    <div class="text-center">
                        <div class="mb-2">
                            @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $complaint->satisfaction_rating ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                        </div>
                        <p class="mb-0">{{ $complaint->satisfaction_rating }}/5점</p>
                        @if($complaint->satisfaction_comment)
                        <hr>
                        <p class="small text-muted">{{ $complaint->satisfaction_comment }}</p>
                        @endif
                    </div>
                    @else
                    <div class="text-center text-muted">
                        <p class="mb-0">평가 대기 중</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 상태 변경 확인 모달 -->
<div class="modal fade" id="statusChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">상태 변경 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>민원 상태를 <strong id="newStatusText"></strong>로 변경하시겠습니까?</p>
                <div class="mb-3">
                    <label class="form-label">변경 사유</label>
                    <textarea class="form-control" id="statusChangeReason" rows="3" 
                              placeholder="상태 변경 사유를 입력하세요..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">확인</button>
            </div>
        </div>
    </div>
</div>

<!-- 담당자 할당 확인 모달 -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">담당자 할당 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>담당자를 <strong id="newAssigneeText"></strong>로 할당하시겠습니까?</p>
                <div class="mb-3">
                    <label class="form-label">할당 메모</label>
                    <textarea class="form-control" id="assignReason" rows="3" 
                              placeholder="할당 사유나 특이사항을 입력하세요..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmAssign">할당</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 전역 변수
    const complaintId = {{ $complaint->id }};
    
    // 헬퍼 함수
    function getProgressPercentage(status) {
        const statusProgress = {
            'submitted': 20,
            'in_progress': 50,
            'resolved': 80,
            'closed': 100
        };
        return statusProgress[status] || 0;
    }
    
    function getFileType(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        if (['pdf'].includes(ext)) return 'pdf';
        if (['doc', 'docx'].includes(ext)) return 'doc';
        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'img';
        return 'etc';
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // 상태 변경
    window.changeStatus = function() {
        const newStatus = document.getElementById('quickStatus').value;
        if (!newStatus) {
            showAlert('변경할 상태를 선택해주세요.', 'warning');
            return;
        }
        
        const statusLabels = {
            'submitted': '접수 완료',
            'in_progress': '처리 중',
            'resolved': '해결 완료',
            'closed': '종료'
        };
        
        document.getElementById('newStatusText').textContent = statusLabels[newStatus];
        const modal = new bootstrap.Modal(document.getElementById('statusChangeModal'));
        modal.show();
        
        document.getElementById('confirmStatusChange').onclick = async function() {
            const reason = document.getElementById('statusChangeReason').value;
            
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
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('상태가 성공적으로 변경되었습니다.', 'success');
                    modal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(result.message || '상태 변경에 실패했습니다.');
                }
                
            } catch (error) {
                console.error('상태 변경 오류:', error);
                showAlert('상태 변경 중 오류가 발생했습니다.', 'error');
            }
        };
    };
    
    // 담당자 할당
    window.assignUser = function() {
        const newAssignee = document.getElementById('quickAssign').value;
        if (!newAssignee) {
            showAlert('할당할 담당자를 선택해주세요.', 'warning');
            return;
        }
        
        const assigneeText = document.getElementById('quickAssign').selectedOptions[0].text;
        document.getElementById('newAssigneeText').textContent = assigneeText;
        const modal = new bootstrap.Modal(document.getElementById('assignModal'));
        modal.show();
        
        document.getElementById('confirmAssign').onclick = async function() {
            const reason = document.getElementById('assignReason').value;
            
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
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('담당자가 성공적으로 할당되었습니다.', 'success');
                    modal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(result.message || '담당자 할당에 실패했습니다.');
                }
                
            } catch (error) {
                console.error('담당자 할당 오류:', error);
                showAlert('담당자 할당 중 오류가 발생했습니다.', 'error');
            }
        };
    };
    
    // 우선순위 변경
    window.changePriority = async function() {
        const newPriority = document.getElementById('quickPriority').value;
        
        if (!confirm('우선순위를 변경하시겠습니까?')) {
            return;
        }
        
        try {
            const response = await fetch(`/api/complaints/${complaintId}/priority`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    priority: newPriority
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('우선순위가 성공적으로 변경되었습니다.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || '우선순위 변경에 실패했습니다.');
            }
            
        } catch (error) {
            console.error('우선순위 변경 오류:', error);
            showAlert('우선순위 변경 중 오류가 발생했습니다.', 'error');
        }
    };
    
    // 댓글 작성
    document.getElementById('commentForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const content = document.getElementById('commentContent').value.trim();
        const isPublic = document.getElementById('isPublic')?.checked || true;
        
        if (!content) {
            showAlert('댓글 내용을 입력해주세요.', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`{{ route('complaints.comments.store', $complaint) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    content: content,
                    is_private: isPrivate
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('댓글이 성공적으로 등록되었습니다.', 'success');
                document.getElementById('commentContent').value = '';
                if (document.getElementById('isPrivate')) {
                    document.getElementById('isPrivate').checked = false;
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || '댓글 등록에 실패했습니다.');
            }
            
        } catch (error) {
            console.error('댓글 등록 오류:', error);
            showAlert('댓글 등록 중 오류가 발생했습니다.', 'error');
        }
    });
    
    // 댓글 삭제
    window.deleteComment = async function(commentId) {
        if (!confirm('댓글을 삭제하시겠습니까?')) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('complaints.comments.destroy', '') }}/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('댓글이 삭제되었습니다.', 'success');
                document.querySelector(`[data-comment-id="${commentId}"]`).remove();
            } else {
                throw new Error(result.message || '댓글 삭제에 실패했습니다.');
            }
            
        } catch (error) {
            console.error('댓글 삭제 오류:', error);
            showAlert('댓글 삭제 중 오류가 발생했습니다.', 'error');
        }
    };
    
    // 파일 업로드 (드래그앤드롭)
    const dropzone = document.getElementById('fileDropzone');
    const fileInput = document.getElementById('fileInput');
    
    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
        
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        });
        
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            uploadFiles(files);
        });
        
        fileInput.addEventListener('change', (e) => {
            uploadFiles(e.target.files);
        });
    }
    
    // 파일 업로드 함수
    async function uploadFiles(files) {
        if (files.length === 0) return;
        
        const formData = new FormData();
        for (let file of files) {
            formData.append('attachments[]', file);
        }
        
        const progressElement = document.getElementById('uploadProgress');
        const progressBar = progressElement.querySelector('.progress-bar');
        progressElement.style.display = 'block';
        
        try {
            const response = await fetch(`{{ route('complaints.attachments.upload', $complaint) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('파일이 성공적으로 업로드되었습니다.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || '파일 업로드에 실패했습니다.');
            }
            
        } catch (error) {
            console.error('파일 업로드 오류:', error);
            showAlert('파일 업로드 중 오류가 발생했습니다.', 'error');
        } finally {
            progressElement.style.display = 'none';
            progressBar.style.width = '0%';
        }
    }
    
    // 첨부파일 삭제
    window.deleteAttachment = async function(attachmentId) {
        if (!confirm('첨부파일을 삭제하시겠습니까?')) {
            return;
        }
        
        try {
            const response = await fetch(`{{ route('complaints.attachments.delete', '') }}/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('첨부파일이 삭제되었습니다.', 'success');
                location.reload();
            } else {
                throw new Error(result.message || '첨부파일 삭제에 실패했습니다.');
            }
            
        } catch (error) {
            console.error('첨부파일 삭제 오류:', error);
            showAlert('첨부파일 삭제 중 오류가 발생했습니다.', 'error');
        }
    };
    
    // 기타 기능들
    window.printComplaint = function() {
        window.print();
    };
    
    window.shareComplaint = function() {
        if (navigator.share) {
            navigator.share({
                title: '{{ $complaint->title }}',
                text: '민원을 공유합니다.',
                url: window.location.href
            });
        } else {
            // 클립보드에 URL 복사
            navigator.clipboard.writeText(window.location.href).then(() => {
                showAlert('링크가 클립보드에 복사되었습니다.', 'success');
            });
        }
    };
    
    // 알림 표시 함수
    function showAlert(message, type = 'info') {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed`;
        alertElement.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : (type === 'error' ? 'exclamation-circle' : 'info-circle'))} me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        document.body.appendChild(alertElement);
        
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, 5000);
    }
    
    // 실시간 업데이트 (30초마다)
    setInterval(async function() {
        try {
            const response = await fetch(`{{ route('complaints.show', $complaint) }}?ajax=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                // 댓글 카운트 업데이트
                const commentsBadge = document.querySelector('.badge.bg-secondary');
                if (commentsBadge && data.comments_count !== undefined) {
                    commentsBadge.textContent = data.comments_count;
                }
            }
        } catch (error) {
            console.log('실시간 업데이트 오류:', error);
        }
    }, 30000);
});
</script>
@endpush
