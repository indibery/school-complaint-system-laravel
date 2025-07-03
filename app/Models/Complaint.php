<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

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
}
