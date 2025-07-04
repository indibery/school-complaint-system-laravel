{{-- resources/views/complaints/partials/table-rows.blade.php --}}
@forelse($complaints as $complaint)
<tr class="complaint-row" data-id="{{ $complaint->id }}">
    @if(in_array(auth()->user()->role, ['admin', 'teacher', 'security_staff', 'ops_staff']))
    <td>
        <input type="checkbox" class="form-check-input complaint-checkbox" value="{{ $complaint->id }}">
    </td>
    @endif
    <td>
        <strong class="text-primary">#{{ $complaint->complaint_number }}</strong>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <span class="priority-indicator bg-{{ 
                $complaint->priority == 'urgent' ? 'danger' : 
                ($complaint->priority == 'high' ? 'warning' : 
                ($complaint->priority == 'normal' ? 'success' : 'secondary')) 
            }}"></span>
            <a href="{{ route('complaints.show', $complaint) }}" class="text-decoration-none fw-medium">
                {{ Str::limit($complaint->title, 50) }}
            </a>
        </div>
        @if($complaint->attachments_count > 0)
        <small class="text-muted">
            <i class="fas fa-paperclip"></i> {{ $complaint->attachments_count }}개 첨부
        </small>
        @endif
    </td>
    <td>{{ $complaint->user->name ?? '알 수 없음' }}</td>
    <td>
        <span class="badge bg-light text-dark">{{ $complaint->category->name }}</span>
    </td>
    <td>
        <span class="status-badge bg-{{ 
            $complaint->status == 'submitted' ? 'primary' : 
            ($complaint->status == 'resolved' ? 'success' : 
            ($complaint->status == 'closed' ? 'secondary' : 'warning')) 
        }}">
            @switch($complaint->status)
                @case('submitted') 접수 완료 @break
                @case('in_progress') 처리 중 @break
                @case('resolved') 해결 완료 @break
                @case('closed') 종료 @break
                @default {{ $complaint->status }}
            @endswitch
        </span>
    </td>
    <td>
        <span class="badge bg-{{ 
            $complaint->priority == 'urgent' ? 'danger' : 
            ($complaint->priority == 'high' ? 'warning' : 
            ($complaint->priority == 'normal' ? 'success' : 'secondary')) 
        }}">
            @switch($complaint->priority)
                @case('low') 낮음 @break
                @case('normal') 보통 @break
                @case('high') 높음 @break
                @case('urgent') 긴급 @break
                @default {{ $complaint->priority }}
            @endswitch
        </span>
    </td>
    @if(auth()->user()->role === 'admin')
    <td>
        @if($complaint->assignedTo)
        <small class="text-muted">{{ $complaint->assignedTo->name }}</small>
        @else
        <small class="text-muted">미할당</small>
        @endif
    </td>
    @endif
    <td>
        <small class="text-muted">{{ $complaint->created_at->format('m/d H:i') }}</small>
    </td>
    <td>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-cog"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('complaints.show', $complaint) }}">
                    <i class="fas fa-eye"></i> 상세보기
                </a></li>
                @if(auth()->user()->role === 'parent' && $complaint->user_id === auth()->id())
                <li><a class="dropdown-item" href="{{ route('complaints.edit', $complaint) }}">
                    <i class="fas fa-edit"></i> 수정
                </a></li>
                @endif
                @if(in_array(auth()->user()->role, ['admin', 'teacher', 'security_staff', 'ops_staff']))
                <li><hr class="dropdown-divider"></li>
                @if(auth()->user()->role === 'admin')
                <li><a class="dropdown-item quick-assign" href="#" data-id="{{ $complaint->id }}">
                    <i class="fas fa-user-plus"></i> 담당자 할당
                </a></li>
                @endif
                <li><a class="dropdown-item quick-status" href="#" data-id="{{ $complaint->id }}">
                    <i class="fas fa-exchange-alt"></i> 상태 변경
                </a></li>
                @endif
            </ul>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="{{ auth()->user()->role === 'admin' ? '10' : '9' }}" class="text-center py-5">
        <div class="text-muted">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p class="mb-0">조건에 맞는 민원이 없습니다.</p>
        </div>
    </td>
</tr>
@endforelse
