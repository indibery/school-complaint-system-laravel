@extends('layouts.app')

@section('title', '대시보드 - 학교 민원 관리 시스템')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">대시보드</h1>
        <p class="text-muted mb-0">
            안녕하세요, {{ auth()->user()->name }}님! 
            @if(auth()->user()->hasRole('parent'))
                오늘도 좋은 하루 되세요.
            @elseif(auth()->user()->hasRole(['teacher', 'staff']))
                오늘 처리할 민원이 {{ $myComplaints ? $myComplaints->count() : 0 }}건 있습니다.
            @else
                민원 관리 현황을 확인하세요.
            @endif
        </p>
    </div>
    <div class="text-end">
        <small class="text-muted">
            마지막 업데이트: {{ now()->format('Y-m-d H:i') }}
        </small>
    </div>
</div>

<!-- 통계 카드 -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-file-earmark-text fs-1 opacity-75"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fs-2 fw-bold">{{ number_format($stats['total']) }}</div>
                    <div class="small">전체 민원</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-clock fs-1 opacity-75"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fs-2 fw-bold">{{ number_format($stats['pending']) }}</div>
                    <div class="small">접수 대기</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-gear fs-1 opacity-75"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fs-2 fw-bold">{{ number_format($stats['in_progress']) }}</div>
                    <div class="small">처리 중</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle fs-1 opacity-75"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fs-2 fw-bold">{{ number_format($stats['resolved']) }}</div>
                    <div class="small">해결 완료</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 빠른 통계 -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle text-danger fs-1 mb-2"></i>
                <h5>긴급 민원</h5>
                <div class="fs-3 fw-bold text-danger">{{ number_format($stats['urgent']) }}</div>
                <small class="text-muted">즉시 처리 필요</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-calendar-event text-primary fs-1 mb-2"></i>
                <h5>오늘 접수</h5>
                <div class="fs-3 fw-bold text-primary">{{ number_format($stats['today']) }}</div>
                <small class="text-muted">금일 신규 민원</small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-calendar-week text-success fs-1 mb-2"></i>
                <h5>이번 주</h5>
                <div class="fs-3 fw-bold text-success">{{ number_format($stats['this_week']) }}</div>
                <small class="text-muted">주간 총 민원</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 나의 담당 민원 (교직원만) -->
    @if($myComplaints !== null)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-person-check me-2"></i>
                    나의 담당 민원
                </h5>
                <a href="{{ route('complaints.index', ['assigned_to' => auth()->id()]) }}" class="btn btn-sm btn-outline-primary">
                    전체 보기
                </a>
            </div>
            <div class="card-body p-0">
                @if($myComplaints->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($myComplaints as $complaint)
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="{{ route('complaints.show', $complaint) }}" class="text-decoration-none">
                                            {{ Str::limit($complaint->title, 40) }}
                                        </a>
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        {{ $complaint->complainant->name ?? '알 수 없음' }} · 
                                        {{ $complaint->category->name ?? '미분류' }}
                                    </p>
                                    <small class="text-muted">{{ $complaint->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-{{ $complaint->status }}">
                                        {{ $complaint->status_label }}
                                    </span>
                                    @if($complaint->priority === 'urgent')
                                        <div class="mt-1">
                                            <span class="badge bg-danger">긴급</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success fs-1 mb-2"></i>
                        <p class="text-muted mb-0">담당 민원이 없습니다.</p>
                        <small class="text-muted">새로운 민원이 배정되면 여기에 표시됩니다.</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    <!-- 최근 민원 -->
    <div class="col-lg-{{ $myComplaints !== null ? '6' : '12' }}">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    최근 민원
                </h5>
                <a href="{{ route('complaints.index') }}" class="btn btn-sm btn-outline-primary">
                    전체 보기
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentComplaints->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentComplaints->take(8) as $complaint)
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="{{ route('complaints.show', $complaint) }}" class="text-decoration-none">
                                            {{ Str::limit($complaint->title, 50) }}
                                        </a>
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        제기자: {{ $complaint->complainant->name ?? '알 수 없음' }}
                                        @if($complaint->assignedTo)
                                            · 담당자: {{ $complaint->assignedTo->name }}
                                        @endif
                                    </p>
                                    <small class="text-muted">
                                        {{ $complaint->category->name ?? '미분류' }} · 
                                        {{ $complaint->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-{{ $complaint->status }}">
                                        {{ $complaint->status_label }}
                                    </span>
                                    @if($complaint->priority === 'urgent')
                                        <div class="mt-1">
                                            <span class="badge bg-danger">긴급</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted fs-1 mb-2"></i>
                        <p class="text-muted mb-0">민원이 없습니다.</p>
                        <small class="text-muted">새로운 민원이 접수되면 여기에 표시됩니다.</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 빠른 액션 (하단 플로팅 버튼) -->
@if(auth()->user()->hasRole(['parent', 'teacher', 'staff']))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
    <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-lg rounded-circle shadow-lg" data-bs-toggle="tooltip" data-bs-placement="left" title="새 민원 작성">
        <i class="bi bi-plus-lg fs-4"></i>
    </a>
</div>
@endif
@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
    }
    
    .bg-primary { background: linear-gradient(135deg, #2c5aa0, #1e3d72) !important; }
    .bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800) !important; }
    .bg-info { background: linear-gradient(135deg, #0dcaf0, #0aa8cc) !important; }
    .bg-success { background: linear-gradient(135deg, #198754, #146c43) !important; }
    
    .list-group-item-action:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
        transition: all 0.2s ease;
    }
    
    .btn-lg.rounded-circle {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-lg.rounded-circle:hover {
        transform: scale(1.1);
        transition: transform 0.2s ease;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 툴팁 초기화
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 통계 카드 애니메이션
    $('.card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate__animated animate__fadeInUp');
    });
});
</script>
@endpush
