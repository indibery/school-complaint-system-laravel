<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface ComplaintFileServiceInterface
{
    /**
     * 파일 업로드
     */
    public function uploadFiles(Complaint $complaint, array $files, User $user): array;

    /**
     * 단일 파일 업로드
     */
    public function uploadFile(Complaint $complaint, UploadedFile $file, User $user): array;

    /**
     * 파일 삭제
     */
    public function deleteFiles(Complaint $complaint, array $fileIds, User $user): bool;

    /**
     * 파일 검증
     */
    public function validateFile(UploadedFile $file): bool;

    /**
     * 파일 다운로드 URL 생성
     */
    public function getDownloadUrl(int $fileId, User $user): ?string;

    /**
     * 파일 정보 조회
     */
    public function getFileInfo(int $fileId): ?array;

    /**
     * 허용된 파일 타입 확인
     */
    public function isAllowedFileType(string $mimeType): bool;

    /**
     * 파일 크기 검증
     */
    public function isValidFileSize(int $size): bool;

    /**
     * 파일 저장 경로 생성
     */
    public function generateFilePath(Complaint $complaint, string $originalName): string;

    /**
     * 파일 메타데이터 저장
     */
    public function saveFileMetadata(
        Complaint $complaint,
        string $originalName,
        string $filePath,
        int $fileSize,
        string $mimeType,
        User $user
    ): array;

    /**
     * 최대 파일 개수 확인
     */
    public function hasReachedMaxFiles(Complaint $complaint): bool;

    /**
     * 파일 액세스 권한 확인
     */
    public function canAccessFile(int $fileId, User $user): bool;
}
