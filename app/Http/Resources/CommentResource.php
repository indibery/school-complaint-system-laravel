<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_private' => $this->is_private,
            'is_edited' => $this->is_edited,
            'is_deleted' => $this->is_deleted,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // 작성자 정보
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                    'role' => $this->author->role,
                    'avatar' => $this->author->avatar,
                ];
            }),
            
            // 부모 댓글 정보
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CommentResource($this->parent);
            }),
            
            // 답글 정보
            'replies_count' => $this->whenCounted('replies'),
            'replies' => $this->whenLoaded('replies', function () {
                return CommentResource::collection($this->replies);
            }),
            
            // 민원 정보
            'complaint' => $this->whenLoaded('complaint', function () {
                return [
                    'id' => $this->complaint->id,
                    'title' => $this->complaint->title,
                    'complaint_number' => $this->complaint->complaint_number,
                ];
            }),
            
            // 메타데이터
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
        ];
    }
    
    /**
     * 댓글 수정 가능 여부 확인
     */
    private function canEdit($user): bool
    {
        if (!$user) return false;
        
        // 관리자는 모든 댓글 수정 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // 작성자는 자신의 댓글 수정 가능 (24시간 이내)
        if ($this->author_id === $user->id) {
            return $this->created_at->diffInHours(now()) <= 24;
        }
        
        return false;
    }
    
    /**
     * 댓글 삭제 가능 여부 확인
     */
    private function canDelete($user): bool
    {
        if (!$user) return false;
        
        // 관리자는 모든 댓글 삭제 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // 작성자는 자신의 댓글 삭제 가능
        if ($this->author_id === $user->id) {
            return true;
        }
        
        return false;
    }
}
