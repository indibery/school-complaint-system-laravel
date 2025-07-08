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

    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * 파일 확장자 가져오기
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }
    
    /**
     * 파일 타입별 아이콘 클래스 반환
     */
    public function getIconClassAttribute(): string
    {
        $extension = strtolower($this->file_extension);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            return 'image';
        } elseif (in_array($extension, ['pdf'])) {
            return 'pdf';
        } elseif (in_array($extension, ['doc', 'docx', 'hwp'])) {
            return 'doc';
        } elseif (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return 'excel';
        } elseif (in_array($extension, ['zip', 'rar', '7z'])) {
            return 'archive';
        } else {
            return 'default';
        }
    }
    
    /**
     * 파일 타입별 Bootstrap 아이콘 반환
     */
    public function getIconAttribute(): string
    {
        $iconClass = $this->icon_class;
        
        $icons = [
            'image' => 'image',
            'pdf' => 'file-pdf',
            'doc' => 'file-word',
            'excel' => 'file-excel',
            'archive' => 'file-zip',
            'default' => 'file-earmark',
        ];
        
        return $icons[$iconClass] ?? 'file-earmark';
    }
    
    /**
     * 이미지 파일 여부 확인
     */
    public function getIsImageAttribute(): bool
    {
        return $this->icon_class === 'image';
    }
    
    /**
     * 파일 다운로드 URL 생성
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('complaints.download-attachment', [
            'complaint' => $this->complaint_id,
            'attachment' => $this->id
        ]);
    }
    
    /**
     * 파일 미리보기 URL 생성 (이미지만)
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->is_image) {
            return null;
        }
        
        return Storage::disk('public')->url($this->file_path);
    }
    
    /**
     * 파일 존재 여부 확인
     */
    public function getFileExistsAttribute(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }
    
    /**
     * 파일 삭제 시 실제 파일도 삭제
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}
