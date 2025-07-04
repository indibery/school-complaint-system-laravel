<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComplaintPolicy
{
    /**
     * 민원 목록 조회 권한
     */
    public function viewAny(User $user): bool
    {
        // 모든 인증된 사용자는 민원 목록을 볼 수 있음 (권한에 따라 필터링됨)
        return true;
    }

    /**
     * 특정 민원 조회 권한
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 조회 가능
        if ($user->role === 'admin') {
            return true;
        }
        
        // 학부모는 자신이 등록한 민원만 조회 가능
        if ($user->role === 'parent') {
            return $complaint->user_id === $user->id;
        }
        
        // 교사는 자신에게 할당된 민원만 조회 가능
        if ($user->role === 'teacher') {
            return $complaint->assigned_to === $user->id;
        }
        
        // 직원은 해당 카테고리 관련 민원 조회 가능
        if (in_array($user->role, ['security_staff', 'ops_staff'])) {
            return $this->canHandleCategory($user, $complaint);
        }
        
        return false;
    }

    /**
     * 민원 등록 권한
     */
    public function create(User $user): bool
    {
        // 학부모만 민원 등록 가능
        return $user->role === 'parent';
    }

    /**
     * 민원 수정 권한
     */
    public function update(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 수정 가능
        if ($user->role === 'admin') {
            return true;
        }
        
        // 학부모는 자신이 등록한 민원만 수정 가능 (처리 중이 아닌 경우만)
        if ($user->role === 'parent') {
            return $complaint->user_id === $user->id && 
                   $complaint->status === 'submitted';
        }
        
        return false;
    }

    /**
     * 민원 삭제 권한
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        // 관리자만 민원 삭제 가능
        return $user->role === 'admin';
    }

    /**
     * 민원 상태 변경 권한
     */
    public function updateStatus(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 상태 변경 가능
        if ($user->role === 'admin') {
            return true;
        }
        
        // 교사는 자신에게 할당된 민원 상태 변경 가능
        if ($user->role === 'teacher') {
            return $complaint->assigned_to === $user->id;
        }
        
        // 직원은 해당 카테고리 관련 민원 상태 변경 가능
        if (in_array($user->role, ['security_staff', 'ops_staff'])) {
            return $this->canHandleCategory($user, $complaint);
        }
        
        return false;
    }

    /**
     * 민원 할당 권한
     */
    public function assign(User $user, Complaint $complaint): bool
    {
        // 관리자만 민원 할당 가능
        return $user->role === 'admin';
    }

    /**
     * 민원 관리 권한 (대량 작업 등)
     */
    public function manage(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 관리 가능
        if ($user->role === 'admin') {
            return true;
        }
        
        // 교사는 자신에게 할당된 민원 관리 가능
        if ($user->role === 'teacher') {
            return $complaint->assigned_to === $user->id;
        }
        
        // 직원은 해당 카테고리 관련 민원 관리 가능
        if (in_array($user->role, ['security_staff', 'ops_staff'])) {
            return $this->canHandleCategory($user, $complaint);
        }
        
        return false;
    }

    /**
     * 댓글 작성 권한
     */
    public function comment(User $user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원에 댓글 작성 가능
        if ($user->role === 'admin') {
            return true;
        }
        
        // 학부모는 자신이 등록한 민원에만 댓글 작성 가능
        if ($user->role === 'parent') {
            return $complaint->user_id === $user->id;
        }
        
        // 교사는 자신에게 할당된 민원에 댓글 작성 가능
        if ($user->role === 'teacher') {
            return $complaint->assigned_to === $user->id;
        }
        
        // 직원은 해당 카테고리 관련 민원에 댓글 작성 가능
        if (in_array($user->role, ['security_staff', 'ops_staff'])) {
            return $this->canHandleCategory($user, $complaint);
        }
        
        return false;
    }

    /**
     * 첨부파일 업로드 권한
     */
    public function uploadAttachment(User $user, Complaint $complaint): bool
    {
        // 댓글 권한과 동일
        return $this->comment($user, $complaint);
    }

    /**
     * 사용자가 특정 카테고리의 민원을 처리할 수 있는지 확인
     */
    private function canHandleCategory(User $user, Complaint $complaint): bool
    {
        // 카테고리별 담당자 매핑
        $categoryHandlers = [
            'security_staff' => ['시설/환경', '교통/안전'],
            'ops_staff' => ['급식', '기타']
        ];
        
        $allowedCategories = $categoryHandlers[$user->role] ?? [];
        
        return in_array($complaint->category->name, $allowedCategories);
    }
}
