<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends BaseApiController
{
    /**
     * Display a listing of attachments for a complaint.
     */
    public function index(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewAttachments($request->user(), $complaint)) {
                return $this->errorResponse(
                    '첨부파일을 조회할 권한이 없습니다.',
                    403
                );
            }

            $attachments = $complaint->attachments()
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                AttachmentResource::collection($attachments),
                '첨부파일 목록을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '첨부파일 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created attachment.
     */
    public function store(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array|max:10',
                'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
            ]);

            // 권한 체크
            if (!$this->canCreateAttachment($request->user(), $complaint)) {
                return $this->errorResponse(
                    '첨부파일을 업로드할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $attachments = [];
            $files = $request->file('files');

            foreach ($files as $file) {
                $attachment = $this->uploadFile($file, $complaint, $request->user());
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }

            DB::commit();

            return $this->createdResponse(
                AttachmentResource::collection($attachments),
                count($attachments) . '개의 파일이 성공적으로 업로드되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '파일 업로드 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified attachment.
     */
    public function show(Request $request, Attachment $attachment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewAttachment($request->user(), $attachment)) {
                return $this->errorResponse(
                    '첨부파일을 조회할 권한이 없습니다.',
                    403
                );
            }

            return $this->successResponse(
                new AttachmentResource($attachment),
                '첨부파일 정보를 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '첨부파일 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Download the specified attachment.
     */
    public function download(Request $request, Attachment $attachment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canDownloadAttachment($request->user(), $attachment)) {
                return $this->errorResponse(
                    '첨부파일을 다운로드할 권한이 없습니다.',
                    403
                );
            }

            // 파일 존재 확인
            if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
                return $this->errorResponse(
                    '파일을 찾을 수 없습니다.',
                    404
                );
            }

            // 다운로드 수 증가
            $attachment->increment('download_count');

            // 다운로드 로그 기록
            $this->logDownload($attachment, $request->user());

            // 파일 다운로드 URL 반환 (API에서는 직접 파일 반환보다는 다운로드 URL 제공)
            $downloadUrl = Storage::disk($attachment->disk)->temporaryUrl(
                $attachment->path,
                now()->addMinutes(10)
            );

            return $this->successResponse([
                'download_url' => $downloadUrl,
                'filename' => $attachment->original_name,
                'size' => $attachment->size,
                'mime_type' => $attachment->mime_type,
                'expires_at' => now()->addMinutes(10)->toISOString(),
            ], '다운로드 URL을 생성했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '파일 다운로드 준비 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canDeleteAttachment($request->user(), $attachment)) {
                return $this->errorResponse(
                    '첨부파일을 삭제할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            // 파일 삭제
            if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            // 썸네일 삭제 (이미지인 경우)
            if ($attachment->thumbnail_path && Storage::disk($attachment->disk)->exists($attachment->thumbnail_path)) {
                Storage::disk($attachment->disk)->delete($attachment->thumbnail_path);
            }

            // 데이터베이스에서 삭제
            $attachment->delete();

            DB::commit();

            return $this->deletedResponse('첨부파일이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '첨부파일 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Bulk delete attachments.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'attachment_ids' => 'required|array|min:1|max:50',
                'attachment_ids.*' => 'integer|exists:attachments,id',
            ]);

            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '첨부파일을 대량 삭제할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $attachmentIds = $request->input('attachment_ids');
            $deletedCount = 0;

            foreach ($attachmentIds as $attachmentId) {
                $attachment = Attachment::find($attachmentId);
                if ($attachment) {
                    // 파일 삭제
                    if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                        Storage::disk($attachment->disk)->delete($attachment->path);
                    }

                    // 썸네일 삭제
                    if ($attachment->thumbnail_path && Storage::disk($attachment->disk)->exists($attachment->thumbnail_path)) {
                        Storage::disk($attachment->disk)->delete($attachment->thumbnail_path);
                    }

                    $attachment->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            return $this->successResponse(
                ['deleted_count' => $deletedCount],
                "{$deletedCount}개의 첨부파일이 성공적으로 삭제되었습니다."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '첨부파일 대량 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get attachment statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '첨부파일 통계를 조회할 권한이 없습니다.',
                    403
                );
            }

            $stats = [
                'total_attachments' => Attachment::count(),
                'total_size' => Attachment::sum('size'),
                'total_size_formatted' => $this->formatBytes(Attachment::sum('size')),
                'attachments_today' => Attachment::whereDate('created_at', today())->count(),
                'attachments_this_week' => Attachment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'attachments_this_month' => Attachment::whereMonth('created_at', now()->month)->count(),
                'most_downloaded' => Attachment::orderByDesc('download_count')->take(10)->get(),
                'file_types' => Attachment::select('mime_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('mime_type')
                    ->orderByDesc('count')
                    ->get(),
                'avg_file_size' => Attachment::avg('size'),
                'avg_file_size_formatted' => $this->formatBytes(Attachment::avg('size')),
            ];

            return $this->successResponse($stats, '첨부파일 통계를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '첨부파일 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get upload configuration.
     */
    public function getUploadConfig(Request $request): JsonResponse
    {
        try {
            $config = [
                'max_file_size' => 10240, // 10MB in KB
                'max_files' => 10,
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'],
                'allowed_mime_types' => [
                    'image/jpeg', 'image/png', 'image/gif',
                    'application/pdf',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain',
                    'application/zip', 'application/x-rar-compressed'
                ],
                'storage_disk' => config('filesystems.default', 'local'),
                'chunk_size' => 1024 * 1024, // 1MB chunks for large files
            ];

            return $this->successResponse($config, '업로드 설정을 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '업로드 설정 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Upload a single file.
     */
    private function uploadFile($file, Complaint $complaint, $user): ?Attachment
    {
        try {
            // 파일 정보 수집
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // 파일명 생성
            $fileName = Str::uuid() . '.' . $extension;
            $path = "attachments/{$complaint->id}/{$fileName}";

            // 파일 저장
            $disk = config('filesystems.default', 'local');
            $storedPath = $file->storeAs("attachments/{$complaint->id}", $fileName, $disk);

            // 썸네일 생성 (이미지인 경우)
            $thumbnailPath = null;
            if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                $thumbnailPath = $this->createThumbnail($file, $complaint->id, $fileName, $disk);
            }

            // 데이터베이스에 저장
            $attachment = Attachment::create([
                'complaint_id' => $complaint->id,
                'original_name' => $originalName,
                'file_name' => $fileName,
                'path' => $storedPath,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'size' => $size,
                'thumbnail_path' => $thumbnailPath,
                'uploaded_by' => $user->id,
                'download_count' => 0,
            ]);

            return $attachment;

        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create thumbnail for image files.
     */
    private function createThumbnail($file, $complaintId, $fileName, $disk): ?string
    {
        try {
            // 썸네일 생성 로직 (예: Intervention Image 사용)
            // 실제 구현에서는 이미지 리사이징 라이브러리 사용
            $thumbnailName = 'thumb_' . $fileName;
            $thumbnailPath = "attachments/{$complaintId}/thumbnails/{$thumbnailName}";

            // 간단한 썸네일 생성 (실제로는 이미지 리사이징 라이브러리 사용)
            // Storage::disk($disk)->put($thumbnailPath, file_get_contents($file));

            return $thumbnailPath;

        } catch (\Exception $e) {
            \Log::error('Thumbnail creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log file download.
     */
    private function logDownload(Attachment $attachment, $user): void
    {
        // 다운로드 로그 기록
        \Log::info("File downloaded: {$attachment->original_name} by user {$user->id}");
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if user can view attachments.
     */
    private function canViewAttachments($user, Complaint $complaint): bool
    {
        return $this->canViewComplaint($user, $complaint);
    }

    /**
     * Check if user can view specific attachment.
     */
    private function canViewAttachment($user, Attachment $attachment): bool
    {
        return $this->canViewComplaint($user, $attachment->complaint);
    }

    /**
     * Check if user can create attachment.
     */
    private function canCreateAttachment($user, Complaint $complaint): bool
    {
        return $this->canViewComplaint($user, $complaint);
    }

    /**
     * Check if user can download attachment.
     */
    private function canDownloadAttachment($user, Attachment $attachment): bool
    {
        return $this->canViewComplaint($user, $attachment->complaint);
    }

    /**
     * Check if user can delete attachment.
     */
    private function canDeleteAttachment($user, Attachment $attachment): bool
    {
        // 관리자는 모든 첨부파일 삭제 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 업로드한 사용자는 자신의 첨부파일 삭제 가능
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        // 민원 제기자는 자신의 민원 첨부파일 삭제 가능
        if ($attachment->complaint->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view complaint.
     */
    private function canViewComplaint($user, Complaint $complaint): bool
    {
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 민원 제기자는 자신의 민원 조회 가능
        if ($complaint->created_by === $user->id) {
            return true;
        }

        // 담당자는 담당 민원 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 모든 민원 조회 가능
        if ($user->hasRole(['teacher', 'staff', 'department_head', 'vice_principal', 'principal'])) {
            return true;
        }

        return false;
    }
}
