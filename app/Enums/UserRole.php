<?php

namespace App\Enums;

enum UserRole: string
{
    case PARENT = 'parent';
    case TEACHER = 'teacher';
    case VISITOR_GUARD = 'visitor_guard';
    case OPS_STAFF = 'ops_staff';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::PARENT => '학부모',
            self::TEACHER => '교사',
            self::VISITOR_GUARD => '학교지킴이',
            self::OPS_STAFF => '운영팀 사원',
            self::ADMIN => '총관리자',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PARENT => '학부모 계정 (자녀 대신 민원 제기)',
            self::TEACHER => '교사 계정 (내부 전용, 운영팀 경유 소통)',
            self::VISITOR_GUARD => '학교지킴이 (방문자 예약 확인 및 관리)',
            self::OPS_STAFF => '운영팀 담당자 (교사-학부모 소통 중계)',
            self::ADMIN => '시스템 총관리자',
        ];
    }

    public function channel(): string
    {
        return match($this) {
            self::PARENT => 'parent_app',
            self::TEACHER => 'teacher_internal_web',
            self::VISITOR_GUARD => 'visitor_guard_app',
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
                'comment.create_to_ops', // 운영팀에게만 댓글 작성
                'student.view_own_children', // 자녀 정보 조회
            ],
            self::TEACHER => [
                'complaint.create_internal', // 내부 민원만 생성 (학부모 비공개)
                'complaint.view_own',
                'complaint.view_class_related', // 담당 반 관련 민원 조회 (운영팀 필터링)
                'complaint.update_own',
                'comment.create_internal', // 내부 댓글만 작성
                'student.view_class', // 담당 반 학생 정보 조회
                'communication.request_contact', // 학부모 연락 요청 (운영팀 경유)
            ],
            self::VISITOR_GUARD => [
                'visitor.check_reservation', // 방문 예약 확인
                'visitor.register_walk_in', // 당일 방문자 등록
                'visitor.view_today_list', // 당일 방문자 목록 조회
                'visitor.update_status', // 방문 상태 업데이트
                'complaint.create_visitor_related', // 방문 관련 민원 생성
                'comment.create_internal', // 내부 댓글 작성
            ],
            self::OPS_STAFF => [
                'complaint.view_all',
                'complaint.update_all',
                'complaint.assign',
                'complaint.bridge_communication', // 교사-학부모 소통 중계
                'comment.create',
                'comment.internal',
                'comment.relay_to_parent', // 교사 댓글을 학부모에게 전달
                'report.view', // 운영 보고서 조회
                'category.manage',
                'student.view_all', // 모든 학생 정보 조회
                'communication.manage_requests', // 연락 요청 관리
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
                'visitor.*',
                'communication.*',
            ],
        ];
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
     * 교직원 권한 여부 (교사, 방문관리자, 운영팀, 관리자 포함)
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::TEACHER, self::VISITOR_GUARD, self::OPS_STAFF, self::ADMIN]);
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
     * 학교지킴이(방문관리자) 권한 여부
     */
    public function isVisitorGuard(): bool
    {
        return $this === self::VISITOR_GUARD;
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

    /**
     * 직접 소통 가능 여부 (운영팀, 관리자만)
     */
    public function canDirectCommunicate(): bool
    {
        return in_array($this, [self::OPS_STAFF, self::ADMIN]);
    }

    /**
     * 학부모와 격리된 내부 사용자 여부
     */
    public function isIsolatedInternal(): bool
    {
        return in_array($this, [self::TEACHER, self::VISITOR_GUARD]);
    }
}
