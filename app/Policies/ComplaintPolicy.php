<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // 모든 인증된 사용자는 민원 목록을 볼 수 있음
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 자신의 민원 조회 가능
        if ($complaint->user_id === $user->id) {
            return true;
        }

        // 담당자는 할당된 민원 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 공개 민원은 모두 조회 가능
        if ($complaint->is_public) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // 모든 인증된 사용자는 민원을 생성할 수 있음
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 수정 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 pending 상태일 때만 수정 가능
        if ($complaint->user_id === $user->id && $complaint->status === 'pending') {
            return true;
        }

        // 담당자는 할당된 민원 수정 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        // 관리자만 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 pending 상태일 때만 삭제 가능
        if ($complaint->user_id === $user->id && $complaint->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can comment on the complaint.
     */
    public function comment(User $user, Complaint $complaint): bool
    {
        // 관리자, 담당자, 작성자는 댓글 가능
        return $user->hasRole('admin') || 
               $complaint->assigned_to === $user->id || 
               $complaint->user_id === $user->id;
    }

    /**
     * Determine whether the user can assign the complaint.
     */
    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can export complaints.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['admin', 'staff']);
    }
}
