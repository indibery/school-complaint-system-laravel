<?php

namespace App\Enums;

enum UserRole: string
{
    case STUDENT = 'student';
    case PARENT = 'parent';
    case TEACHER = 'teacher';
    case VISITOR_STAFF = 'visitor_staff';
    case OPS_STAFF = 'ops_staff';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::STUDENT => '학생',
            self::PARENT => '학부모',
            self::TEACHER => '교사',
            self::VISITOR_STAFF => '예약방문 관리인',
            self::OPS_STAFF => '운영팀 사원',
            self::ADMIN => '총관리자',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::STUDENT => '재학생 계정',
            self::PARENT => '학부모 계정',
            self::TEACHER => '교사 계정 (내부 전용)',
            self::VISITOR_STAFF => '예약방문 관리 담당자',
            self::OPS_STAFF => '운영팀 담당자',
            self::ADMIN => '시스템 총관리자',
        };
    }

    public function channel(): string
    {
        return match($this) {
            self::STUDENT => 'student_app',
            self::PARENT => 'parent_app',
            self::TEACHER => 'internal_web',
            self::VISITOR_STAFF => 'visitor_staff_app',
            self::OPS_STAFF => 'ops_web',
            self::ADMIN => 'admin_web',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::STUDENT => [
                'complaint.create',
                'complaint.view_own',
                'complaint.update_own',
                'comment.create',
            ],
            self::PARENT => [
                'complaint.create',
                'complaint.view_own',
                'complaint.view_children', // 자녀 관련 민원 조회
                'complaint.update_own',
                'comment.create',
            ],
            self::TEACHER => [
                'complaint.create',
                'complaint.view_own',
                'complaint.view_assigned',
                'complaint.view_class', // 담당 반 관련 민원 조회
                'complaint.update_own',
                'complaint.update_assigned',
                'comment.create',
                'comment.internal',
            ],
            self::VISITOR_STAFF => [
                'complaint.view_visitor', // 방문 관련 민원만 조회
                'complaint.update_visitor',
                'visitor.manage', // 방문자 관리
                'comment.create',
                'comment.internal',
            ],
            self::OPS_STAFF => [
                'complaint.view_all',
                'complaint.update_all',
                'complaint.assign',
                'comment.create',
                'comment.internal',
                'report.view', // 운영 보고서 조회
                'category.manage',
            ],
            self::ADMIN => [
                'complaint.*',
                'comment.*',
                'user.*',
                'department.*',
                'category.*',
                'system.*',
                'report.*',
                'visitor.*',
            ],
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * 교직원 권한 여부 (교사, 운영팀, 관리자 포함)
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::TEACHER, self::VISITOR_STAFF, self::OPS_STAFF, self::ADMIN]);
    }

    /**
     * 학생 권한 여부
     */
    public function isStudent(): bool
    {
        return $this === self::STUDENT;
    }

    /**
     * 학부모 권한 여부
     */
    public function isParent(): bool
    {
        return $this === self::PARENT;
    }

    /**
     * 교사 권한 여부
     */
    public function isTeacher(): bool
    {
        return $this === self::TEACHER;
    }

    /**
     * 방문 관리자 권한 여부
     */
    public function isVisitorStaff(): bool
    {
        return $this === self::VISITOR_STAFF;
    }

    /**
     * 운영팀 권한 여부
     */
    public function isOpsStaff(): bool
    {
        return $this === self::OPS_STAFF;
    }

    /**
     * 관리자 권한 여부
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * 내부 사용자 여부 (교직원 그룹)
     */
    public function isInternal(): bool
    {
        return $this->isStaff();
    }

    /**
     * 외부 사용자 여부 (학생, 학부모)
     */
    public function isExternal(): bool
    {
        return in_array($this, [self::STUDENT, self::PARENT]);
    }
}
