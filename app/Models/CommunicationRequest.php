<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'teacher_id',
        'parent_id',
        'student_id',
        'ops_staff_id',
        'request_type',
        'teacher_message',
        'ops_message_to_parent',
        'parent_response',
        'ops_message_to_teacher',
        'status',
        'urgency_level',
        'requested_at',
        'processed_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 요청 타입 상수
     */
    const TYPE_CONTACT_REQUEST = 'contact_request';     // 연락 요청
    const TYPE_MEETING_REQUEST = 'meeting_request';     // 면담 요청
    const TYPE_INFO_SHARING = 'info_sharing';           // 정보 공유
    const TYPE_CONCERN_REPORT = 'concern_report';       // 우려사항 보고

    /**
     * 상태 상수
     */
    const STATUS_PENDING = 'pending';                   // 대기중
    const STATUS_REVIEWING = 'reviewing';               // 검토중
    const STATUS_SENT_TO_PARENT = 'sent_to_parent';     // 학부모에게 전달됨
    const STATUS_PARENT_RESPONDED = 'parent_responded'; // 학부모 응답
    const STATUS_COMPLETED = 'completed';               // 완료
    const STATUS_CANCELLED = 'cancelled';               // 취소

    /**
     * 긴급도 상수
     */
    const URGENCY_LOW = 'low';          // 낮음
    const URGENCY_MEDIUM = 'medium';    // 보통
    const URGENCY_HIGH = 'high';        // 높음
    const URGENCY_URGENT = 'urgent';    // 긴급

    /**
     * 관련 민원
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * 요청 교사
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * 대상 학부모
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * 관련 학생
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 처리 운영팀 직원
     */
    public function opsStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ops_staff_id');
    }

    /**
     * 요청 타입 한글 라벨
     */
    public function getRequestTypeLabelAttribute(): string
    {
        return match($this->request_type) {
            self::TYPE_CONTACT_REQUEST => '연락 요청',
            self::TYPE_MEETING_REQUEST => '면담 요청',
            self::TYPE_INFO_SHARING => '정보 공유',
            self::TYPE_CONCERN_REPORT => '우려사항 보고',
            default => '기타',
        };
    }

    /**
     * 상태 한글 라벨
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '대기중',
            self::STATUS_REVIEWING => '검토중',
            self::STATUS_SENT_TO_PARENT => '학부모에게 전달됨',
            self::STATUS_PARENT_RESPONDED => '학부모 응답함',
            self::STATUS_COMPLETED => '완료',
            self::STATUS_CANCELLED => '취소',
            default => '알 수 없음',
        };
    }

    /**
     * 긴급도 한글 라벨
     */
    public function getUrgencyLabelAttribute(): string
    {
        return match($this->urgency_level) {
            self::URGENCY_LOW => '낮음',
            self::URGENCY_MEDIUM => '보통',
            self::URGENCY_HIGH => '높음',
            self::URGENCY_URGENT => '긴급',
            default => '보통',
        };
    }

    /**
     * 긴급도별 색상
     */
    public function getUrgencyColorAttribute(): string
    {
        return match($this->urgency_level) {
            self::URGENCY_LOW => '#10b981',     // green
            self::URGENCY_MEDIUM => '#3b82f6',  // blue
            self::URGENCY_HIGH => '#f59e0b',    // amber
            self::URGENCY_URGENT => '#ef4444',  // red
            default => '#6b7280',               // gray
        };
    }

    /**
     * 처리 중 여부
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_REVIEWING,
            self::STATUS_SENT_TO_PARENT,
            self::STATUS_PARENT_RESPONDED
        ]);
    }

    /**
     * 완료 여부
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * 학부모 응답 대기 중 여부
     */
    public function isWaitingForParent(): bool
    {
        return $this->status === self::STATUS_SENT_TO_PARENT;
    }

    /**
     * 대기중 요청 스코프
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 진행중 요청 스코프
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_REVIEWING,
            self::STATUS_SENT_TO_PARENT,
            self::STATUS_PARENT_RESPONDED
        ]);
    }

    /**
     * 긴급 요청 스코프
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency_level', [self::URGENCY_HIGH, self::URGENCY_URGENT]);
    }

    /**
     * 교사별 요청 스코프
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * 학부모별 요청 스코프
     */
    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * 소통 요청 유효성 검증 규칙
     */
    public static function getValidationRules(): array
    {
        return [
            'complaint_id' => 'nullable|exists:complaints,id',
            'parent_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:students,id',
            'request_type' => 'required|in:contact_request,meeting_request,info_sharing,concern_report',
            'teacher_message' => 'required|string|max:1000',
            'urgency_level' => 'required|in:low,medium,high,urgent',
        ];
    }

    /**
     * 소통 요청 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'parent_id.required' => '학부모를 선택해주세요.',
            'parent_id.exists' => '존재하지 않는 학부모입니다.',
            'student_id.required' => '학생을 선택해주세요.',
            'student_id.exists' => '존재하지 않는 학생입니다.',
            'request_type.required' => '요청 타입을 선택해주세요.',
            'teacher_message.required' => '전달할 메시지를 입력해주세요.',
            'teacher_message.max' => '메시지는 1000자 이내로 입력해주세요.',
            'urgency_level.required' => '긴급도를 선택해주세요.',
        ];
    }
}
