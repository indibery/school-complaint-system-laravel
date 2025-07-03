<?php

namespace App\Enums;

enum Priority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW => '낮음',
            self::MEDIUM => '보통',
            self::HIGH => '높음',
            self::URGENT => '긴급',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::LOW => '일반적인 민원',
            self::MEDIUM => '보통 수준의 민원',
            self::HIGH => '높은 우선순위 민원',
            self::URGENT => '긴급 처리 필요',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW => '#10b981',     // emerald-500
            self::MEDIUM => '#3b82f6',  // blue-500
            self::HIGH => '#f59e0b',    // amber-500
            self::URGENT => '#ef4444',  // red-500
        };
    }

    public function level(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    public function dueInDays(): int
    {
        return match($this) {
            self::LOW => 14,      // 2주
            self::MEDIUM => 7,    // 1주
            self::HIGH => 3,      // 3일
            self::URGENT => 1,    // 1일
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

    public static function getOrderedByLevel(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $a->level() <=> $b->level());
        return $cases;
    }
}
