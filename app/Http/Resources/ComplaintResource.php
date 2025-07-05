<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ComplaintResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        return [
            'id' => $this->id,
            'complaint_number' => $this->complaint_number,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->whenLoaded('category', function () {
                return CategoryResource::summary($this->category);
            }),
            'status' => $this->status,
            'status_display' => $this->formatStatus($this->status),
            'priority' => $this->priority,
            'priority_display' => $this->formatPriority($this->priority),
            'is_urgent' => $this->is_urgent,
            'is_anonymous' => $this->is_anonymous,
            'location' => $this->location,
            'incident_date' => $this->formatDateTime($this->incident_date),
            'incident_date_only' => $this->formatDateOnly($this->incident_date),
            
            // 민원 작성자 정보
            'complainant' => $this->when(
                !$this->is_anonymous || $user?->canHandleComplaints(),
                $this->whenLoaded('complainant', function () {
                    return UserResource::complainant($this->complainant);
                })
            ),
            
            // 담당자 정보
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return UserResource::assignee($this->assignedTo);
            }),
            
            // 관련 학생 정보
            'student' => $this->whenLoaded('student', function () {
                return StudentResource::summary($this->student);
            }),
            
            // 처리 관련 정보
            'assigned_at' => $this->formatDateTime($this->assigned_at),
            'resolved_at' => $this->formatDateTime($this->resolved_at),
            'response_due_date' => $this->formatDateTime($this->response_due_date),
            'is_overdue' => $this->is_overdue,
            'resolution_summary' => $this->resolution_summary,
            'internal_notes' => $this->when(
                $user?->canHandleComplaints(),
                $this->internal_notes
            ),
            
            // 통계 정보
            'comments_count' => $this->whenLoaded('comments', function () {
                return $this->comments->count();
            }),
            
            'public_comments_count' => $this->whenLoaded('comments', function () {
                return $this->comments->where('is_internal', false)->count();
            }),
            
            'attachments_count' => $this->whenLoaded('attachments', function () {
                return $this->attachments->count();
            }),
            
            // 관련 데이터
            'comments' => $this->when(
                $request->has('include_comments'),
                CommentResource::collection($this->whenLoaded('comments'))
            ),
            
            'attachments' => $this->when(
                $request->has('include_attachments'),
                AttachmentResource::collection($this->whenLoaded('attachments'))
            ),
            
            // 진행 상황
            'progress_percentage' => $this->getProgressPercentage(),
            'status_history' => $this->when(
                $user?->canHandleComplaints(),
                $this->whenLoaded('statusHistory', function () {
                    return ComplaintStatusHistoryResource::collection($this->statusHistory);
                })
            ),
            
            // 만족도 조사
            'satisfaction_rating' => $this->satisfaction_rating,
            'satisfaction_comment' => $this->satisfaction_comment,
            'satisfaction_rated_at' => $this->formatDateTime($this->satisfaction_rated_at),
            
            // 접근 권한 정보
            'can_edit' => $this->canEdit($user),
            'can_delete' => $this->canDelete($user),
            'can_assign' => $this->canAssign($user),
            'can_close' => $this->canClose($user),
            'can_rate' => $this->canRate($user),
            
            // 타임스탬프
            ...$this->getTimestamps(),
            
            // 소프트 삭제 정보
            ...$this->withSoftDeletes(),
        ];
    }

    /**
     * 진행률 계산
     */
    protected function getProgressPercentage(): int
    {
        return match($this->status) {
            'pending' => 0,
            'acknowledged' => 20,
            'in_progress' => 50,
            'resolved' => 80,
            'closed' => 100,
            'cancelled' => 0,
            default => 0,
        };
    }

    /**
     * 수정 권한 확인
     */
    protected function canEdit($user): bool
    {
        if (!$user) return false;
        
        return $user->isAdmin() || 
               $user->id === $this->complainant_id ||
               $user->id === $this->assigned_to;
    }

    /**
     * 삭제 권한 확인
     */
    protected function canDelete($user): bool
    {
        if (!$user) return false;
        
        return $user->isAdmin() || 
               ($user->id === $this->complainant_id && in_array($this->status, ['pending', 'acknowledged']));
    }

    /**
     * 담당자 지정 권한 확인
     */
    protected function canAssign($user): bool
    {
        if (!$user) return false;
        
        return $user->isAdmin() || $user->canHandleComplaints();
    }

    /**
     * 완료 권한 확인
     */
    protected function canClose($user): bool
    {
        if (!$user) return false;
        
        return $user->isAdmin() || $user->id === $this->assigned_to;
    }

    /**
     * 평가 권한 확인
     */
    protected function canRate($user): bool
    {
        if (!$user) return false;
        
        return $user->id === $this->complainant_id && 
               $this->status === 'closed' && 
               !$this->satisfaction_rating;
    }

    /**
     * 민원 요약 정보 반환 (목록용)
     */
    public static function summary($resource): array
    {
        $instance = new self($resource);
        
        return [
            'id' => $resource->id,
            'complaint_number' => $resource->complaint_number,
            'title' => $resource->title,
            'status' => $resource->status,
            'status_display' => $instance->formatStatus($resource->status),
            'priority' => $resource->priority,
            'priority_display' => $instance->formatPriority($resource->priority),
            'is_urgent' => $resource->is_urgent,
            'is_anonymous' => $resource->is_anonymous,
            'created_at' => $instance->formatDateTime($resource->created_at),
            'created_at_human' => $instance->formatDateForHumans($resource->created_at),
            'response_due_date' => $instance->formatDateTime($resource->response_due_date),
            'is_overdue' => $resource->is_overdue,
            'progress_percentage' => $instance->getProgressPercentage(),
        ];
    }

    /**
     * 대시보드용 간단 정보 반환
     */
    public static function dashboard($resource): array
    {
        $instance = new self($resource);
        
        return [
            'id' => $resource->id,
            'complaint_number' => $resource->complaint_number,
            'title' => $resource->title,
            'status' => $resource->status,
            'status_display' => $instance->formatStatus($resource->status),
            'priority' => $resource->priority,
            'priority_display' => $instance->formatPriority($resource->priority),
            'is_urgent' => $resource->is_urgent,
            'created_at' => $instance->formatDateTime($resource->created_at),
            'created_at_human' => $instance->formatDateForHumans($resource->created_at),
            'is_overdue' => $resource->is_overdue,
        ];
    }
}
