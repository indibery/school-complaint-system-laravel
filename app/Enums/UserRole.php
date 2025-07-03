<?php

namespace App\Enums;

enum UserRole: string
{
    case PARENT = 'parent';
    case TEACHER = 'teacher';
    case SECURITY_STAFF = 'security_staff';
    case OPS_STAFF = 'ops_staff';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::PARENT => '학부모',
            self::TEACHER => '교사',
            self::SECURITY_STAFF => '학교지킴이',
            self::OPS_STAFF => '운영팀 사원',
            self::ADMIN => '총관리자',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PARENT => '학부모 계정 (자녀 대신 민원 제기)',
            self::TEACHER => '교사 계정 (담당 반/과목 관련)',
            self::SECURITY_STAFF => '학교지킴이 (시설 보안 관련)',
            self::OPS_STAFF => '운영팀 담당자',
            self::ADMIN => '시스템 총관리자',
        };
    }

    public function channel(): string
    {
        return match($this) {
            self::PARENT => 'parent_app',
            self::TEACHER => 'teacher_web',
            self::SECURITY_STAFF => 'security_app',
            self::OPS_STAFF => 'ops_web',
            self::ADMIN => 'admin_web',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::PARENT => [
                'complaint.create',
                'complaint.view_own',
                'complaint.update_own',
                'complaint.view_children', // 자녀 관련 민원 조회
                'comment.create',
                'student.view_own_children', // 자녀 정보 조회
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
                'student.view_class', // 담당 반 학생 정보 조회
            ],
            self::SECURITY_STAFF => [
                'complaint.create',
                'complaint.view_own',
                'complaint.view_security', // 보안/시설 관련 민원 조회
                'complaint.update_security',
                'comment.create',
                'comment.internal',
                'facility.manage', // 시설 관리
            ],
            self::OPS_STAFF => [
                'complaint.view_all',
                'complaint.update_all',
                'complaint.assign',
                'comment.create',
                'comment.internal',
                'report.view', // 운영 보고서 조회
                'category.manage',
                'student.view_all', // 모든 학생 정보 조회
            ],
            self::ADMIN => [
                'complaint.*',
                'comment.*',
                'user.*',
                'department.*',
                'category.*',
                'system.*',
                'report.*',
                'student.*',
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
     * 교직원 권한 여부 (교사, 보안요원, 운영팀, 관리자 포함)
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::TEACHER, self::SECURITY_STAFF, self::OPS_STAFF, self::ADMIN]);
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
     * 학교지킴이 권한 여부
     */
    public function isSecurityStaff(): bool
    {
        return $this === self::SECURITY_STAFF;
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
     * 외부 사용자 여부 (학부모)
     */
    public function isExternal(): bool
    {
        return $this === self::PARENT;
    }
}
