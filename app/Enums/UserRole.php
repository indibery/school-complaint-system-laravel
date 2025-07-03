<?php

namespace App\Enums;

enum UserRole: string
{
    case STUDENT = 'student';
    case STAFF = 'staff';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::STUDENT => '학생',
            self::STAFF => '교직원',
            self::ADMIN => '관리자',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::STUDENT => '학생 계정',
            self::STAFF => '교직원 계정',
            self::ADMIN => '시스템 관리자',
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
            self::STAFF => [
                'complaint.create',
                'complaint.view_own',
                'complaint.view_assigned',
                'complaint.update_own',
                'complaint.update_assigned',
                'comment.create',
                'comment.internal',
            ],
            self::ADMIN => [
                'complaint.*',
                'comment.*',
                'user.*',
                'department.*',
                'category.*',
                'system.*',
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
     * 교직원 권한 여부 (관리자 포함)
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::STAFF, self::ADMIN]);
    }

    /**
     * 학생 권한 여부
     */
    public function isStudent(): bool
    {
        return $this === self::STUDENT;
    }

    /**
     * 관리자 권한 여부
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
