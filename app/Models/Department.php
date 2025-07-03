<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
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
        'type',
        'manager_id',
        'parent_id',
        'phone',
        'email',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => DepartmentType::class,
        'is_active' => 'boolean',
    ];

    /**
     * 부서장 (관리자)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * 상위 부서
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * 하위 부서들
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * 부서 소속 사용자들
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 부서로 배정된 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 부서의 모든 하위 부서들 (재귀적)
     */
    public function allChildren(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')->with('allChildren');
    }

    /**
     * 부서의 모든 상위 부서들 (재귀적)
     */
    public function allParents(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id')->with('allParents');
    }

    /**
     * 부서의 활성 사용자들
     */
    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    /**
     * 부서의 진행 중인 민원들
     */
    public function activeComplaints(): HasMany
    {
        return $this->complaints()->whereNotIn('status', [
            \App\Enums\ComplaintStatus::RESOLVED,
            \App\Enums\ComplaintStatus::CLOSED
        ]);
    }

    /**
     * 활성 부서 여부
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 최상위 부서 여부
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 전체 부서 경로 반환
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $path->join(' > ');
    }

    /**
     * 활성 부서 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 부서 타입별 스코프
     */
    public function scopeByType($query, DepartmentType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 최상위 부서 스코프
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 하위 부서 스코프
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
