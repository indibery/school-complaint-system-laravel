<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComplaintFileService implements ComplaintFileServiceInterface
{
    private const MAX_FILES = 10;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * 파일 업로드
     */
    public function uploadFiles(Complaint $complaint, array $files, User $user): array
    {
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                // 파일 개수 체크
                if ($this->hasReachedMaxFiles($complaint)) {
                    $errors[] = "최대 " . self::MAX_FILES . "개까지만 업로드할 수 있습니다.";
                    break;
                }

                $result = $this->uploadFile($complaint, $file, $user);
                $uploadedFiles[] = $result;

            } catch (\Exception $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                Log::error('파일 업로드 실패', [
                    'complaint_id' => $complaint->id,
                    'file_name' => $file->getClientOriginalName(),
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($errors)) {
            Log::warning('파일 업로드 중 일부 오류 발생', [
                'complaint_id' => $complaint->id,
                'errors' => $errors
            ]);
        }

        return [
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * 단일 파일 업로드
     */
    public function uploadFile(Complaint $complaint, UploadedFile $file, User $user): array
    {
        DB::beginTransaction();

        try {
            // 파일 검증
            if (!$this->validateFile($file)) {
                throw new \Exception('유효하지 않은 파일입니다.');
            }

            // 파일 저장 경로 생성
            $filePath = $this->generateFilePath($complaint, $file->getClientOriginalName());
            
            // 파일 저장
            $storedPath = $file->storeAs(
                dirname($filePath),
                basename($filePath),
                'public'
            );

            if (!$storedPath) {
                throw new \Exception('파일 저장에 실패했습니다.');
            }

            // 메타데이터 저장
            $fileInfo = $this->saveFileMetadata(
                $complaint,
                $file->getClientOriginalName(),
                $storedPath,
                $file->getSize(),
                $file->getMimeType(),
                $user
            );

            DB::commit();

            Log::info('파일 업로드 완료', [
                'complaint_id' => $complaint->id,
                'file_id' => $fileInfo['id'],
                'file_name' => $file->getClientOriginalName(),
                'user_id' => $user->id
            ]);

            return $fileInfo;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // 저장된 파일이 있다면 삭제
            if (isset($storedPath) && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $e;
        }
    }

    /**
     * 파일 삭제
     */
    public function deleteFiles(Complaint $complaint, array $fileIds, User $user): bool
    {
        DB::beginTransaction();

        try {
            $deletedCount = 0;
            $errors = [];

            foreach ($fileIds as $fileId) {
                try {
                    // 파일 정보 조회
                    $fileInfo = $this->getFileInfo($fileId);
                    
                    if (!$fileInfo) {
                        $errors[] = "파일 ID {$fileId}를 찾을 수 없습니다.";
                        continue;
                    }

                    // 파일 접근 권한 확인
                    if (!$this->canAccessFile($fileId, $user)) {
                        $errors[] = "파일 ID {$fileId}에 대한 권한이 없습니다.";
                        continue;
                    }

                    // 물리적 파일 삭제
                    if (Storage::disk('public')->exists($fileInfo['file_path'])) {
                        Storage::disk('public')->delete($fileInfo['file_path']);
                    }

                    // 데이터베이스 레코드 삭제
                    DB::table('complaint_attachments')
                        ->where('id', $fileId)
                        ->delete();

                    $deletedCount++;

                } catch (\Exception $e) {
                    $errors[] = "파일 ID {$fileId} 삭제 실패: " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('파일 삭제 완료', [
                'complaint_id' => $complaint->id,
                'deleted_count' => $deletedCount,
                'errors' => $errors,
                'user_id' => $user->id
            ]);

            return $deletedCount > 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('파일 삭제 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'file_ids' => $fileIds,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 파일 검증
     */
    public function validateFile(UploadedFile $file): bool
    {
        // 파일 크기 검증
        if (!$this->isValidFileSize($file->getSize())) {
            throw new \Exception('파일 크기가 ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB를 초과합니다.');
        }

        // 파일 타입 검증
        if (!$this->isAllowedFileType($file->getMimeType())) {
            throw new \Exception('허용되지 않는 파일 형식입니다.');
        }

        // 파일 확장자 검증
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'xls', 'xlsx'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('허용되지 않는 파일 확장자입니다.');
        }

        return true;
    }

    /**
     * 파일 다운로드 URL 생성
     */
    public function getDownloadUrl(int $fileId, User $user): ?string
    {
        try {
            // 파일 접근 권한 확인
            if (!$this->canAccessFile($fileId, $user)) {
                return null;
            }

            $fileInfo = $this->getFileInfo($fileId);
            
            if (!$fileInfo || !Storage::disk('public')->exists($fileInfo['file_path'])) {
                return null;
            }

            return Storage::disk('public')->url($fileInfo['file_path']);

        } catch (\Exception $e) {
            Log::error('파일 다운로드 URL 생성 실패', [
                'file_id' => $fileId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 파일 정보 조회
     */
    public function getFileInfo(int $fileId): ?array
    {
        $fileInfo = DB::table('complaint_attachments')
            ->where('id', $fileId)
            ->first();

        if (!$fileInfo) {
            return null;
        }

        return [
            'id' => $fileInfo->id,
            'complaint_id' => $fileInfo->complaint_id,
            'original_name' => $fileInfo->original_name,
            'file_path' => $fileInfo->file_path,
            'file_size' => $fileInfo->file_size,
            'file_type' => $fileInfo->file_type,
            'uploaded_by' => $fileInfo->uploaded_by,
            'uploaded_at' => $fileInfo->created_at,
        ];
    }

    /**
     * 허용된 파일 타입 확인
     */
    public function isAllowedFileType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_TYPES);
    }

    /**
     * 파일 크기 검증
     */
    public function isValidFileSize(int $size): bool
    {
        return $size <= self::MAX_FILE_SIZE;
    }

    /**
     * 파일 저장 경로 생성
     */
    public function generateFilePath(Complaint $complaint, string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = Str::uuid() . '.' . $extension;
        
        return 'complaints/' . $complaint->id . '/' . date('Y/m') . '/' . $filename;
    }

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
    ): array {
        $attachmentData = [
            'complaint_id' => $complaint->id,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $mimeType,
            'uploaded_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $fileId = DB::table('complaint_attachments')->insertGetId($attachmentData);

        return array_merge($attachmentData, ['id' => $fileId]);
    }

    /**
     * 최대 파일 개수 확인
     */
    public function hasReachedMaxFiles(Complaint $complaint): bool
    {
        $currentCount = DB::table('complaint_attachments')
            ->where('complaint_id', $complaint->id)
            ->count();

        return $currentCount >= self::MAX_FILES;
    }

    /**
     * 파일 액세스 권한 확인
     */
    public function canAccessFile(int $fileId, User $user): bool
    {
        $fileInfo = $this->getFileInfo($fileId);
        
        if (!$fileInfo) {
            return false;
        }

        // 파일이 속한 민원 조회
        $complaint = DB::table('complaints')
            ->where('id', $fileInfo['complaint_id'])
            ->first();

        if (!$complaint) {
            return false;
        }

        // 관리자는 모든 파일 접근 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원의 파일 접근 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 작성자는 본인 민원의 파일 접근 가능
        if ($complaint->created_by === $user->id) {
            return true;
        }

        // 같은 부서 직원은 부서 민원의 파일 접근 가능
        if ($user->department_id === $complaint->department_id) {
            return true;
        }

        // 학부모는 자녀 관련 민원의 파일 접근 가능
        if ($user->hasRole('parent') && $complaint->student_id) {
            $childrenIds = $user->children()->pluck('id');
            return $childrenIds->contains($complaint->student_id);
        }

        return false;
    }
}
