<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'color' => $this->color,
            'icon' => $this->icon,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // 계층 구조 정보
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CategoryResource($this->parent);
            }),
            
            // 하위 카테고리 정보
            'children_count' => $this->whenCounted('children'),
            'children' => $this->whenLoaded('children', function () {
                return CategoryResource::collection($this->children);
            }),
            
            // 민원 관련 정보
            'complaints_count' => $this->whenCounted('complaints'),
            'active_complaints_count' => $this->when(
                $this->relationLoaded('complaints'),
                function () {
                    return $this->complaints->where('status', '!=', 'closed')->count();
                }
            ),
            
            // 통계 정보
            'usage_stats' => $this->when(
                $request->input('with_stats'),
                function () {
                    return [
                        'total_complaints' => $this->complaints()->count(),
                        'completed_complaints' => $this->complaints()->where('status', 'closed')->count(),
                        'pending_complaints' => $this->complaints()->where('status', 'pending')->count(),
                        'in_progress_complaints' => $this->complaints()->where('status', 'in_progress')->count(),
                        'avg_resolution_time' => $this->getAverageResolutionTime(),
                    ];
                }
            ),
            
            // 메타데이터
            'level' => $this->getLevel(),
            'path' => $this->getPath(),
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
            'has_complaints' => $this->complaints()->exists(),
        ];
    }
    
    /**
     * 카테고리 레벨 계산
     */
    private function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }
        
        return $level;
    }
    
    /**
     * 카테고리 경로 생성
     */
    private function getPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
    
    /**
     * 평균 해결 시간 계산 (일 단위)
     */
    private function getAverageResolutionTime(): ?float
    {
        $resolvedComplaints = $this->complaints()
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($resolvedComplaints->isEmpty()) {
            return null;
        }
        
        $totalDays = $resolvedComplaints->sum(function ($complaint) {
            return $complaint->created_at->diffInDays($complaint->resolved_at);
        });
        
        return round($totalDays / $resolvedComplaints->count(), 1);
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
        
        // 하위 카테고리나 민원이 있으면 삭제 불가
        if ($this->children()->exists() || $this->complaints()->exists()) {
            return false;
        }
        
        return true;
    }
}
