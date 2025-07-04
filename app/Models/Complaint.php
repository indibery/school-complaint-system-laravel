<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'complaint_number',
        'title',
        'description',
        'category_id',
        'department_id',
        'priority',
        'status',
        'created_by',
        'assigned_to',
        'student_id',
        'resolved_at',
        'escalated_at',
        'escalated_by',
        'transferred_at',
        'transferred_by',
        'transfer_reason',
        'auto_transferred',
        'due_date',
        'follow_up_date',
        'satisfaction_rating',
        'satisfaction_comment',
        'resolution_comment',
        'private_notes',
        'tags',
        'metadata',
        'views_count',
        'source',
        'contact_method',
        'contact_info',
        'is_anonymous',
        'is_urgent',
        'is_confidential',
        'external_reference',
        'location',
        'incident_date',
        'witness_info',
        'related_student_id',
        'visitor_reservation_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'transferred_at' => 'datetime',
        'due_date' => 'datetime',
        'follow_up_date' => 'datetime',
        'incident_date' => 'datetime',
        'auto_transferred' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_urgent' => 'boolean',
        'is_confidential' => 'boolean',
        'satisfaction_rating' => 'integer',
        'views_count' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    protected $dates = [
        'resolved_at',
        'escalated_at',
        'transferred_at',
        'due_date',
        'follow_up_date',
        'incident_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 민원 상태 목록
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 우선순위 목록
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * 카테고리
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
     * 민원 제기자
     */
    public function complainant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 담당자
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 이관자
     */
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    /**
     * 상급 이관자
     */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    /**
     * 관련 학생 (학부모가 자녀 관련 민원 제기시)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * 관련 학생 (다른 관계)
     */
    public function relatedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'related_student_id');
    }

    /**
     * 관련 방문자 예약 (방문 관련 민원인 경우)
     */
    public function visitorReservation(): BelongsTo
    {
        return $this->belongsTo(VisitorReservation::class, 'visitor_reservation_id');
    }

    /**
     * 댓글
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 첨부파일
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * 상태 변경 이력
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class);
    }

    /**
     * 조회수 증가
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * 태그 동기화
     */
    public function syncTags(array $tags): void
    {
        $this->tags = $tags;
        $this->save();
    }

    /**
     * 상태 변경 가능 여부 확인
     */
    public function canChangeStatus(string $newStatus): bool
    {
        $allowedTransitions = [
            self::STATUS_PENDING => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
            self::STATUS_ASSIGNED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_RESOLVED, self::STATUS_CANCELLED],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS],
            self::STATUS_CLOSED => [],
            self::STATUS_CANCELLED => [self::STATUS_PENDING],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    /**
     * 민원 완료 처리
     */
    public function markAsResolved(string $comment = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_comment' => $comment,
        ]);
    }

    /**
     * 민원 마감 처리
     */
    public function markAsClosed(string $comment = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'resolution_comment' => $comment,
        ]);
    }

    /**
     * 우선순위 업데이트
     */
    public function updatePriority(string $priority): void
    {
        $this->update(['priority' => $priority]);
    }

    /**
     * 이관 처리
     */
    public function transferTo(User $user, string $reason = null, User $transferredBy = null): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'transferred_at' => now(),
            'transferred_by' => $transferredBy?->id,
            'transfer_reason' => $reason,
        ]);
    }

    /**
     * 스코프: 활성 민원
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    /**
     * 스코프: 미할당 민원
     */
    public function scopeUnassigned($query)
    {
        return $query->where('status', self::STATUS_PENDING)->whereNull('assigned_to');
    }

    /**
     * 스코프: 긴급 민원
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * 스코프: 기한 초과 민원
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->active();
    }

    /**
     * 스코프: 사용자별 민원
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhere('assigned_to', $user->id);
        });
    }

    /**
     * 스코프: 부서별 민원
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * 접근자: 상태 라벨
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => '접수',
            self::STATUS_ASSIGNED => '배정',
            self::STATUS_IN_PROGRESS => '처리중',
            self::STATUS_RESOLVED => '해결',
            self::STATUS_CLOSED => '완료',
            self::STATUS_CANCELLED => '취소',
        ];

        return $labels[$this->status] ?? '알 수 없음';
    }

    /**
     * 접근자: 우선순위 라벨
     */
    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            self::PRIORITY_LOW => '낮음',
            self::PRIORITY_NORMAL => '보통',
            self::PRIORITY_HIGH => '높음',
            self::PRIORITY_URGENT => '긴급',
        ];

        return $labels[$this->priority] ?? '보통';
    }

    /**
     * 접근자: 이관 여부
     */
    public function getIsTransferredAttribute(): bool
    {
        return !is_null($this->transferred_at);
    }

    /**
     * 접근자: 상급 이관 여부
     */
    public function getIsEscalatedAttribute(): bool
    {
        return !is_null($this->escalated_at);
    }

    /**
     * 접근자: 기한 초과 여부
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== self::STATUS_CLOSED;
    }

    /**
     * 접근자: 처리 기간 (일)
     */
    public function getProcessingDaysAttribute(): ?int
    {
        if (!$this->resolved_at) return null;

        return $this->created_at->diffInDays($this->resolved_at);
    }
}
