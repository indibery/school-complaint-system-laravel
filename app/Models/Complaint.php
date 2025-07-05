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
        'complaint_number',
        'escalation_level',
        'escalation_target',
        'escalation_reason',
        'escalated_at',
        'escalated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'expected_completion_at' => 'datetime',
        'completed_at' => 'datetime',
        'satisfaction_rating' => 'integer',
        'escalated_at' => 'datetime',
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
    
    /**
     * 상태 텍스트 가져오기
     */
    public function getStatusTextAttribute(): string
    {
        $statusTexts = [
            'pending' => '대기',
            'in_progress' => '처리중',
            'resolved' => '해결됨',
            'closed' => '종료',
            'escalated' => '상급 이관',
        ];
        
        return $statusTexts[$this->status] ?? '알 수 없음';
    }
    
    /**
     * 우선순위 텍스트 가져오기
     */
    public function getPriorityTextAttribute(): string
    {
        $priorityTexts = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급',
        ];
        
        return $priorityTexts[$this->priority] ?? '보통';
    }
    
    /**
     * 상태 아이콘 가져오기
     */
    public function getStatusIconAttribute(): string
    {
        $statusIcons = [
            'pending' => 'clock',
            'in_progress' => 'play-circle',
            'resolved' => 'check-circle',
            'closed' => 'x-circle',
            'escalated' => 'arrow-up-circle',
        ];
        
        return $statusIcons[$this->status] ?? 'question-circle';
    }
    
    /**
     * 진행률 계산
     */
    public function getProgressPercentageAttribute(): int
    {
        $statusProgress = [
            'pending' => 10,
            'in_progress' => 50,
            'resolved' => 90,
            'closed' => 100,
            'escalated' => 25,
        ];
        
        $baseProgress = $statusProgress[$this->status] ?? 0;
        
        // 댓글이 있으면 +5%
        if ($this->comments_count > 0) {
            $baseProgress += 5;
        }
        
        // 담당자가 있으면 +10%
        if ($this->assigned_to) {
            $baseProgress += 10;
        }
        
        // 첨부파일이 있으면 +5%
        if ($this->attachments_count > 0) {
            $baseProgress += 5;
        }
        
        return min(100, $baseProgress);
    }
    
    /**
     * 민원 번호 자동 생성
     */
    public static function generateComplaintNumber(): string
    {
        $today = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        
        return $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 긴급 민원 여부 확인
     */
    public function getIsUrgentAttribute(): bool
    {
        return $this->priority === 'urgent';
    }
    
    /**
     * 지연 민원 여부 확인
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_completion_at) {
            return false;
        }
        
        return now()->gt($this->expected_completion_at) && 
               !in_array($this->status, ['resolved', 'closed']);
    }
    
    /**
     * 민원 처리 소요 시간 (시간 단위)
     */
    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return $this->created_at->diffInHours($this->completed_at);
    }
    
    /**
     * 민원 만족도 평가 여부
     */
    public function getHasSatisfactionRatingAttribute(): bool
    {
        return !is_null($this->satisfaction_rating);
    }
