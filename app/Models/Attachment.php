<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'comment_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * 업로드한 사용자
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * 파일 URL 생성
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * 임시 다운로드 URL 생성
     */
    public function getTemporaryUrl(int $minutes = 10): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes($minutes));
    }

    /**
     * 썸네일 URL 생성
     */
    public function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    /**
     * 파일 존재 여부 확인
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * 파일 크기 포맷팅
     */
    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 파일 유형 확인
     */
    public function getFileType(): string
    {
        $mimeType = $this->mime_type;
        
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])) {
            return 'document';
        } elseif (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ])) {
            return 'archive';
        } elseif (str_starts_with($mimeType, 'text/')) {
            return 'text';
        } else {
            return 'other';
        }
    }

    /**
     * 이미지 파일 여부
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * 문서 파일 여부
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    /**
     * 압축 파일 여부
     */
    public function isArchive(): bool
    {
        return in_array($this->mime_type, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ]);
    }

    /**
     * 다운로드 수 증가
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * 스코프: 이미지 파일만
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * 스코프: 문서 파일만
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    /**
     * 스코프: 크기별 필터
     */
    public function scopeBySize($query, int $minSize = null, int $maxSize = null)
    {
        if ($minSize !== null) {
            $query->where('size', '>=', $minSize);
        }
        
        if ($maxSize !== null) {
            $query->where('size', '<=', $maxSize);
        }
        
        return $query;
    }

    /**
     * 파일 삭제 (스토리지에서도 삭제)
     */
    public function deleteFile(): bool
    {
        try {
            // 원본 파일 삭제
            if ($this->exists()) {
                Storage::disk($this->disk)->delete($this->path);
            }
            
            // 썸네일 삭제
            if ($this->thumbnail_path && Storage::disk($this->disk)->exists($this->thumbnail_path)) {
                Storage::disk($this->disk)->delete($this->thumbnail_path);
            }
            
            // 데이터베이스에서 삭제
            return $this->delete();
            
        } catch (\Exception $e) {
            \Log::error('File deletion failed: ' . $e->getMessage());
            return false;
        }
    }
}
