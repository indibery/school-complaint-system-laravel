<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'user_id',
        'content',
        'is_internal',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_internal' => 'boolean',
    ];

    /**
     * 댓글이 속한 민원
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * 댓글 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 상위 댓글 (답글인 경우)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 하위 댓글들 (답글들)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * 댓글 첨부파일들
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * 최상위 댓글 여부
     */
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 답글 여부
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * 내부 댓글 여부 (교직원만 볼 수 있는 댓글)
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * 공개 댓글 여부
     */
    public function isPublic(): bool
    {
        return !$this->is_internal;
    }

    /**
     * 댓글 수정 가능 여부
     */
    public function canEdit(User $user): bool
    {
        // 작성자 본인이거나 관리자인 경우
        return $this->user_id === $user->id || $user->isAdmin();
    }

    /**
     * 댓글 삭제 가능 여부
     */
    public function canDelete(User $user): bool
    {
        // 작성자 본인이거나 관리자인 경우
        return $this->user_id === $user->id || $user->isAdmin();
    }

    /**
     * 공개 댓글 스코프
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * 내부 댓글 스코프
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * 최상위 댓글 스코프
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 답글 스코프
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * 특정 민원의 댓글 스코프
     */
    public function scopeForComplaint($query, $complaintId)
    {
        return $query->where('complaint_id', $complaintId);
    }

    /**
     * 작성자별 댓글 스코프
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 최근 댓글 순 정렬
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 오래된 댓글 순 정렬
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}
