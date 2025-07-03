<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintStatusLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'complaint_status_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'user_id',
        'from_status',
        'to_status',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'from_status' => ComplaintStatus::class,
        'to_status' => ComplaintStatus::class,
    ];

    /**
     * Indicates if the model should be timestamped.
     * 이 모델은 created_at만 사용하므로 updated_at은 사용하지 않음
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 상태 로그가 속한 민원
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * 상태를 변경한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 상태 변경 여부 (새로운 민원인 경우는 false)
     */
    public function isStatusChange(): bool
    {
        return !is_null($this->from_status);
    }

    /**
     * 초기 상태 로그 여부 (민원 생성 시)
     */
    public function isInitialLog(): bool
    {
        return is_null($this->from_status);
    }

    /**
     * 상태 변경 설명 반환
     */
    public function getStatusChangeDescriptionAttribute(): string
    {
        if ($this->isInitialLog()) {
            return '민원이 ' . $this->to_status->label() . ' 상태로 생성되었습니다.';
        }
        
        return $this->from_status->label() . '에서 ' . $this->to_status->label() . '로 변경되었습니다.';
    }

    /**
     * 변경 사유 반환
     */
    public function getReasonAttribute(): string
    {
        return $this->comment ?? '사유 없음';
    }

    /**
     * 특정 민원의 상태 로그 스코프
     */
    public function scopeForComplaint($query, $complaintId)
    {
        return $query->where('complaint_id', $complaintId);
    }

    /**
     * 특정 사용자의 상태 로그 스코프
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 특정 상태로 변경된 로그 스코프
     */
    public function scopeToStatus($query, ComplaintStatus $status)
    {
        return $query->where('to_status', $status);
    }

    /**
     * 특정 상태에서 변경된 로그 스코프
     */
    public function scopeFromStatus($query, ComplaintStatus $status)
    {
        return $query->where('from_status', $status);
    }

    /**
     * 상태 변경 로그만 (초기 로그 제외)
     */
    public function scopeStatusChanges($query)
    {
        return $query->whereNotNull('from_status');
    }

    /**
     * 초기 로그만 (민원 생성 시)
     */
    public function scopeInitialLogs($query)
    {
        return $query->whereNull('from_status');
    }

    /**
     * 최근 로그 순 정렬
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 오래된 로그 순 정렬
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * 댓글이 있는 로그 스코프
     */
    public function scopeWithComment($query)
    {
        return $query->whereNotNull('comment');
    }

    /**
     * 댓글이 없는 로그 스코프
     */
    public function scopeWithoutComment($query)
    {
        return $query->whereNull('comment');
    }
}
