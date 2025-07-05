<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_formatted' => $this->formatSize($this->size),
            'download_count' => $this->download_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // 파일 유형 정보
            'file_type' => $this->getFileType(),
            'is_image' => $this->isImage(),
            'is_document' => $this->isDocument(),
            'is_archive' => $this->isArchive(),
            
            // 썸네일 정보
            'has_thumbnail' => !is_null($this->thumbnail_path),
            'thumbnail_url' => $this->when($this->thumbnail_path, function () {
                return $this->getThumbnailUrl();
            }),
            
            // 업로드 정보
            'uploaded_by' => $this->whenLoaded('uploadedBy', function () {
                return [
                    'id' => $this->uploadedBy->id,
                    'name' => $this->uploadedBy->name,
                    'role' => $this->uploadedBy->role,
                ];
            }),
            
            // 민원 정보
            'complaint' => $this->whenLoaded('complaint', function () {
                return [
                    'id' => $this->complaint->id,
                    'title' => $this->complaint->title,
                    'complaint_number' => $this->complaint->complaint_number,
                ];
            }),
            
            // 액션 권한
            'can_download' => $this->canDownload($request->user()),
            'can_delete' => $this->canDelete($request->user()),
            
            // 다운로드 URL (임시)
            'download_url' => $this->when($this->canDownload($request->user()), function () {
                return route('api.attachments.download', $this->id);
            }),
        ];
    }
    
    /**
     * 파일 크기 포맷팅
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * 파일 유형 확인
     */
    private function getFileType(): string
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
    private function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    
    /**
     * 문서 파일 여부
     */
    private function isDocument(): bool
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
    private function isArchive(): bool
    {
        return in_array($this->mime_type, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ]);
    }
    
    /**
     * 썸네일 URL 생성
     */
    private function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        
        // 실제 구현에서는 스토리지 URL 생성
        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }
    
    /**
     * 다운로드 가능 여부 확인
     */
    private function canDownload($user): bool
    {
        if (!$user) return false;
        
        // 관리자는 모든 파일 다운로드 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // 민원 제기자는 자신의 민원 첨부파일 다운로드 가능
        if ($this->complaint->created_by === $user->id) {
            return true;
        }
        
        // 담당자는 담당 민원 첨부파일 다운로드 가능
        if ($this->complaint->assigned_to === $user->id) {
            return true;
        }
        
        // 교직원은 모든 첨부파일 다운로드 가능
        if ($user->hasRole(['teacher', 'staff', 'department_head', 'vice_principal', 'principal'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 삭제 가능 여부 확인
     */
    private function canDelete($user): bool
    {
        if (!$user) return false;
        
        // 관리자는 모든 첨부파일 삭제 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        
        // 업로드한 사용자는 자신의 첨부파일 삭제 가능
        if ($this->uploaded_by === $user->id) {
            return true;
        }
        
        // 민원 제기자는 자신의 민원 첨부파일 삭제 가능
        if ($this->complaint->created_by === $user->id) {
            return true;
        }
        
        return false;
    }
}
