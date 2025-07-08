<?php

namespace App\Repositories\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasAccessControl
{
    /**
     * 사용자 권한 기반 접근 제어 적용
     */
    public function applyAccessControl(Builder $query, User $user): Builder
    {
        // 관리자는 모든 데이터 조회 가능
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        // 교사/직원은 할당받은 데이터와 본인이 작성한 데이터, 동일 부서 데이터 조회 가능
        if ($user->hasRole(['teacher', 'staff'])) {
            return $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('user_id', $user->id);
                
                // 부서 ID가 있는 경우 부서 조건 추가
                if ($user->department_id) {
                    $q->orWhere('department_id', $user->department_id);
                }
            });
        }
        
        // 학부모는 본인과 자녀 관련 데이터만 조회 가능
        if ($user->hasRole('parent')) {
            $studentIds = $user->children()->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $studentIds) {
                $q->where('user_id', $user->id);
                if (!empty($studentIds)) {
                    $q->orWhereIn('student_id', $studentIds);
                }
            });
        }
        
        // 학생은 본인 관련 데이터만 조회 가능
        if ($user->hasRole('student')) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('student_id', $user->id);
            });
        }
        
        // 기본적으로 본인이 작성한 데이터만 조회 가능
        return $query->where('user_id', $user->id);
    }
}
