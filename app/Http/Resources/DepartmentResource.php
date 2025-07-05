<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'location' => $this->location,
            'budget' => $this->budget,
            'established_date' => $this->established_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // 부서장 정보
            'head_id' => $this->head_id,
            'head' => $this->whenLoaded('head', function () {
                return [
                    'id' => $this->head->id,
                    'name' => $this->head->name,
                    'email' => $this->head->email,
                    'role' => $this->head->role,
                    'avatar' => $this->head->avatar,
                ];
            }),
            
            // 부서원 정보
            'members_count' => $this->whenCounted('members'),
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->role,
                        'status' => $member->status,
                        'is_head' => $member->id === $this->head_id,
                        'joined_at' => $member->created_at->toDateString(),
                    ];
                });
            }),
            
            // 민원 관련 정보
            'complaints_count' => $this->whenCounted('complaints'),
            'active_complaints_count' => $this->whenCounted('active_complaints'),
            'recent_complaints' => $this->when(
                $this->relationLoaded('complaints') && $request->input('with_recent_complaints'),
                function () {
                    return $this->complaints->take(5)->map(function ($complaint) {
                        return [
                            'id' => $complaint->id,
                            'title' => $complaint->title,
                            'status' => $complaint->status,
                            'priority' => $complaint->priority,
                            'created_at' => $complaint->created_at->diffForHumans(),
                        ];
                    });
                }
            ),
            
            // 통계 정보
            'statistics' => $this->when(
                $request->input('with_statistics'),
                function () {
                    return [
                        'total_complaints' => $this->complaints()->count(),
                        'pending_complaints' => $this->complaints()->where('status', 'pending')->count(),
                        'in_progress_complaints' => $this->complaints()->where('status', 'in_progress')->count(),
                        'resolved_complaints' => $this->complaints()->where('status', 'resolved')->count(),
                        'closed_complaints' => $this->complaints()->where('status', 'closed')->count(),
                        'avg_resolution_time' => $this->getAverageResolutionTime(),
                        'satisfaction_rating' => $this->getAverageSatisfactionRating(),
                    ];
                }
            ),
            
            // 메타데이터
            'is_active' => $this->status === 'active',
            'has_head' => !is_null($this->head_id),
            'has_members' => $this->members()->exists(),
            'has_complaints' => $this->complaints()->exists(),
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
        ];
    }
    
    /**
     * 평균 해결 시간 계산 (시간 단위)
     */
    private function getAverageResolutionTime(): ?float
    {
        $resolvedComplaints = $this->complaints()
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($resolvedComplaints->isEmpty()) {
            return null;
        }
        
        $totalHours = $resolvedComplaints->sum(function ($complaint) {
            return $complaint->created_at->diffInHours($complaint->resolved_at);
        });
        
        return round($totalHours / $resolvedComplaints->count(), 1);
    }
    
    /**
     * 평균 만족도 계산
     */
    private function getAverageSatisfactionRating(): ?float
    {
        $avg = $this->complaints()
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');
        
        return $avg ? round($avg, 1) : null;
    }
    
    /**
     * 수정 가능 여부 확인
     */
    private function canEdit($user): bool
    {
        if (!$user) return false;
        
        return $user->hasRole(['admin', 'super_admin']);
    }
    
    /**
     * 삭제 가능 여부 확인
     */
    private function canDelete($user): bool
    {
        if (!$user) return false;
        
        // 관리자만 삭제 가능
        if (!$user->hasRole(['admin', 'super_admin'])) {
            return false;
        }
        
        // 부서원이나 민원이 있으면 삭제 불가
        if ($this->members()->exists() || $this->complaints()->exists()) {
            return false;
        }
        
        return true;
    }
}
