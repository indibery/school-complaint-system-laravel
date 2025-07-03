<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => '접수됨',
            self::IN_PROGRESS => '처리중',
            self::RESOLVED => '해결됨',
            self::REJECTED => '반려됨',
            self::CLOSED => '종료됨',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PENDING => '민원이 접수되어 처리 대기 중입니다',
            self::IN_PROGRESS => '민원이 처리 중입니다',
            self::RESOLVED => '민원이 해결되었습니다',
            self::REJECTED => '민원이 반려되었습니다',
            self::CLOSED => '민원이 종료되었습니다',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => '#fbbf24',      // yellow-400
            self::IN_PROGRESS => '#3b82f6',  // blue-500
            self::RESOLVED => '#10b981',     // emerald-500
            self::REJECTED => '#ef4444',     // red-500
            self::CLOSED => '#6b7280',       // gray-500
        };
    }

    public function canTransitionTo(ComplaintStatus $status): bool
    {
        return match($this) {
            self::PENDING => in_array($status, [self::IN_PROGRESS, self::REJECTED]),
            self::IN_PROGRESS => in_array($status, [self::RESOLVED, self::REJECTED, self::PENDING]),
            self::RESOLVED => in_array($status, [self::CLOSED, self::IN_PROGRESS]),
            self::REJECTED => in_array($status, [self::PENDING, self::IN_PROGRESS]),
            self::CLOSED => false, // 종료된 민원은 상태 변경 불가
        };
    }

    public function isActive(): bool
    {
        return $this !== self::CLOSED;
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED]);
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

    public static function getActiveStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isActive());
    }
}
