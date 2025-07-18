<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    /**
     * 민원 목록 조회 권한
     */
    public function viewAny(User $user): bool
    {
        return true; // 모든 인증된 사용자가 민원 목록을 볼 수 있음
    }

    /**
     * 민원 상세 조회 권한
     */
    public function view(User $user, Complaint $complaint): bool
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
        if ($complaint->user_id === $user->id) {
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
     * 민원 생성 권한
     */
    public function create(User $user): bool
    {
        // 모든 인증된 사용자가 민원을 생성할 수 있음
        return true;
    }

    /**
     * 민원 수정 권한
     */
    public function update(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 수정 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원 수정 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 작성자는 본인 민원 수정 가능 (단, 접수 또는 진행 중 상태일 때만)
        if ($complaint->user_id === $user->id && 
            in_array($complaint->status, ['submitted', 'in_progress'])) {
            return true;
        }

        return false;
    }

    /**
     * 민원 삭제 권한
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 본인 민원 삭제 가능 (접수 상태일 때만)
        if ($complaint->user_id === $user->id && $complaint->status === 'submitted') {
            return true;
        }

        return false;
    }

    /**
     * 민원 할당 권한
     */
    public function assign(User $user, Complaint $complaint): bool
    {
        // 관리자만 민원을 할당할 수 있음
        return $user->hasRole('admin');
    }

    /**
     * 민원 상태 변경 권한
     */
    public function updateStatus(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 상태 변경 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원 상태 변경 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 민원 내보내기 권한
     */
    public function export(User $user): bool
    {
        // 관리자만 민원을 내보낼 수 있음
        return $user->hasRole('admin');
    }
}
