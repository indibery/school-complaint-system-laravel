<?php

namespace App\Enums;

enum DepartmentType: string
{
    case ACADEMIC = 'academic';
    case ADMINISTRATIVE = 'administrative';
    case SUPPORT = 'support';

    public function label(): string
    {
        return match($this) {
            self::ACADEMIC => '학사부서',
            self::ADMINISTRATIVE => '행정부서',
            self::SUPPORT => '지원부서',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ACADEMIC => '학사 업무를 담당하는 부서',
            self::ADMINISTRATIVE => '행정 업무를 담당하는 부서',
            self::SUPPORT => '지원 업무를 담당하는 부서',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACADEMIC => '#3b82f6',    // blue-500
            self::ADMINISTRATIVE => '#10b981', // emerald-500
            self::SUPPORT => '#f59e0b',     // amber-500
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
}
