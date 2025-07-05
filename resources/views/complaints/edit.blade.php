{{-- resources/views/complaints/edit.blade.php --}}
@extends('layouts.app')

@section('title', '민원 수정')

@push('styles')
<style>
    .form-section {
        background: #fff;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .priority-selector {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .priority-option {
        flex: 1;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .priority-option:hover {
        border-color: #007bff;
        transform: translateY(-2px);
    }
    
    .priority-option.selected {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .priority-option.urgent {
        border-color: #dc3545;
    }
    
    .priority-option.urgent.selected {
        background: #fee;
        border-color: #dc3545;
    }
    
    .priority-option.high {
        border-color: #fd7e14;
    }
    
    .priority-option.high.selected {
        background: #fff3e0;
        border-color: #fd7e14;
    }
    
    .priority-option.normal {
        border-color: #28a745;
    }
    
    .priority-option.normal.selected {
        background: #e8f5e9;
        border-color: #28a745;
    }
    
    .file-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .file-upload-area:hover {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .file-upload-area.dragging {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .file-list {
        margin-top: 20px;
    }
    
    .file-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 5px;
    }
    
    .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .file-icon {
        font-size: 1.5rem;
    }
    
    .file-size {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .remove-file {
        color: #dc3545;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    .remove-file:hover {
        color: #c82333;
    }
    
    .char-counter {
        text-align: right;
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .form-helper {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .submit-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    
    .last-modified {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }
    
    .category-item {
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .category-item:hover {
        border-color: #007bff;
        transform: translateY(-2px);
    }
    
    .category-item.selected {
        border-color: #007bff;
        background: #e7f3ff;
        font-weight: 600;
    }
    
    .existing-file {
        background: #e8f5e9;
        border: 1px solid #28a745;
    }
    
    .existing-file .file-info {
        color: #28a745;
    }
    
    .complaint-info {
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .complaint-info .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 5px;
    }
    
    .complaint-info .info-label {
        font-weight: 600;
        min-width: 100px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    .validation-feedback {
        display: none;
        font-size: 0.875rem;
        margin-top: 5px;
    }
    
    .validation-feedback.valid {
        color: #28a745;
        display: block;
    }
    
    .validation-feedback.invalid {
        color: #dc3545;
        display: block;
    }
    
    .loading-spinner {
        display: none;
        margin-left: 10px;
    }
    
    .edit-notice {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 10px 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .edit-notice i {
        color: #f39c12;
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .form-section {
            padding: 20px;
        }
        
        .priority-selector {
            flex-direction: column;
        }
        
        .category-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .submit-section {
            flex-direction: column;
            gap: 15px;
        }
        
        .complaint-info .info-item {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">민원 수정</h1>
            <p class="text-muted mb-0">민원 정보를 수정합니다</p>
        </div>
        <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 민원 상세로
        </a>
    </div>

    <!-- 수정 안내 -->
    <div class="edit-notice">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            민원 상태가 '{{ $complaint->status_text }}'인 상태에서 수정 중입니다. 
            수정 후에도 현재 상태가 유지됩니다.
        </div>
    </div>

    <!-- 민원 정보 -->
    <div class="complaint-info">
        <div class="info-item">
            <span class="info-label">민원번호:</span>
            <strong>{{ $complaint->complaint_number }}</strong>
        </div>
        <div class="info-item">
            <span class="info-label">등록일시:</span>
            <span>{{ $complaint->created_at->format('Y-m-d H:i:s') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">현재 상태:</span>
            <span class="status-badge 
                @if($complaint->status == 'pending') bg-warning text-dark
                @elseif($complaint->status == 'in_progress') bg-info
                @elseif($complaint->status == 'resolved') bg-success
                @else bg-secondary
                @endif">
                {{ $complaint->status_text }}
            </span>
        </div>
        @if($complaint->assigned_to)
        <div class="info-item">
            <span class="info-label">담당자:</span>
            <span>{{ $complaint->assignedTo->name }}</span>
        </div>
        @endif
    </div>

    <form id="complaintForm" action="{{ route('complaints.update', $complaint) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <!-- 기본 정보 섹션 -->
        <div class="form-section">
            <h3 class="section-title">기본 정보</h3>
            
            <div class="mb-3">
                <label for="title" class="form-label required">민원 제목</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title', $complaint->title) }}" 
                       placeholder="민원 제목을 입력하세요" maxlength="200" required>
                <div class="char-counter">
                    <span id="titleCharCount">0</span> / 200
                </div>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label required">카테고리</label>
                <div class="category-grid">
                    @foreach($categories as $category)
                    <div class="category-item {{ $complaint->category_id == $category->id ? 'selected' : '' }}" 
                         data-category-id="{{ $category->id }}">
                        <i class="bi bi-folder-fill"></i>
                        {{ $category->name }}
                    </div>
                    @endforeach
                </div>
                <input type="hidden" id="category_id" name="category_id" 
                       value="{{ old('category_id', $complaint->category_id) }}" required>
                @error('category_id')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label required">우선순위</label>
                <div class="priority-selector">
                    <div class="priority-option low {{ $complaint->priority == 'low' ? 'selected' : '' }}" 
                         data-priority="low">
                        <i class="bi bi-arrow-down-circle"></i>
                        <div>낮음</div>
                        <small class="text-muted">일반적인 문의</small>
                    </div>
                    <div class="priority-option normal {{ $complaint->priority == 'normal' ? 'selected' : '' }}" 
                         data-priority="normal">
                        <i class="bi bi-dash-circle"></i>
                        <div>보통</div>
                        <small class="text-muted">표준 처리</small>
                    </div>
                    <div class="priority-option high {{ $complaint->priority == 'high' ? 'selected' : '' }}" 
                         data-priority="high">
                        <i class="bi bi-arrow-up-circle"></i>
                        <div>높음</div>
                        <small class="text-muted">신속 처리 필요</small>
                    </div>
                    <div class="priority-option urgent {{ $complaint->priority == 'urgent' ? 'selected' : '' }}" 
                         data-priority="urgent">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>긴급</div>
                        <small class="text-muted">즉시 처리 필요</small>
                    </div>
                </div>
                <input type="hidden" id="priority" name="priority" 
                       value="{{ old('priority', $complaint->priority) }}">
                @error('priority')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- 민원 내용 섹션 -->
        <div class="form-section">
            <h3 class="section-title">민원 내용</h3>
            
            <div class="mb-3">
                <label for="description" class="form-label required">상세 내용</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="8" 
                          placeholder="민원 내용을 자세히 작성해주세요" required>{{ old('description', $complaint->description) }}</textarea>
                <div class="char-counter">
                    <span id="descriptionCharCount">0</span> / 5000
                </div>
                <div class="form-helper">
                    <i class="bi bi-info-circle"></i> 
                    민원 내용은 구체적으로 작성할수록 빠른 처리가 가능합니다.
                </div>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">첨부파일</label>
                
                @if($complaint->attachments->count() > 0)
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    기존 첨부파일이 {{ $complaint->attachments->count() }}개 있습니다. 
                    새로운 파일을 추가하거나 기존 파일을 삭제할 수 있습니다.
                </div>
                
                <div class="existing-files mb-3">
                    @foreach($complaint->attachments as $attachment)
                    <div class="file-item existing-file" data-attachment-id="{{ $attachment->id }}">
                        <div class="file-info">
                            <i class="bi bi-file-earmark file-icon"></i>
                            <div>
                                <div>{{ $attachment->original_name }}</div>
                                <div class="file-size">{{ number_format($attachment->file_size / 1024, 2) }} KB</div>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('complaints.download-attachment', [$complaint, $attachment]) }}" 
                               class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-existing-file" 
                                    data-attachment-id="{{ $attachment->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <div class="file-upload-area" id="fileUploadArea">
                    <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="mt-2 mb-0">새 파일을 드래그하거나 클릭하여 업로드</p>
                    <small class="text-muted">최대 5개, 각 10MB 이하 (PDF, DOC, DOCX, JPG, PNG, GIF)</small>
                </div>
                <input type="file" id="fileInput" name="attachments[]" multiple 
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" style="display: none;">
                <div class="file-list" id="fileList"></div>
                
                <!-- 삭제할 첨부파일 ID들 -->
                <input type="hidden" id="deleted_attachments" name="deleted_attachments" value="">
                
                @error('attachments.*')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- 민원인 정보 섹션 -->
        <div class="form-section">
            <h3 class="section-title">민원인 정보</h3>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="complainant_name" class="form-label required">성명</label>
                    <input type="text" class="form-control @error('complainant_name') is-invalid @enderror" 
                           id="complainant_name" name="complainant_name" 
                           value="{{ old('complainant_name', $complaint->complainant_name) }}" required>
                    @error('complainant_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="complainant_email" class="form-label required">이메일</label>
                    <input type="email" class="form-control @error('complainant_email') is-invalid @enderror" 
                           id="complainant_email" name="complainant_email" 
                           value="{{ old('complainant_email', $complaint->complainant_email) }}" required>
                    <div class="validation-feedback" id="emailValidation"></div>
                    @error('complainant_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="complainant_phone" class="form-label">연락처</label>
                    <input type="tel" class="form-control @error('complainant_phone') is-invalid @enderror" 
                           id="complainant_phone" name="complainant_phone" 
                           value="{{ old('complainant_phone', $complaint->complainant_phone) }}"
                           placeholder="010-0000-0000">
                    <div class="validation-feedback" id="phoneValidation"></div>
                    @error('complainant_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 제출 섹션 -->
        <div class="submit-section">
            <div class="last-modified">
                마지막 수정: {{ $complaint->updated_at->format('Y-m-d H:i:s') }}
            </div>
            <div>
                <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-outline-secondary me-2">
                    취소
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="spinner-border spinner-border-sm loading-spinner" role="status" aria-hidden="true"></span>
                    변경사항 저장
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// 전역 변수
let selectedFiles = [];
let deletedAttachments = [];
let existingFileCount = {{ $complaint->attachments->count() }};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    initializeFileUpload();
    initializeValidation();
    initializeExistingFiles();
});

// 폼 초기화
function initializeForm() {
    // 카테고리 선택
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.category-item').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('category_id').value = this.dataset.categoryId;
        });
    });

    // 우선순위 선택
    document.querySelectorAll('.priority-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.priority-option').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('priority').value = this.dataset.priority;
        });
    });

    // 문자 수 카운터
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');

    titleInput.addEventListener('input', function() {
        document.getElementById('titleCharCount').textContent = this.value.length;
    });

    descriptionInput.addEventListener('input', function() {
        document.getElementById('descriptionCharCount').textContent = this.value.length;
    });

    // 초기 카운트 설정
    document.getElementById('titleCharCount').textContent = titleInput.value.length;
    document.getElementById('descriptionCharCount').textContent = descriptionInput.value.length;
}

// 파일 업로드 초기화
function initializeFileUpload() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('fileInput');

    // 클릭으로 파일 선택
    fileUploadArea.addEventListener('click', () => fileInput.click());

    // 파일 선택 이벤트
    fileInput.addEventListener('change', handleFileSelect);

    // 드래그 앤 드롭
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileUploadArea.classList.add('dragging');
    });

    fileUploadArea.addEventListener('dragleave', () => {
        fileUploadArea.classList.remove('dragging');
    });

    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileUploadArea.classList.remove('dragging');
        handleFiles(e.dataTransfer.files);
    });
}

// 기존 파일 초기화
function initializeExistingFiles() {
    document.querySelectorAll('.remove-existing-file').forEach(btn => {
        btn.addEventListener('click', function() {
            const attachmentId = this.dataset.attachmentId;
            const fileItem = this.closest('.file-item');
            
            if (confirm('이 파일을 삭제하시겠습니까?')) {
                deletedAttachments.push(attachmentId);
                fileItem.style.opacity = '0.5';
                fileItem.style.textDecoration = 'line-through';
                this.disabled = true;
                
                // hidden input 업데이트
                document.getElementById('deleted_attachments').value = deletedAttachments.join(',');
                
                existingFileCount--;
                checkFileLimit();
            }
        });
    });
}

// 파일 선택 처리
function handleFileSelect(e) {
    handleFiles(e.target.files);
}

// 파일 처리
function handleFiles(files) {
    const maxFiles = 5;
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                         'image/jpeg', 'image/png', 'image/gif'];

    const totalFiles = existingFileCount + selectedFiles.length;

    Array.from(files).forEach(file => {
        // 파일 개수 체크
        if (totalFiles + selectedFiles.length >= maxFiles) {
            showNotification('첨부파일은 총 5개까지만 업로드할 수 있습니다.', 'warning');
            return;
        }

        // 파일 크기 체크
        if (file.size > maxSize) {
            showNotification(`${file.name}: 파일 크기가 10MB를 초과합니다.`, 'error');
            return;
        }

        // 파일 타입 체크
        if (!allowedTypes.includes(file.type)) {
            showNotification(`${file.name}: 허용되지 않는 파일 형식입니다.`, 'error');
            return;
        }

        // 중복 체크
        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            showNotification(`${file.name}: 이미 추가된 파일입니다.`, 'warning');
            return;
        }

        selectedFiles.push(file);
    });

    updateFileList();
    checkFileLimit();
}

// 파일 목록 업데이트
function updateFileList() {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <div class="file-info">
                <i class="bi ${getFileIcon(file.type)} file-icon"></i>
                <div>
                    <div>${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
            </div>
            <i class="bi bi-x-circle remove-file" onclick="removeFile(${index})"></i>
        `;
        fileList.appendChild(fileItem);
    });

    // 파일 input 업데이트
    updateFileInput();
}

// 파일 제거
function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
    checkFileLimit();
}

// 파일 input 업데이트
function updateFileInput() {
    const fileInput = document.getElementById('fileInput');
    const dataTransfer = new DataTransfer();
    
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    
    fileInput.files = dataTransfer.files;
}

// 파일 개수 제한 체크
function checkFileLimit() {
    const totalFiles = existingFileCount - deletedAttachments.length + selectedFiles.length;
    const fileUploadArea = document.getElementById('fileUploadArea');
    
    if (totalFiles >= 5) {
        fileUploadArea.style.opacity = '0.5';
        fileUploadArea.style.pointerEvents = 'none';
        fileUploadArea.querySelector('small').textContent = '파일 개수 제한에 도달했습니다.';
    } else {
        fileUploadArea.style.opacity = '1';
        fileUploadArea.style.pointerEvents = 'auto';
        fileUploadArea.querySelector('small').textContent = '최대 5개, 각 10MB 이하 (PDF, DOC, DOCX, JPG, PNG, GIF)';
    }
}

// 파일 아이콘 가져오기
function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return 'bi-file-image';
    if (mimeType.includes('pdf')) return 'bi-file-pdf';
    if (mimeType.includes('word')) return 'bi-file-word';
    return 'bi-file-earmark';
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 유효성 검사 초기화
function initializeValidation() {
    // 이메일 검증
    const emailInput = document.getElementById('complainant_email');
    emailInput.addEventListener('blur', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const feedback = document.getElementById('emailValidation');
        
        if (emailRegex.test(this.value)) {
            feedback.className = 'validation-feedback valid';
            feedback.textContent = '올바른 이메일 형식입니다.';
        } else {
            feedback.className = 'validation-feedback invalid';
            feedback.textContent = '올바른 이메일 주소를 입력해주세요.';
        }
    });

    // 전화번호 검증
    const phoneInput = document.getElementById('complainant_phone');
    phoneInput.addEventListener('input', function() {
        // 자동 하이픈 추가
        let value = this.value.replace(/[^0-9]/g, '');
        if (value.length >= 4 && value.length <= 7) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else if (value.length >= 8) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
        }
        this.value = value;
        
        // 유효성 검사
        const phoneRegex = /^010-\d{4}-\d{4}$/;
        const feedback = document.getElementById('phoneValidation');
        
        if (value && phoneRegex.test(value)) {
            feedback.className = 'validation-feedback valid';
            feedback.textContent = '올바른 전화번호 형식입니다.';
        } else if (value) {
            feedback.className = 'validation-feedback invalid';
            feedback.textContent = '010-0000-0000 형식으로 입력해주세요.';
        } else {
            feedback.className = 'validation-feedback';
        }
    });

    // 폼 제출 검증
    document.getElementById('complaintForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        // 로딩 표시
        const submitBtn = document.getElementById('submitBtn');
        const spinner = submitBtn.querySelector('.loading-spinner');
        spinner.style.display = 'inline-block';
        submitBtn.disabled = true;
        
        // 폼 제출
        this.submit();
    });
}

// 폼 유효성 검사
function validateForm() {
    // 필수 필드 체크
    const title = document.getElementById('title').value.trim();
    const categoryId = document.getElementById('category_id').value;
    const description = document.getElementById('description').value.trim();
    const name = document.getElementById('complainant_name').value.trim();
    const email = document.getElementById('complainant_email').value.trim();

    if (!title) {
        showNotification('민원 제목을 입력해주세요.', 'error');
        document.getElementById('title').focus();
        return false;
    }

    if (!categoryId) {
        showNotification('카테고리를 선택해주세요.', 'error');
        return false;
    }

    if (!description) {
        showNotification('민원 내용을 입력해주세요.', 'error');
        document.getElementById('description').focus();
        return false;
    }

    if (!name) {
        showNotification('성명을 입력해주세요.', 'error');
        document.getElementById('complainant_name').focus();
        return false;
    }

    if (!email) {
        showNotification('이메일을 입력해주세요.', 'error');
        document.getElementById('complainant_email').focus();
        return false;
    }

    return true;
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
