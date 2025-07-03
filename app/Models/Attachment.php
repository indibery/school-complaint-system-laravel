<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'comment_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'extension',
        'uploaded_by',
        'is_image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_image' => 'boolean',
    ];

    /**
     * 첨부파일이 속한 민원
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * 첨부파일이 속한 댓글 (선택사항)
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * 첨부파일 업로드자
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * 이미지 파일 여부
     */
    public function isImage(): bool
    {
        return $this->is_image;
    }

    /**
     * 파일 크기를 읽기 쉬운 형식으로 반환
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 파일 전체 경로 반환
     */
    public function getFullPathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    /**
     * 파일 다운로드 URL 반환
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    /**
     * 파일 미리보기 URL 반환 (이미지인 경우)
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->is_image) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    /**
     * 파일 삭제 가능 여부
     */
    public function canDelete(User $user): bool
    {
        // 업로드한 사용자 본인이거나 관리자인 경우
        return $this->uploaded_by === $user->id || $user->isAdmin();
    }

    /**
     * 파일 다운로드 가능 여부
     */
    public function canDownload(User $user): bool
    {
        // 민원 작성자, 담당자, 관리자인 경우
        $complaint = $this->complaint;
        
        return $user->isAdmin() || 
               $complaint->user_id === $user->id || 
               $complaint->assigned_to === $user->id;
    }

    /**
     * 파일 실제 삭제
     */
    public function deleteFile(): bool
    {
        try {
            if (Storage::exists($this->file_path)) {
                Storage::delete($this->file_path);
            }
            return $this->delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 이미지 파일 스코프
     */
    public function scopeImages($query)
    {
        return $query->where('is_image', true);
    }

    /**
     * 일반 파일 스코프
     */
    public function scopeFiles($query)
    {
        return $query->where('is_image', false);
    }

    /**
     * 특정 민원의 첨부파일 스코프
     */
    public function scopeForComplaint($query, $complaintId)
    {
        return $query->where('complaint_id', $complaintId);
    }

    /**
     * 특정 댓글의 첨부파일 스코프
     */
    public function scopeForComment($query, $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    /**
     * 업로드자별 스코프
     */
    public function scopeByUploader($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * 파일 타입별 스코프
     */
    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * 파일 확장자별 스코프
     */
    public function scopeByExtension($query, $extension)
    {
        return $query->where('extension', $extension);
    }

    /**
     * 파일 크기별 스코프
     */
    public function scopeBySizeRange($query, $minSize, $maxSize)
    {
        return $query->whereBetween('file_size', [$minSize, $maxSize]);
    }
}
