<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 카테고리에 속한 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 카테고리의 활성 민원들
     */
    public function activeComplaints(): HasMany
    {
        return $this->complaints()->whereNotIn('status', [
            \App\Enums\ComplaintStatus::RESOLVED,
            \App\Enums\ComplaintStatus::CLOSED
        ]);
    }

    /**
     * 카테고리의 해결된 민원들
     */
    public function resolvedComplaints(): HasMany
    {
        return $this->complaints()->whereIn('status', [
            \App\Enums\ComplaintStatus::RESOLVED,
            \App\Enums\ComplaintStatus::CLOSED
        ]);
    }

    /**
     * 카테고리의 긴급 민원들
     */
    public function urgentComplaints(): HasMany
    {
        return $this->complaints()->where('priority', \App\Enums\Priority::URGENT);
    }

    /**
     * 활성 카테고리 여부
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 카테고리 색상 반환 (기본값 포함)
     */
    public function getColorAttribute($value): string
    {
        return $value ?? '#6b7280'; // 기본 회색
    }

    /**
     * 민원 수 반환
     */
    public function getComplaintsCountAttribute(): int
    {
        return $this->complaints()->count();
    }

    /**
     * 활성 카테고리 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 정렬 순서 스코프
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * 카테고리 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'name' => 'required|string|max:100|regex:/^[가-힣a-zA-Z0-9\s\-()]+$/',
            'code' => 'required|string|max:20|unique:categories,code|regex:/^[A-Z0-9_]+$/',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * 카테고리 생성 시 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'name.required' => '카테고리명은 필수입니다.',
            'name.regex' => '카테고리명은 한글, 영문, 숫자, 공백, 하이픈, 괄호만 입력 가능합니다.',
            'code.required' => '카테고리 코드는 필수입니다.',
            'code.unique' => '이미 사용 중인 카테고리 코드입니다.',
            'code.regex' => '카테고리 코드는 영문 대문자, 숫자, 언더스코어만 입력 가능합니다.',
            'description.max' => '설명은 최대 500자까지 입력 가능합니다.',
            'color.regex' => '올바른 색상 코드를 입력해주세요. (예: #FF0000)',
            'sort_order.min' => '정렬 순서는 0 이상이어야 합니다.',
            'sort_order.max' => '정렬 순서는 9999 이하여야 합니다.',
        ];
    }
}
