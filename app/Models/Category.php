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
}
