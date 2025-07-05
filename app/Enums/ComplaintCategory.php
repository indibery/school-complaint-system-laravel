<?php

namespace App\Enums;

enum ComplaintCategory: string
{
    case FACILITY = 'facility';
    case ACADEMIC = 'academic';
    case LIVING = 'living';
    case ADMINISTRATIVE = 'administrative';
    case IT = 'it';
    case SAFETY = 'safety';
    case OTHER = 'other';

    /**
     * 카테고리명을 한국어로 반환
     */
    public function label(): string
    {
        return match ($this) {
            self::FACILITY => '시설',
            self::ACADEMIC => '학사',
            self::LIVING => '생활',
            self::ADMINISTRATIVE => '행정',
            self::IT => 'IT/시스템',
            self::SAFETY => '안전',
            self::OTHER => '기타',
        };
    }

    /**
     * 카테고리 설명을 반환
     */
    public function description(): string
    {
        return match ($this) {
            self::FACILITY => '시설물 관련 문의 및 불편사항',
            self::ACADEMIC => '수강신청, 성적, 졸업 등 학사 관련',
            self::LIVING => '기숙사, 식당, 도서관 등 생활 관련',
            self::ADMINISTRATIVE => '등록, 장학금, 증명서 발급 등',
            self::IT => 'IT 시스템, 네트워크 관련 문의',
            self::SAFETY => '안전, 보안 관련 신고 및 문의',
            self::OTHER => '기타 분류되지 않은 민원',
        };
    }

    /**
     * 카테고리에 따른 색상 클래스 반환
     */
    public function color(): string
    {
        return match ($this) {
            self::FACILITY => 'warning',
            self::ACADEMIC => 'primary',
            self::LIVING => 'success',
            self::ADMINISTRATIVE => 'info',
            self::IT => 'secondary',
            self::SAFETY => 'danger',
            self::OTHER => 'dark',
        };
    }

    /**
     * 카테고리에 따른 아이콘 반환
     */
    public function icon(): string
    {
        return match ($this) {
            self::FACILITY => 'building',
            self::ACADEMIC => 'book',
            self::LIVING => 'house',
            self::ADMINISTRATIVE => 'clipboard',
            self::IT => 'laptop',
            self::SAFETY => 'shield',
            self::OTHER => 'folder',
        };
    }

    /**
     * 기본 처리 우선순위 반환
     */
    public function defaultPriority(): Priority
    {
        return match ($this) {
            self::SAFETY => Priority::HIGH,
            self::IT => Priority::MEDIUM,
            self::ACADEMIC => Priority::MEDIUM,
            self::FACILITY => Priority::MEDIUM,
            self::ADMINISTRATIVE => Priority::MEDIUM,
            self::LIVING => Priority::LOW,
            self::OTHER => Priority::LOW,
        };
    }

    /**
     * 모든 카테고리 목록을 배열로 반환
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * 선택 옵션 형태로 반환 (value => label)
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn($carry, $case) => $carry + [$case->value => $case->label()],
            []
        );
    }

    /**
     * 우선순위별로 정렬된 카테고리들 반환
     */
    public static function byPriority(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $b->defaultPriority()->value() <=> $a->defaultPriority()->value());
        return $cases;
    }
}
