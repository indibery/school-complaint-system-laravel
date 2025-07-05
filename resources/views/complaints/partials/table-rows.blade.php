{{-- resources/views/complaints/partials/table-rows.blade.php --}}
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
