<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
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
            'name' => $this->name,
            'email' => $this->when(
                $this->isOwner($this->id) || $user?->isAdmin(),
                $this->email,
                $this->maskEmail($this->email)
            ),
            'role' => $this->role,
            'role_display' => $this->formatRole($this->role),
            'grade' => $this->grade,
            'class_number' => $this->class_number,
            'homeroom_info' => $this->homeroom_info,
            'subject' => $this->subject,
            'department' => $this->department,
            'phone' => $this->when(
                $this->isOwner($this->id) || $user?->isAdmin(),
                $this->phone,
                $this->phone ? $this->maskPhone($this->phone) : null
            ),
            'is_active' => $this->is_active,
            'access_channel' => $this->access_channel,
            'display_name' => $this->display_name,
            
            // 관리자 또는 본인만 볼 수 있는 정보
            'email_verified_at' => $this->when(
                $this->isOwner($this->id) || $user?->isAdmin(),
                $this->formatDateTime($this->email_verified_at)
            ),
            
            // 통계 정보 (권한에 따라)
            'complaints_count' => $this->whenLoaded('complaints', function () {
                return $this->complaints->count();
            }),
            
            'assigned_complaints_count' => $this->whenLoaded('assignedComplaints', function () {
                return $this->assignedComplaints->count();
            }),
            
            // 학부모인 경우 자녀 정보
            'children' => $this->when(
                $this->role === 'parent' && ($this->isOwner($this->id) || $user?->isAdmin()),
                StudentResource::collection($this->whenLoaded('children'))
            ),
            
            // 교사인 경우 담당 학생 정보
            'homeroom_students_count' => $this->when(
                $this->role === 'teacher' && ($this->isOwner($this->id) || $user?->isAdmin()),
                $this->whenLoaded('homeroomStudents', function () {
                    return $this->homeroomStudents->count();
                })
            ),
            
            // 타임스탬프
            ...$this->getTimestamps(),
            
            // 소프트 삭제 정보
            ...$this->withSoftDeletes(),
        ];
    }

    /**
     * 권한 확인 메서드 오버라이드
     */
    protected function checkPermission($user, string $permission): bool
    {
        return match($permission) {
            'view_sensitive_data' => $user->isAdmin() || $this->isOwner($this->id),
            'view_statistics' => $user->isAdmin() || $user->canHandleComplaints(),
            'view_relationships' => $user->isAdmin() || $this->isOwner($this->id),
            default => false,
        };
    }

    /**
     * 사용자 요약 정보 반환 (목록용)
     */
    public static function summary($resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'role' => $resource->role,
            'role_display' => (new self($resource))->formatRole($resource->role),
            'display_name' => $resource->display_name,
            'is_active' => $resource->is_active,
            'department' => $resource->department,
            'homeroom_info' => $resource->homeroom_info,
        ];
    }

    /**
     * 담당자 정보 반환 (민원 등에서 사용)
     */
    public static function assignee($resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'role' => $resource->role,
            'role_display' => (new self($resource))->formatRole($resource->role),
            'department' => $resource->department,
            'homeroom_info' => $resource->homeroom_info,
            'display_name' => $resource->display_name,
        ];
    }

    /**
     * 민원 작성자 정보 반환
     */
    public static function complainant($resource): array
    {
        $user = request()->user();
        $instance = new self($resource);
        
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'role' => $resource->role,
            'role_display' => $instance->formatRole($resource->role),
            'display_name' => $resource->display_name,
            'email' => $user?->isAdmin() ? $resource->email : $instance->maskEmail($resource->email),
            'phone' => $user?->isAdmin() ? $resource->phone : ($resource->phone ? $instance->maskPhone($resource->phone) : null),
        ];
    }
}
