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

    protected $fillable = [
        'complaint_id',
        'author_id',
        'parent_id',
        'content',
        'is_private',
        'is_edited',
        'is_deleted',
        'edited_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 민원
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * 작성자
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * 부모 댓글
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 답글들
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * 첨부파일들
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * 스코프: 최상위 댓글 (답글이 아닌)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 스코프: 답글들
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * 스코프: 공개 댓글
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * 스코프: 비공개 댓글
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * 스코프: 삭제되지 않은 댓글
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * 댓글 수정 처리
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * 댓글 삭제 처리 (소프트 삭제)
     */
    public function markAsDeleted(): void
    {
        $this->update([
            'content' => '삭제된 댓글입니다.',
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    /**
     * 답글 여부 확인
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * 답글 존재 여부 확인
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * 수정 가능 여부 확인
     */
    public function canEdit(User $user): bool
    {
        // 관리자는 모든 댓글 수정 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 작성자는 자신의 댓글 수정 가능 (24시간 이내)
        if ($this->author_id === $user->id) {
            return $this->created_at->diffInHours(now()) <= 24;
        }

        return false;
    }

    /**
     * 삭제 가능 여부 확인
     */
    public function canDelete(User $user): bool
    {
        // 관리자는 모든 댓글 삭제 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 작성자는 자신의 댓글 삭제 가능
        if ($this->author_id === $user->id) {
            return true;
        }

        return false;
    }
}
