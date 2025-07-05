<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StudentResource extends BaseResource
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
            'student_id' => $this->student_id,
            'grade' => $this->grade,
            'class_number' => $this->class_number,
            'class_display' => $this->grade . '학년 ' . $this->class_number . '반',
            'birth_date' => $this->when(
                $this->canViewSensitiveInfo($user),
                $this->formatDateOnly($this->birth_date)
            ),
            'gender' => $this->gender,
            'phone' => $this->when(
                $this->canViewSensitiveInfo($user),
                $this->phone
            ),
            'address' => $this->when(
                $this->canViewSensitiveInfo($user),
                $this->address
            ),
            'is_active' => $this->is_active,
            
            // 담임교사 정보
            'homeroom_teacher' => $this->whenLoaded('homeroomTeacher', function () {
                return UserResource::summary($this->homeroomTeacher);
            }),
            
            // 학부모 정보
            'parents' => $this->when(
                $this->canViewSensitiveInfo($user),
                $this->whenLoaded('parents', function () {
                    return UserResource::collection($this->parents);
                })
            ),
            
            'primary_parent' => $this->when(
                $this->canViewSensitiveInfo($user),
                $this->whenLoaded('parents', function () {
                    $primaryParent = $this->parents->where('pivot.is_primary', true)->first();
                    return $primaryParent ? UserResource::summary($primaryParent) : null;
                })
            ),
            
            // 민원 관련 정보
            'complaints_count' => $this->whenLoaded('complaints', function () {
                return $this->complaints->count();
            }),
            
            'recent_complaints' => $this->when(
                $this->canViewComplaints($user),
                $this->whenLoaded('complaints', function () {
                    return ComplaintResource::collection($this->complaints->take(5));
                })
            ),
            
            // 출석 정보 (필요시)
            'attendance_rate' => $this->attendance_rate,
            'last_attendance_date' => $this->formatDateOnly($this->last_attendance_date),
            
            // 권한 정보
            'can_view_details' => $this->canViewSensitiveInfo($user),
            'can_view_complaints' => $this->canViewComplaints($user),
            'can_edit' => $this->canEdit($user),
            
            // 타임스탬프
            ...$this->getTimestamps(),
            
            // 소프트 삭제 정보
            ...$this->withSoftDeletes(),
        ];
    }

    /**
     * 민감한 정보 조회 권한 확인
     */
    protected function canViewSensitiveInfo($user): bool
    {
        if (!$user) return false;

        // 관리자는 모든 정보 조회 가능
        if ($user->isAdmin()) return true;

        // 담임교사는 자신 반 학생 정보 조회 가능
        if ($user->isTeacher() && 
            $user->grade === $this->grade && 
            $user->class_number === $this->class_number) {
            return true;
        }

        // 학부모는 자신의 자녀 정보만 조회 가능
        if ($user->isParent()) {
            return $user->children->contains('id', $this->id);
        }

        return false;
    }

    /**
     * 민원 조회 권한 확인
     */
    protected function canViewComplaints($user): bool
    {
        if (!$user) return false;

        // 관리자는 모든 민원 조회 가능
        if ($user->isAdmin()) return true;

        // 담임교사는 자신 반 학생 민원 조회 가능
        if ($user->isTeacher() && 
            $user->grade === $this->grade && 
            $user->class_number === $this->class_number) {
            return true;
        }

        // 학부모는 자신의 자녀 민원만 조회 가능
        if ($user->isParent()) {
            return $user->children->contains('id', $this->id);
        }

        // 민원 처리 담당자는 조회 가능
        if ($user->canHandleComplaints()) {
            return true;
        }

        return false;
    }

    /**
     * 수정 권한 확인
     */
    protected function canEdit($user): bool
    {
        if (!$user) return false;

        return $user->isAdmin() || 
               ($user->isTeacher() && 
                $user->grade === $this->grade && 
                $user->class_number === $this->class_number);
    }

    /**
     * 학생 요약 정보 반환
     */
    public static function summary($resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'student_id' => $resource->student_id,
            'grade' => $resource->grade,
            'class_number' => $resource->class_number,
            'class_display' => $resource->grade . '학년 ' . $resource->class_number . '반',
            'is_active' => $resource->is_active,
        ];
    }

    /**
     * 출석부용 정보 반환
     */
    public static function attendance($resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'student_id' => $resource->student_id,
            'grade' => $resource->grade,
            'class_number' => $resource->class_number,
            'attendance_rate' => $resource->attendance_rate,
            'last_attendance_date' => (new self($resource))->formatDateOnly($resource->last_attendance_date),
            'is_active' => $resource->is_active,
        ];
    }

    /**
     * 학부모 앱용 자녀 정보 반환
     */
    public static function child($resource): array
    {
        $instance = new self($resource);
        
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'student_id' => $resource->student_id,
            'grade' => $resource->grade,
            'class_number' => $resource->class_number,
            'class_display' => $resource->grade . '학년 ' . $resource->class_number . '반',
            'birth_date' => $instance->formatDateOnly($resource->birth_date),
            'gender' => $resource->gender,
            'phone' => $resource->phone,
            'homeroom_teacher_name' => $resource->homeroomTeacher?->name ?? '미배정',
            'attendance_rate' => $resource->attendance_rate,
            'complaints_count' => $resource->complaints_count ?? 0,
            'is_active' => $resource->is_active,
        ];
    }
}
