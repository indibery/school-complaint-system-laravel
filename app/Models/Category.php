<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
        'color',
        'icon',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 카테고리에 속한 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 부모 카테고리
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 자식 카테고리들
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 모든 하위 카테고리들 (재귀적)
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('descendants');
    }

    /**
     * 카테고리의 활성 민원들
     */
    public function activeComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class)->where('status', '!=', 'closed');
    }

    /**
     * 스코프: 활성 카테고리
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 스코프: 비활성 카테고리
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * 스코프: 최상위 카테고리
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 스코프: 하위 카테고리
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * 스코프: 정렬 순서대로
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * 스코프: 이름으로 검색
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * 카테고리 레벨 계산
     */
    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }
        
        return $level;
    }

    /**
     * 카테고리 경로 생성
     */
    public function getPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * 모든 조상 카테고리 ID 배열 반환
     */
    public function getAncestorIds(): array
    {
        $ancestors = [];
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors[] = $parent->id;
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    /**
     * 모든 하위 카테고리 ID 배열 반환 (재귀적)
     */
    public function getDescendantIds(): array
    {
        $descendants = [];
        
        foreach ($this->children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $child->getDescendantIds());
        }
        
        return $descendants;
    }

    /**
     * 하위 카테고리 존재 여부
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * 루트 카테고리 여부
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 최상위 카테고리 반환
     */
    public function getRoot(): Category
    {
        $category = $this;
        
        while ($category->parent) {
            $category = $category->parent;
        }
        
        return $category;
    }

    /**
     * 카테고리 트리 HTML 생성
     */
    public function toTreeHtml(int $level = 0): string
    {
        $indent = str_repeat('&nbsp;&nbsp;', $level);
        $html = $indent . $this->name;
        
        foreach ($this->children as $child) {
            $html .= '<br>' . $child->toTreeHtml($level + 1);
        }
        
        return $html;
    }

    /**
     * 순환 참조 확인
     */
    public function wouldCreateCircularReference(int $parentId): bool
    {
        if ($parentId === $this->id) {
            return true;
        }
        
        $parent = static::find($parentId);
        
        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }

    /**
     * 카테고리 사용 통계
     */
    public function getUsageStats(): array
    {
        return [
            'total_complaints' => $this->complaints()->count(),
            'active_complaints' => $this->activeComplaints()->count(),
            'completed_complaints' => $this->complaints()->where('status', 'closed')->count(),
            'pending_complaints' => $this->complaints()->where('status', 'pending')->count(),
            'in_progress_complaints' => $this->complaints()->where('status', 'in_progress')->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
        ];
    }

    /**
     * 평균 해결 시간 계산 (일 단위)
     */
    public function getAverageResolutionTime(): ?float
    {
        $resolvedComplaints = $this->complaints()
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($resolvedComplaints->isEmpty()) {
            return null;
        }
        
        $totalDays = $resolvedComplaints->sum(function ($complaint) {
            return $complaint->created_at->diffInDays($complaint->resolved_at);
        });
        
        return round($totalDays / $resolvedComplaints->count(), 1);
    }

    /**
     * 카테고리 활성화/비활성화
     */
    public function toggleStatus(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    /**
     * 정렬 순서 업데이트
     */
    public function updateSortOrder(int $sortOrder): void
    {
        $this->update(['sort_order' => $sortOrder]);
    }

    /**
     * 카테고리 삭제 가능 여부 확인
     */
    public function canDelete(): bool
    {
        return !$this->hasChildren() && !$this->complaints()->exists();
    }
}
