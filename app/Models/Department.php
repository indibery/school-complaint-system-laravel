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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
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

    /**
     * 부서 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'name' => 'required|string|max:100|regex:/^[가-힣a-zA-Z0-9\s\-()]+$/',
            'code' => 'required|string|max:10|unique:departments,code|regex:/^[A-Z0-9]+$/',
            'type' => 'required|in:' . implode(',', DepartmentType::getValues()),
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|regex:/^0[0-9]{1,2}-?[0-9]{3,4}-?[0-9]{4}$/',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * 부서 생성 시 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'name.required' => '부서명은 필수입니다.',
            'name.regex' => '부서명은 한글, 영문, 숫자, 공백, 하이픈, 괄호만 입력 가능합니다.',
            'code.required' => '부서 코드는 필수입니다.',
            'code.unique' => '이미 사용 중인 부서 코드입니다.',
            'code.regex' => '부서 코드는 영문 대문자와 숫자만 입력 가능합니다.',
            'type.required' => '부서 타입은 필수입니다.',
            'type.in' => '올바른 부서 타입을 선택해주세요.',
            'manager_id.exists' => '존재하지 않는 사용자입니다.',
            'parent_id.exists' => '존재하지 않는 상위 부서입니다.',
            'phone.regex' => '올바른 전화번호 형식을 입력해주세요. (예: 02-1234-5678)',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'description.max' => '설명은 최대 1000자까지 입력 가능합니다.',
        ];
    }
}
