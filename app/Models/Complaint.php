<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id', 
        'category_id',
        'title',
        'content',
        'status',
        'priority',
        'assigned_to',
        'expected_completion_at',
        'completed_at',
        'is_public',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'expected_completion_at' => 'datetime',
        'completed_at' => 'datetime',
        'satisfaction_rating' => 'integer',
    ];

    /**
     * 민원 제기자 (학부모)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 민원인 (별칭)
     */
    public function complainant(): BelongsTo
    {
        return $this->user();
    }

    /**
     * 관련 학생
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 민원 카테고리
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 담당자
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 첨부파일들
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * 상태 로그들
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class);
    }

    /**
     * 상태 라벨 가져오기
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'submitted' => '접수 완료',
            'in_review' => '검토 중',
            'in_progress' => '처리 중',
            'resolved' => '해결 완료',
            'closed' => '종료',
            'rejected' => '반려'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * 우선순위 라벨 가져오기
     */
    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급'
        ];

        return $labels[$this->priority] ?? $this->priority;
    }

    /**
     * 상태 변경
     */
    public function changeStatus(string $status, string $reason = null, int $changedBy = null): bool
    {
        $oldStatus = $this->status;
        $this->status = $status;
        
        if ($status === 'resolved' || $status === 'closed') {
            $this->completed_at = now();
        }

        $result = $this->save();

        if ($result) {
            // 상태 로그 기록
            ComplaintStatusLog::create([
                'complaint_id' => $this->id,
                'from_status' => $oldStatus,
                'to_status' => $status,
                'changed_by' => $changedBy ?? auth()->id(),
                'notes' => $reason,
            ]);
        }

        return $result;
    }

    /**
     * 담당자 할당
     */
    public function assignTo(int $userId, string $reason = null): bool
    {
        $this->assigned_to = $userId;
        $this->status = 'in_progress';
        
        $result = $this->save();

        if ($result) {
            // 상태 로그 기록
            ComplaintStatusLog::create([
                'complaint_id' => $this->id,
                'from_status' => 'submitted',
                'to_status' => 'in_progress',
                'changed_by' => auth()->id(),
                'notes' => $reason ?? '담당자 할당',
            ]);
        }

        return $result;
    }
}
