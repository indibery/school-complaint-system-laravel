<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Complaint\ComplaintStoreRequest;
use App\Http\Requests\Api\Complaint\ComplaintUpdateRequest;
use App\Http\Requests\Api\Complaint\ComplaintIndexRequest;
use App\Http\Requests\Api\Complaint\ComplaintStatusRequest;
use App\Http\Requests\Api\Complaint\ComplaintAssignRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ComplaintController extends BaseApiController
{
    /**
     * Display a listing of the complaints.
     */
    public function index(ComplaintIndexRequest $request): JsonResponse
    {
        try {
            $query = Complaint::with(['category', 'department', 'complainant', 'assignedTo', 'student']);
            
            // 권한 기반 접근 제어
            $this->applyAccessControl($query, $request);
            
            // 필터링 적용
            $this->applyFilters($query, $request);
            
            // 정렬 적용
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            [$sortBy, $sortOrder] = $this->validateSortParameters($sortBy, $sortOrder);
            $query->orderBy($sortBy, $sortOrder);
            
            // 페이지네이션
            $perPage = min($request->input('per_page', 20), $this->maxPerPage);
            $complaints = $query->paginate($perPage);
            
            // 통계 정보 추가 (관리자만)
            $meta = [];
            if ($request->input('with_statistics') && $request->user()->hasRole('admin')) {
                $meta['statistics'] = $this->getComplaintStatistics($request);
            }
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '민원 목록을 조회했습니다.',
                $meta
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(ComplaintStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->sanitized();
            
            // 민원 번호 생성
            $data['complaint_number'] = $this->generateComplaintNumber();
            
            // 작성자 정보 추가
            $data['created_by'] = $request->user()->id;
            
            // 학생 정보 추가 (학부모가 작성하는 경우)
            if ($request->user()->hasRole('parent') && $request->has('student_id')) {
                $data['student_id'] = $request->input('student_id');
            }
            
            // 민원 생성
            $complaint = Complaint::create($data);
            
            // 태그 저장
            if ($request->has('tags')) {
                $complaint->syncTags($request->input('tags'));
            }
            
            // 첨부파일 처리
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($complaint, $request->file('attachments'));
            }
            
            // 자동 할당 로직
            $this->autoAssignComplaint($complaint);
            
            DB::commit();
            
            return $this->createdResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원이 성공적으로 접수되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 접수 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified complaint.
     */
    public function show(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewComplaint($request->user(), $complaint)) {
                return $this->errorResponse(
                    '해당 민원을 조회할 권한이 없습니다.',
                    403
                );
            }
            
            $complaint->load([
                'category', 
                'department', 
                'complainant', 
                'assignedTo', 
                'student', 
                'attachments',
                'comments.author',
                'statusHistory.changedBy'
            ]);
            
            // 조회수 증가
            $complaint->incrementViews();
            
            return $this->successResponse(
                new ComplaintResource($complaint),
                '민원 정보를 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 정보 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified complaint in storage.
     */
    public function update(ComplaintUpdateRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->sanitized();
            
            // 수정자 정보 추가
            $data['updated_by'] = $request->user()->id;
            
            // 민원 정보 업데이트
            $complaint->update($data);
            
            // 태그 업데이트
            if ($request->has('tags')) {
                $complaint->syncTags($request->input('tags'));
            }
            
            // 첨부파일 처리
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($complaint, $request->file('attachments'));
            }
            
            // 첨부파일 삭제
            if ($request->has('remove_attachments')) {
                $this->removeAttachments($complaint, $request->input('remove_attachments'));
            }
            
            // 수정 이력 저장
            $this->logComplaintHistory($complaint, 'updated', '민원 정보가 수정되었습니다.', $request->user());
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원 정보가 성공적으로 수정되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 정보 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified complaint from storage.
     */
    public function destroy(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canDeleteComplaint($request->user(), $complaint)) {
                return $this->errorResponse(
                    '민원을 삭제할 권한이 없습니다.',
                    403
                );
            }
            
            // 삭제 불가 상태 체크
            if (in_array($complaint->status, ['in_progress', 'resolved'])) {
                return $this->errorResponse(
                    '진행 중이거나 해결된 민원은 삭제할 수 없습니다.',
                    400
                );
            }
            
            DB::beginTransaction();
            
            // 이력 저장
            $this->logComplaintHistory($complaint, 'deleted', '민원이 삭제되었습니다.', $request->user());
            
            // 소프트 삭제 실행
            $complaint->delete();
            
            DB::commit();
            
            return $this->deletedResponse(
                '민원이 성공적으로 삭제되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get my complaints.
     */
    public function myComplaints(Request $request): JsonResponse
    {
        try {
            $query = Complaint::with(['category', 'department', 'assignedTo'])
                ->where('created_by', $request->user()->id);
            
            // 기본 필터링
            $this->applyBasicFilters($query, $request);
            
            // 정렬
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // 페이지네이션
            $perPage = min($request->input('per_page', 20), $this->maxPerPage);
            $complaints = $query->paginate($perPage);
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '내 민원 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '내 민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get complaints assigned to me.
     */
    public function assignedToMe(Request $request): JsonResponse
    {
        try {
            $query = Complaint::with(['category', 'department', 'complainant', 'student'])
                ->where('assigned_to', $request->user()->id);
            
            // 기본 필터링
            $this->applyBasicFilters($query, $request);
            
            // 정렬
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // 페이지네이션
            $perPage = min($request->input('per_page', 20), $this->maxPerPage);
            $complaints = $query->paginate($perPage);
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '내 할당 민원 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '내 할당 민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Apply access control based on user role.
     */
    private function applyAccessControl(Builder $query, Request $request): void
    {
        $user = $request->user();
        
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return;
        }
        
        // 교사/직원은 할당받은 민원과 본인이 작성한 민원 조회 가능
        if ($user->hasRole(['teacher', 'staff'])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('department_id', $user->department_id);
            });
            return;
        }
        
        // 학부모는 본인과 자녀 관련 민원만 조회 가능
        if ($user->hasRole('parent')) {
            $studentIds = $user->children()->pluck('id');
            $query->where(function ($q) use ($user, $studentIds) {
                $q->where('created_by', $user->id)
                  ->orWhereIn('student_id', $studentIds);
            });
            return;
        }
        
        // 학생은 본인 관련 민원만 조회 가능
        if ($user->hasRole('student')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('student_id', $user->id);
            });
            return;
        }
        
        // 기본적으로 본인이 작성한 민원만 조회 가능
        $query->where('created_by', $user->id);
    }

    /**
     * Apply filters to the complaint query.
     */
    private function applyFilters(Builder $query, ComplaintIndexRequest $request): void
    {
        // 검색어 필터
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('complaint_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 우선순위 필터
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // 카테고리 필터
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 부서 필터
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // 담당자 필터
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        // 작성자 필터
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        // 공개 여부 필터
        if ($request->filled('is_public')) {
            $query->where('is_public', $request->input('is_public'));
        }

        // 익명 여부 필터
        if ($request->filled('is_anonymous')) {
            $query->where('is_anonymous', $request->input('is_anonymous'));
        }

        // 첨부파일 여부 필터
        if ($request->filled('has_attachments')) {
            if ($request->input('has_attachments')) {
                $query->whereHas('attachments');
            } else {
                $query->whereDoesntHave('attachments');
            }
        }

        // 기한 초과 필터
        if ($request->filled('overdue')) {
            if ($request->input('overdue')) {
                $query->where('due_date', '<', now())
                      ->whereNotIn('status', ['resolved', 'closed', 'cancelled']);
            }
        }

        // 태그 필터
        if ($request->filled('tags')) {
            $tags = $request->input('tags');
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // 위치 필터
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->input('location')}%");
        }

        // 사건 날짜 필터
        if ($request->filled('incident_date_from')) {
            $query->whereDate('incident_date', '>=', $request->input('incident_date_from'));
        }

        if ($request->filled('incident_date_to')) {
            $query->whereDate('incident_date', '<=', $request->input('incident_date_to'));
        }

        // 피해 금액 필터
        if ($request->filled('damage_amount_min')) {
            $query->whereJsonContains('metadata->damage_amount', '>=', $request->input('damage_amount_min'));
        }

        if ($request->filled('damage_amount_max')) {
            $query->whereJsonContains('metadata->damage_amount', '<=', $request->input('damage_amount_max'));
        }

        // 날짜 범위 필터
        if ($request->filled('created_at_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_at_from'));
        }

        if ($request->filled('created_at_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_at_to'));
        }

        // 내 민원만 필터
        if ($request->input('only_my_complaints')) {
            $query->where('created_by', $request->user()->id);
        }

        // 내 할당 민원만 필터
        if ($request->input('only_assigned_to_me')) {
            $query->where('assigned_to', $request->user()->id);
        }
    }

    /**
     * Apply basic filters.
     */
    private function applyBasicFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
    }

    /**
     * Get available sort columns.
     */
    private function getAvailableSortColumns(): array
    {
        return [
            'id', 'complaint_number', 'title', 'status', 'priority',
            'created_at', 'updated_at', 'due_date', 'resolved_at',
            'category_id', 'department_id', 'assigned_to', 'created_by'
        ];
    }

    /**
     * Validate sort parameters.
     */
    private function validateSortParameters(string $sortBy, string $sortOrder): array
    {
        $availableColumns = $this->getAvailableSortColumns();
        $sortBy = in_array($sortBy, $availableColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        return [$sortBy, $sortOrder];
    }

    /**
     * Generate complaint number.
     */
    private function generateComplaintNumber(): string
    {
        $prefix = 'C' . date('Ymd');
        $lastComplaint = Complaint::where('complaint_number', 'like', $prefix . '%')
            ->orderBy('complaint_number', 'desc')
            ->first();
        
        if ($lastComplaint) {
            $lastNumber = (int) substr($lastComplaint->complaint_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Handle file attachments.
     */
    private function handleAttachments(Complaint $complaint, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store('complaints/' . $complaint->id, 'public');
            
            $complaint->attachments()->create([
                'original_name' => $attachment->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $attachment->getSize(),
                'file_type' => $attachment->getClientMimeType(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Remove attachments.
     */
    private function removeAttachments(Complaint $complaint, array $attachmentIds): void
    {
        $attachments = $complaint->attachments()->whereIn('id', $attachmentIds)->get();
        
        foreach ($attachments as $attachment) {
            // 파일 삭제
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            
            // 레코드 삭제
            $attachment->delete();
        }
    }

    /**
     * Auto assign complaint based on category and department.
     */
    private function autoAssignComplaint(Complaint $complaint): void
    {
        // 카테고리 기반 자동 할당 규칙
        if ($complaint->category && $complaint->category->default_assignee_id) {
            $complaint->update([
                'assigned_to' => $complaint->category->default_assignee_id,
                'status' => 'assigned'
            ]);
            return;
        }

        // 부서 기반 자동 할당 규칙
        if ($complaint->department && $complaint->department->head_id) {
            $complaint->update([
                'assigned_to' => $complaint->department->head_id,
                'status' => 'assigned'
            ]);
            return;
        }

        // 우선순위 기반 자동 할당
        if ($complaint->priority === 'urgent') {
            $adminUser = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->first();
            
            if ($adminUser) {
                $complaint->update([
                    'assigned_to' => $adminUser->id,
                    'status' => 'assigned'
                ]);
            }
        }
    }

    /**
     * Check if user can view complaint.
     */
    private function canViewComplaint($user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 작성자는 본인 민원 조회 가능
        if ($complaint->created_by === $user->id) {
            return true;
        }

        // 공개 민원은 관련 사용자들이 조회 가능
        if ($complaint->is_public) {
            // 같은 부서 직원
            if ($user->department_id === $complaint->department_id) {
                return true;
            }
            
            // 학부모는 자녀 관련 민원 조회 가능
            if ($user->hasRole('parent') && $complaint->student_id) {
                $childrenIds = $user->children()->pluck('id');
                return $childrenIds->contains($complaint->student_id);
            }
            
            // 학생은 본인 관련 민원 조회 가능
            if ($user->hasRole('student') && $complaint->student_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can delete complaint.
     */
    private function canDeleteComplaint($user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 본인 민원 삭제 가능 (접수 상태일 때만)
        if ($complaint->created_by === $user->id && $complaint->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Log complaint history.
     */
    private function logComplaintHistory(Complaint $complaint, string $action, string $description, $user): void
    {
        $complaint->statusHistory()->create([
            'action' => $action,
            'description' => $description,
            'changed_by' => $user->id,
            'changed_at' => now(),
            'metadata' => [
                'user_name' => $user->name,
                'user_role' => $user->roles->pluck('name')->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        ]);
    }

    /**
     * Get complaint statistics.
     */
    private function getComplaintStatistics(Request $request): array
    {
        $baseQuery = Complaint::query();
        
        // 권한 기반 필터링 적용
        $this->applyAccessControl($baseQuery, $request);
        
        return [
            'total_complaints' => $baseQuery->count(),
            'status_breakdown' => [
                'pending' => $baseQuery->where('status', 'pending')->count(),
                'assigned' => $baseQuery->where('status', 'assigned')->count(),
                'in_progress' => $baseQuery->where('status', 'in_progress')->count(),
                'resolved' => $baseQuery->where('status', 'resolved')->count(),
                'closed' => $baseQuery->where('status', 'closed')->count(),
                'cancelled' => $baseQuery->where('status', 'cancelled')->count(),
            ],
            'priority_breakdown' => [
                'low' => $baseQuery->where('priority', 'low')->count(),
                'normal' => $baseQuery->where('priority', 'normal')->count(),
                'high' => $baseQuery->where('priority', 'high')->count(),
                'urgent' => $baseQuery->where('priority', 'urgent')->count(),
            ],
            'overdue_complaints' => $baseQuery->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->count(),
            'recent_complaints' => $baseQuery->where('created_at', '>=', now()->subDays(7))->count(),
            'resolved_this_month' => $baseQuery->where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)
                ->count(),
        ];
    }
}

    /**
     * Update complaint status.
     */
    public function updateStatus(ComplaintStatusRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $oldStatus = $complaint->status;
            $newStatus = $data['status'];
            
            // 상태 업데이트
            $complaint->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => $request->user()->id,
            ]);
            
            // 상태별 추가 처리
            $this->handleStatusChange($complaint, $oldStatus, $newStatus, $data);
            
            // 상태 변경 이력 저장
            $this->logComplaintHistory(
                $complaint,
                'status_changed',
                "상태가 '{$this->getStatusDisplay($oldStatus)}'에서 '{$this->getStatusDisplay($newStatus)}'로 변경되었습니다. 사유: " . $data['reason'],
                $request->user()
            );
            
            // 알림 처리
            if ($data['notify_submitter']) {
                $this->notifyComplaintSubmitter($complaint, 'status_changed', $data);
            }
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원 상태가 성공적으로 변경되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Assign complaint to user.
     */
    public function assign(ComplaintAssignRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $oldAssignee = $complaint->assignedTo;
            
            // 할당 정보 업데이트
            $complaint->update([
                'assigned_to' => $data['assigned_to'],
                'department_id' => $data['department_id'] ?? $complaint->department_id,
                'priority' => $data['priority'] ?? $complaint->priority,
                'due_date' => $data['due_date'] ?? $complaint->due_date,
                'status' => $complaint->status === 'pending' ? 'assigned' : $complaint->status,
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);
            
            // 할당 메타데이터 업데이트
            $metadata = $complaint->metadata ?? [];
            $metadata['assignment'] = [
                'assigned_at' => now()->toISOString(),
                'assigned_by' => $request->user()->id,
                'assigned_by_name' => $request->user()->name,
                'assignment_note' => $data['assignment_note'],
                'escalation_level' => $data['escalation_level'] ?? 1,
                'requires_approval' => $data['requires_approval'] ?? false,
                'auto_reassign_if_overdue' => $data['auto_reassign_if_overdue'] ?? false,
                'reassign_after_days' => $data['reassign_after_days'] ?? null,
            ];
            $complaint->update(['metadata' => $metadata]);
            
            // 할당 이력 저장
            $assigneeName = User::find($data['assigned_to'])->name;
            $this->logComplaintHistory(
                $complaint,
                'assigned',
                "담당자가 '{$assigneeName}'로 할당되었습니다. 메모: " . $data['assignment_note'],
                $request->user()
            );
            
            // 알림 처리
            if ($data['notify_assignee']) {
                $this->notifyComplaintAssignee($complaint, $data);
            }
            
            if ($data['notify_submitter']) {
                $this->notifyComplaintSubmitter($complaint, 'assigned', $data);
            }
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원이 성공적으로 할당되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 할당 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Reassign complaint to another user.
     */
    public function reassign(ComplaintAssignRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            $oldAssignee = $complaint->assignedTo;
            $newAssignee = User::find($data['assigned_to']);
            
            // 재할당 정보 업데이트
            $complaint->update([
                'assigned_to' => $data['assigned_to'],
                'department_id' => $data['department_id'] ?? $complaint->department_id,
                'priority' => $data['priority'] ?? $complaint->priority,
                'due_date' => $data['due_date'] ?? $complaint->due_date,
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);
            
            // 재할당 이력 저장
            $oldAssigneeName = $oldAssignee ? $oldAssignee->name : '미할당';
            $newAssigneeName = $newAssignee->name;
            
            $this->logComplaintHistory(
                $complaint,
                'reassigned',
                "담당자가 '{$oldAssigneeName}'에서 '{$newAssigneeName}'로 재할당되었습니다. 메모: " . $data['assignment_note'],
                $request->user()
            );
            
            // 알림 처리
            if ($data['notify_assignee']) {
                $this->notifyComplaintAssignee($complaint, $data);
            }
            
            if ($data['notify_submitter']) {
                $this->notifyComplaintSubmitter($complaint, 'reassigned', $data);
            }
            
            // 이전 담당자에게 알림
            if ($oldAssignee) {
                $this->notifyComplaintReassignment($complaint, $oldAssignee, $newAssignee);
            }
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원이 성공적으로 재할당되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 재할당 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update complaint priority.
     */
    public function updatePriority(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $request->validate([
                'priority' => 'required|string|in:low,normal,high,urgent',
                'reason' => 'required|string|max:500',
                'notify_assignee' => 'boolean',
                'notify_submitter' => 'boolean',
            ]);
            
            // 권한 체크
            if (!$request->user()->hasRole('admin') && $complaint->assigned_to !== $request->user()->id) {
                return $this->errorResponse(
                    '민원 우선순위를 변경할 권한이 없습니다.',
                    403
                );
            }
            
            DB::beginTransaction();
            
            $oldPriority = $complaint->priority;
            $newPriority = $request->input('priority');
            
            // 우선순위 업데이트
            $complaint->update([
                'priority' => $newPriority,
                'priority_changed_at' => now(),
                'priority_changed_by' => $request->user()->id,
            ]);
            
            // 긴급으로 변경 시 특별 처리
            if ($newPriority === 'urgent') {
                $this->handleUrgentPriority($complaint);
            }
            
            // 우선순위 변경 이력 저장
            $this->logComplaintHistory(
                $complaint,
                'priority_changed',
                "우선순위가 '{$this->getPriorityDisplay($oldPriority)}'에서 '{$this->getPriorityDisplay($newPriority)}'로 변경되었습니다. 사유: " . $request->input('reason'),
                $request->user()
            );
            
            // 알림 처리
            if ($request->input('notify_assignee', false) && $complaint->assignedTo) {
                $this->notifyPriorityChange($complaint, $oldPriority, $newPriority);
            }
            
            if ($request->input('notify_submitter', false)) {
                $this->notifyComplaintSubmitter($complaint, 'priority_changed', $request->validated());
            }
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원 우선순위가 성공적으로 변경되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 우선순위 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Transfer complaint to another user or department.
     */
    public function transfer(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $request->validate([
                'transfer_type' => 'required|in:user,department,escalate',
                'transfer_to' => 'required|integer',
                'reason' => 'nullable|string|max:1000',
                'notify' => 'boolean',
            ]);
            
            // 권한 체크
            if (!$this->canTransferComplaint($request->user(), $complaint)) {
                return $this->errorResponse(
                    '민원을 이관할 권한이 없습니다.',
                    403
                );
            }
            
            DB::beginTransaction();
            
            $transferService = app(\App\Services\ComplaintTransferService::class);
            $transferType = $request->input('transfer_type');
            $transferTo = $request->input('transfer_to');
            $reason = $request->input('reason');
            $user = $request->user();
            
            $result = false;
            
            switch ($transferType) {
                case 'user':
                    $result = $transferService->transferToUser($complaint, $transferTo, $reason, $user);
                    break;
                case 'department':
                    $result = $transferService->transferToDepartment($complaint, $transferTo, $reason, $user);
                    break;
                case 'escalate':
                    $result = $transferService->escalateToHigherLevel($complaint, $reason, $user);
                    break;
            }
            
            if (!$result) {
                throw new \Exception('이관 처리 중 오류가 발생했습니다.');
            }
            
            // 상태 업데이트
            $complaint->update([
                'status' => 'assigned',
                'updated_at' => now(),
            ]);
            
            DB::commit();
            
            return $this->updatedResponse(
                new ComplaintResource($complaint->load(['category', 'department', 'complainant', 'assignedTo'])),
                '민원이 성공적으로 이관되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '민원 이관 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Bulk status update for multiple complaints.
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'complaint_ids' => 'required|array|min:1|max:100',
                'complaint_ids.*' => 'integer|exists:complaints,id',
                'status' => 'required|string|in:pending,assigned,in_progress,resolved,closed,cancelled',
                'reason' => 'required|string|max:500',
                'notify_submitters' => 'boolean',
            ]);
            
            // 권한 체크
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '대량 상태 변경 권한이 없습니다.',
                    403
                );
            }
            
            DB::beginTransaction();
            
            $complaintIds = $request->input('complaint_ids');
            $newStatus = $request->input('status');
            $reason = $request->input('reason');
            
            $complaints = Complaint::whereIn('id', $complaintIds)->get();
            $updatedCount = 0;
            
            foreach ($complaints as $complaint) {
                $oldStatus = $complaint->status;
                
                // 상태 업데이트
                $complaint->update([
                    'status' => $newStatus,
                    'status_changed_at' => now(),
                    'status_changed_by' => $request->user()->id,
                ]);
                
                // 이력 저장
                $this->logComplaintHistory(
                    $complaint,
                    'bulk_status_changed',
                    "일괄 상태 변경: '{$this->getStatusDisplay($oldStatus)}'에서 '{$this->getStatusDisplay($newStatus)}'로 변경. 사유: {$reason}",
                    $request->user()
                );
                
                // 알림 처리
                if ($request->input('notify_submitters', false)) {
                    $this->notifyComplaintSubmitter($complaint, 'status_changed', [
                        'status' => $newStatus,
                        'reason' => $reason,
                    ]);
                }
                
                $updatedCount++;
            }
            
            DB::commit();
            
            return $this->successResponse(
                ['updated_count' => $updatedCount],
                "{$updatedCount}개의 민원 상태가 성공적으로 변경되었습니다."
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '대량 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Handle status change specific logic.
     */
    private function handleStatusChange(Complaint $complaint, string $oldStatus, string $newStatus, array $data): void
    {
        switch ($newStatus) {
            case 'in_progress':
                $complaint->update([
                    'started_at' => now(),
                    'started_by' => auth()->id(),
                ]);
                break;
                
            case 'resolved':
                $complaint->update([
                    'resolved_at' => now(),
                    'resolved_by' => auth()->id(),
                    'resolution_note' => $data['resolution_note'] ?? null,
                    'resolution_category' => $data['resolution_category'] ?? null,
                ]);
                
                // 만족도 조사 예약
                if ($data['satisfaction_survey'] ?? false) {
                    $this->scheduleSatisfactionSurvey($complaint);
                }
                break;
                
            case 'closed':
                $complaint->update([
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                ]);
                break;
                
            case 'cancelled':
                $complaint->update([
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => $data['reason'],
                ]);
                break;
        }
        
        // 후속 조치 설정
        if ($data['follow_up_required'] ?? false) {
            $this->scheduleFollowUp($complaint, $data['follow_up_date'] ?? null);
        }
    }

    /**
     * Handle urgent priority special processing.
     */
    private function handleUrgentPriority(Complaint $complaint): void
    {
        // 긴급 민원 특별 처리
        $complaint->update([
            'due_date' => now()->addHours(24), // 24시간 내 처리
            'is_urgent' => true,
        ]);
        
        // 관리자에게 즉시 알림
        $this->notifyUrgentComplaint($complaint);
        
        // 자동 상급 이관 (필요시)
        if ($complaint->escalation_level < 2) {
            $this->autoEscalateUrgent($complaint);
        }
    }

    /**
     * Check if user can transfer complaint.
     */
    private function canTransferComplaint(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 이관 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // 담당자는 자신의 민원 이관 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }
        
        // 부서장은 자신의 부서 민원 이관 가능
        if ($user->hasRole('department_head') && $complaint->department_id === $user->department_id) {
            return true;
        }
        
        // 교감/교장은 모든 민원 이관 가능
        if ($user->hasRole(['vice_principal', 'principal'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Auto escalate urgent complaints.
     */
    private function autoEscalateUrgent(Complaint $complaint): void
    {
        $transferService = app(\App\Services\ComplaintTransferService::class);
        $transferService->applyAutoTransferRules($complaint);
    }

    /**
     * Schedule satisfaction survey.
     */
    private function scheduleSatisfactionSurvey(Complaint $complaint): void
    {
        // 만족도 조사 스케줄링 로직
        $surveyData = [
            'complaint_id' => $complaint->id,
            'complainant_id' => $complaint->created_by,
            'scheduled_at' => now()->addDays(1),
            'expires_at' => now()->addDays(7),
            'survey_type' => 'resolution_satisfaction',
        ];
        
        // 만족도 조사 테이블에 저장 (별도 테이블이 있다면)
        // SatisfactionSurvey::create($surveyData);
        
        // 또는 큐에 작업 추가
        // dispatch(new SendSatisfactionSurveyJob($complaint))->delay(now()->addDays(1));
    }

    /**
     * Schedule follow up action.
     */
    private function scheduleFollowUp(Complaint $complaint, ?string $followUpDate): void
    {
        $date = $followUpDate ? Carbon::parse($followUpDate) : now()->addDays(7);
        
        $followUpData = [
            'complaint_id' => $complaint->id,
            'assigned_to' => $complaint->assigned_to,
            'scheduled_at' => $date,
            'follow_up_type' => 'status_check',
            'created_by' => auth()->id(),
        ];
        
        // 후속 조치 테이블에 저장 (별도 테이블이 있다면)
        // FollowUpAction::create($followUpData);
        
        // 또는 큐에 작업 추가
        // dispatch(new FollowUpReminderJob($complaint, $date))->delay($date);
    }

    /**
     * Get status display name.
     */
    private function getStatusDisplay(string $status): string
    {
        $statusMap = [
            'pending' => '접수',
            'assigned' => '할당',
            'in_progress' => '진행중',
            'resolved' => '해결',
            'closed' => '완료',
            'cancelled' => '취소',
        ];
        
        return $statusMap[$status] ?? $status;
    }

    /**
     * Get priority display name.
     */
    private function getPriorityDisplay(string $priority): string
    {
        $priorityMap = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급',
        ];
        
        return $priorityMap[$priority] ?? $priority;
    }

    /**
     * Notify complaint submitter.
     */
    private function notifyComplaintSubmitter(Complaint $complaint, string $type, array $data): void
    {
        $submitter = $complaint->complainant;
        
        if (!$submitter) {
            return;
        }
        
        $notificationData = [
            'complaint_id' => $complaint->id,
            'complaint_number' => $complaint->complaint_number,
            'type' => $type,
            'data' => $data,
            'message' => $this->getNotificationMessage($type, $data),
        ];
        
        // 알림 전송 로직
        // $submitter->notify(new ComplaintStatusNotification($notificationData));
        
        // 또는 큐에 작업 추가
        // dispatch(new SendComplaintNotificationJob($submitter, $notificationData));
    }

    /**
     * Notify complaint assignee.
     */
    private function notifyComplaintAssignee(Complaint $complaint, array $data): void
    {
        $assignee = $complaint->assignedTo;
        
        if (!$assignee) {
            return;
        }
        
        $notificationData = [
            'complaint_id' => $complaint->id,
            'complaint_number' => $complaint->complaint_number,
            'type' => 'assigned',
            'assignment_note' => $data['assignment_note'],
            'due_date' => $data['due_date'] ?? null,
        ];
        
        // 알림 전송 로직
        // $assignee->notify(new ComplaintAssignmentNotification($notificationData));
    }

    /**
     * Notify complaint reassignment.
     */
    private function notifyComplaintReassignment(Complaint $complaint, User $oldAssignee, User $newAssignee): void
    {
        $notificationData = [
            'complaint_id' => $complaint->id,
            'complaint_number' => $complaint->complaint_number,
            'old_assignee' => $oldAssignee->name,
            'new_assignee' => $newAssignee->name,
        ];
        
        // 이전 담당자에게 알림
        // $oldAssignee->notify(new ComplaintReassignmentNotification($notificationData));
        
        // 새 담당자에게 알림
        // $newAssignee->notify(new ComplaintAssignmentNotification($notificationData));
    }

    /**
     * Notify priority change.
     */
    private function notifyPriorityChange(Complaint $complaint, string $oldPriority, string $newPriority): void
    {
        $assignee = $complaint->assignedTo;
        
        if (!$assignee) {
            return;
        }
        
        $notificationData = [
            'complaint_id' => $complaint->id,
            'complaint_number' => $complaint->complaint_number,
            'old_priority' => $this->getPriorityDisplay($oldPriority),
            'new_priority' => $this->getPriorityDisplay($newPriority),
        ];
        
        // 알림 전송 로직
        // $assignee->notify(new ComplaintPriorityChangeNotification($notificationData));
    }

    /**
     * Notify urgent complaint.
     */
    private function notifyUrgentComplaint(Complaint $complaint): void
    {
        $admins = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();
        
        foreach ($admins as $admin) {
            $notificationData = [
                'complaint_id' => $complaint->id,
                'complaint_number' => $complaint->complaint_number,
                'title' => $complaint->title,
                'priority' => 'urgent',
            ];
            
            // 긴급 알림 전송
            // $admin->notify(new UrgentComplaintNotification($notificationData));
        }
    }

    /**
     * Notify escalation.
     */
    private function notifyEscalation(Complaint $complaint, string $reason): void
    {
        $stakeholders = collect([
            $complaint->complainant,
            $complaint->assignedTo,
        ])->filter()->unique('id');
        
        foreach ($stakeholders as $user) {
            $notificationData = [
                'complaint_id' => $complaint->id,
                'complaint_number' => $complaint->complaint_number,
                'escalation_level' => $complaint->escalation_level,
                'escalation_reason' => $reason,
            ];
            
            // 상급 이관 알림 전송
            // $user->notify(new ComplaintEscalationNotification($notificationData));
        }
    }

    /**
     * Get notification message.
     */
    private function getNotificationMessage(string $type, array $data): string
    {
        $messages = [
            'status_changed' => "민원 상태가 '{$this->getStatusDisplay($data['status'])}'로 변경되었습니다.",
            'assigned' => '민원이 담당자에게 할당되었습니다.',
            'reassigned' => '민원 담당자가 변경되었습니다.',
            'priority_changed' => "민원 우선순위가 '{$this->getPriorityDisplay($data['priority'])}'로 변경되었습니다.",
        ];
        
        return $messages[$type] ?? '민원 상태가 업데이트되었습니다.';
    }
    /**
     * Get transfer statistics.
     */
    public function getTransferStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'department_id' => 'nullable|integer|exists:departments,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);
            
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'department_head', 'vice_principal', 'principal'])) {
                return $this->errorResponse(
                    '이관 통계를 조회할 권한이 없습니다.',
                    403
                );
            }
            
            $transferService = app(\App\Services\ComplaintTransferService::class);
            
            $stats = $transferService->getTransferStatistics(
                $request->input('department_id'),
                $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
                $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null
            );
            
            return $this->successResponse($stats, '이관 통계를 조회했습니다.');
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '이관 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get transfer options for a complaint.
     */
    public function getTransferOptions(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canTransferComplaint($request->user(), $complaint)) {
                return $this->errorResponse(
                    '이관 옵션을 조회할 권한이 없습니다.',
                    403
                );
            }
            
            $transferService = app(\App\Services\ComplaintTransferService::class);
            $currentUser = $request->user();
            
            // 이관 가능한 사용자 목록
            $availableUsers = User::where('status', 'active')
                ->where('id', '!=', $currentUser->id)
                ->get()
                ->filter(function ($user) use ($transferService, $currentUser) {
                    return $transferService->canTransferTo($currentUser, $user);
                })
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'department' => $user->department?->name,
                    ];
                });
            
            // 이관 가능한 부서 목록
            $availableDepartments = Department::where('status', 'active')
                ->where('id', '!=', $complaint->department_id)
                ->get()
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'head' => $dept->head?->name,
                    ];
                });
            
            return $this->successResponse([
                'users' => $availableUsers,
                'departments' => $availableDepartments,
                'can_escalate' => $transferService->canTransferTo($currentUser, $currentUser), // 상향 이관 가능 여부
            ], '이관 옵션을 조회했습니다.');
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '이관 옵션 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }
