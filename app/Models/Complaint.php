<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 기본적으로 로드할 관계들
     *
     * @var array<string>
     */
    protected $with = ['user', 'category', 'department'];

    /**
     * 관계 로딩 시 카운트할 관계들
     *
     * @var array<string>
     */
    protected $withCount = ['comments', 'attachments'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'status',
        'priority',
        'complaint_number',
        'user_id',
        'category_id',
        'department_id',
        'assigned_to',
        'due_date',
        'resolved_at',
        'satisfaction_score',
        'is_anonymous',
        'is_public',
        'view_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ComplaintStatus::class,
        'priority' => Priority::class,
        'due_date' => 'date',
        'resolved_at' => 'datetime',
        'satisfaction_score' => 'integer',
        'is_anonymous' => 'boolean',
        'is_public' => 'boolean',
        'view_count' => 'integer',
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
        'due_date',
        'resolved_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 부트 메서드 - 모델 생성 시 자동으로 실행
     */
    protected static function boot()
    {
        parent::boot();

        // 민원 번호 자동 생성
        static::creating(function ($complaint) {
            if (empty($complaint->complaint_number)) {
                $complaint->complaint_number = static::generateComplaintNumber();
            }
            
            // 우선순위별 기본 처리 예정일 설정
            if (empty($complaint->due_date) && $complaint->priority) {
                $complaint->due_date = Carbon::now()->addDays($complaint->priority->dueInDays());
            }
        });
        
        // 상태 변경 시 해결 시간 자동 설정
        static::updating(function ($complaint) {
            if ($complaint->isDirty('status')) {
                if ($complaint->status === ComplaintStatus::RESOLVED && !$complaint->resolved_at) {
                    $complaint->resolved_at = Carbon::now();
                }
            }
        });
    }

    /**
     * 민원 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 민원 담당자
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 민원 카테고리
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 처리 부서
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 민원 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 민원 첨부파일들
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * 민원 상태 로그들
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class);
    }

    /**
     * 민원의 공개 댓글들
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('is_internal', false);
    }

    /**
     * 민원의 내부 댓글들 (교직원 전용)
     */
    public function internalComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('is_internal', true);
    }

    /**
     * 민원의 최상위 댓글들 (답글 제외)
     */
    public function topLevelComments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * 민원의 이미지 첨부파일들
     */
    public function imageAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->where('is_image', true);
    }

    /**
     * 민원의 일반 첨부파일들
     */
    public function fileAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->where('is_image', false);
    }

    /**
     * 민원의 최신 상태 로그
     */
    public function latestStatusLog(): HasOne
    {
        return $this->hasOne(ComplaintStatusLog::class)->latestOfMany();
    }

    /**
     * 민원에 관련된 모든 사용자들 (작성자, 담당자, 댓글 작성자들)
     */
    public function relatedUsers(): Collection
    {
        $users = collect();
        
        // 민원 작성자
        if ($this->user) {
            $users->push($this->user);
        }
        
        // 담당자
        if ($this->assignedUser) {
            $users->push($this->assignedUser);
        }
        
        // 댓글 작성자들
        $commentUsers = $this->comments()->with('user')->get()->pluck('user');
        $users = $users->merge($commentUsers);
        
        return $users->unique('id');
    }

    /**
     * 민원 상태 라벨 Accessor
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * 우선순위 라벨 Accessor
     */
    public function getPriorityLabelAttribute(): string
    {
        return $this->priority->label();
    }

    /**
     * 처리 기간 Accessor (일 단위)
     */
    public function getProcessingDaysAttribute(): ?int
    {
        if ($this->resolved_at) {
            return $this->created_at->diffInDays($this->resolved_at);
        }
        return null;
    }

    /**
     * 남은 기간 Accessor (일 단위)
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if ($this->due_date && !$this->isResolved()) {
            return Carbon::now()->diffInDays($this->due_date, false);
        }
        return null;
    }

    /**
     * 민원 요약 Accessor
     */
    public function getSummaryAttribute(): string
    {
        return mb_substr($this->content, 0, 100) . (mb_strlen($this->content) > 100 ? '...' : '');
    }

    /**
     * 민원 번호 생성
     */
    public static function generateComplaintNumber(): string
    {
        $prefix = 'C' . date('Y');
        $lastNumber = static::whereYear('created_at', date('Y'))
            ->where('complaint_number', 'LIKE', $prefix . '%')
            ->count();
        
        return $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * 민원 상태 변경
     */
    public function changeStatus(ComplaintStatus $newStatus, User $user, string $comment = null): bool
    {
        $oldStatus = $this->status;
        
        // 상태 변경 가능 여부 확인
        if (!$oldStatus->canTransitionTo($newStatus)) {
            return false;
        }
        
        $this->status = $newStatus;
        $this->save();
        
        // 상태 로그 생성
        $this->statusLogs()->create([
            'user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'comment' => $comment,
        ]);
        
        return true;
    }

    /**
     * 조회수 증가
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * 처리 기한 초과 여부
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date < Carbon::now() && !$this->status->isResolved();
    }

    /**
     * 해결 완료 여부
     */
    public function isResolved(): bool
    {
        return $this->status->isResolved();
    }

    /**
     * 활성 상태 여부
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * 처리 기간 반환 (해결된 경우)
     */
    public function getProcessingTime(): ?int
    {
        if ($this->resolved_at) {
            return $this->created_at->diffInDays($this->resolved_at);
        }
        return null;
    }

    /**
     * 상태별 스코프
     */
    public function scopeByStatus($query, ComplaintStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 우선순위별 스코프
     */
    public function scopeByPriority($query, Priority $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * 공개 민원 스코프
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 기한 초과 스코프
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now())
            ->whereNotIn('status', [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED]);
    }

    /**
     * 활성 민원 스코프
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [ComplaintStatus::CLOSED]);
    }

    /**
     * 카테고리별 스코프
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * 부서별 스코프
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * 작성자별 스코프
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 담당자별 스코프
     */
    public function scopeByAssignee($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * 민원 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'title' => 'required|string|max:255|min:10',
            'content' => 'required|string|min:20|max:10000',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:' . implode(',', Priority::getValues()),
            'due_date' => 'nullable|date|after:today',
            'satisfaction_score' => 'nullable|integer|min:1|max:5',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * 민원 생성 시 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'title.required' => '민원 제목은 필수입니다.',
            'title.min' => '민원 제목은 최소 10자 이상 입력해주세요.',
            'title.max' => '민원 제목은 최대 255자까지 입력 가능합니다.',
            'content.required' => '민원 내용은 필수입니다.',
            'content.min' => '민원 내용은 최소 20자 이상 입력해주세요.',
            'content.max' => '민원 내용은 최대 10,000자까지 입력 가능합니다.',
            'category_id.required' => '카테고리는 필수입니다.',
            'category_id.exists' => '존재하지 않는 카테고리입니다.',
            'department_id.required' => '처리 부서는 필수입니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'priority.required' => '우선순위는 필수입니다.',
            'priority.in' => '올바른 우선순위를 선택해주세요.',
            'due_date.date' => '올바른 날짜 형식을 입력해주세요.',
            'due_date.after' => '처리 예정일은 오늘 이후로 설정해주세요.',
            'satisfaction_score.min' => '만족도는 1점 이상이어야 합니다.',
            'satisfaction_score.max' => '만족도는 5점 이하여야 합니다.',
        ];
    }
}
