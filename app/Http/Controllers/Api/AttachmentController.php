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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttachmentController extends BaseApiController
{
    /**
     * Display a listing of attachments for a specific complaint.
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
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                AttachmentResource::collection($attachments),
                '첨부파일 목록을 조회했습니다.'
            );

        } catch (\Exception $e) {
            Log::error('첨부파일 목록 조회 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 목록 조회에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Store newly created attachments.
     */
    public function store(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canUploadAttachment($request->user(), $complaint)) {
                return $this->errorResponse(
                    '첨부파일을 업로드할 권한이 없습니다.',
                    403
                );
            }

            $validated = $request->validate([
                'attachments' => 'required|array|max:10',
                'attachments.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,hwp,txt,xlsx,xls',
            ]);

            DB::beginTransaction();

            $uploadedFiles = [];
            $errors = [];

            foreach ($validated['attachments'] as $file) {
                try {
                    // 파일 저장
                    $filePath = $file->store('complaints/' . $complaint->id, 'public');
                    
                    // 첨부파일 레코드 생성
                    $attachment = $complaint->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => Auth::id(),
                    ]);

                    $uploadedFiles[] = $attachment;

                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => '파일 업로드 실패: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $message = count($uploadedFiles) . '개의 파일이 업로드되었습니다.';
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . '개의 파일 업로드에 실패했습니다.';
            }

            return $this->successResponse([
                'uploaded_files' => AttachmentResource::collection($uploadedFiles),
                'errors' => $errors,
                'total_uploaded' => count($uploadedFiles),
                'total_errors' => count($errors)
            ], $message, 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('첨부파일 업로드 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 업로드에 실패했습니다.',
                500
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

            $attachment->load(['user', 'complaint']);

            return $this->successResponse(
                new AttachmentResource($attachment),
                '첨부파일 정보를 조회했습니다.'
            );

        } catch (\Exception $e) {
            Log::error('첨부파일 조회 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 조회에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Download the specified attachment.
     */
    public function download(Request $request, Attachment $attachment)
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
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return $this->errorResponse(
                    '파일을 찾을 수 없습니다.',
                    404
                );
            }

            // 다운로드 카운트 증가
            $attachment->increment('download_count');
            
            // 다운로드 로그 (향후 구현)
            // $this->logDownload($attachment, $request->user());

            return Storage::disk('public')->download(
                $attachment->file_path,
                $attachment->original_name
            );

        } catch (\Exception $e) {
            Log::error('첨부파일 다운로드 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 다운로드에 실패했습니다.',
                500
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

            // 실제 파일 삭제
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // 데이터베이스에서 삭제
            $attachment->delete();

            DB::commit();

            return $this->successResponse(
                null,
                '첨부파일이 성공적으로 삭제되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('첨부파일 삭제 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 삭제에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Get attachment preview/thumbnail
     */
    public function preview(Request $request, Attachment $attachment)
    {
        try {
            // 권한 체크
            if (!$this->canViewAttachment($request->user(), $attachment)) {
                return $this->errorResponse(
                    '첨부파일을 조회할 권한이 없습니다.',
                    403
                );
            }

            // 이미지 파일만 미리보기 지원
            if (!str_starts_with($attachment->mime_type, 'image/')) {
                return $this->errorResponse(
                    '이미지 파일만 미리보기가 가능합니다.',
                    400
                );
            }

            // 파일 존재 확인
            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return $this->errorResponse(
                    '파일을 찾을 수 없습니다.',
                    404
                );
            }

            return Storage::disk('public')->response($attachment->file_path);

        } catch (\Exception $e) {
            Log::error('첨부파일 미리보기 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 미리보기에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Bulk delete attachments
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attachment_ids' => 'required|array|min:1|max:50',
                'attachment_ids.*' => 'exists:attachments,id',
            ]);

            $attachments = Attachment::whereIn('id', $validated['attachment_ids'])->get();
            $deletedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($attachments as $attachment) {
                try {
                    // 권한 체크
                    if (!$this->canDeleteAttachment($request->user(), $attachment)) {
                        $errors[] = [
                            'attachment_id' => $attachment->id,
                            'error' => '삭제 권한이 없습니다.'
                        ];
                        continue;
                    }

                    // 실제 파일 삭제
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }

                    // 데이터베이스에서 삭제
                    $attachment->delete();
                    $deletedCount++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'attachment_id' => $attachment->id,
                        'error' => '삭제 실패: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $message = $deletedCount . '개의 첨부파일이 삭제되었습니다.';
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . '개의 파일 삭제에 실패했습니다.';
            }

            return $this->successResponse([
                'deleted_count' => $deletedCount,
                'errors' => $errors,
                'total_errors' => count($errors)
            ], $message);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('첨부파일 대량 삭제 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '첨부파일 대량 삭제에 실패했습니다.',
                500
            );
        }
    }

    /**
     * 첨부파일 조회 권한 확인
     */
    private function canViewAttachments($user, $complaint): bool
    {
        // 관리자는 모든 첨부파일 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 민원인은 자신의 민원 첨부파일 조회 가능
        if ($complaint->complainant_id === $user->id) {
            return true;
        }

        // 담당자는 할당된 민원 첨부파일 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 관련 민원 첨부파일 조회 가능
        if ($user->hasRole(['teacher', 'staff', 'security_staff', 'ops_staff'])) {
            return true;
        }

        return false;
    }

    /**
     * 첨부파일 조회 권한 확인 (개별)
     */
    private function canViewAttachment($user, $attachment): bool
    {
        return $this->canViewAttachments($user, $attachment->complaint);
    }

    /**
     * 첨부파일 업로드 권한 확인
     */
    private function canUploadAttachment($user, $complaint): bool
    {
        // 관리자는 모든 첨부파일 업로드 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 민원인은 자신의 민원에 첨부파일 업로드 가능
        if ($complaint->complainant_id === $user->id) {
            return true;
        }

        // 담당자는 할당된 민원에 첨부파일 업로드 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 관련 민원에 첨부파일 업로드 가능
        if ($user->hasRole(['teacher', 'staff', 'security_staff', 'ops_staff'])) {
            return true;
        }

        return false;
    }

    /**
     * 첨부파일 다운로드 권한 확인
     */
    private function canDownloadAttachment($user, $attachment): bool
    {
        return $this->canViewAttachment($user, $attachment);
    }

    /**
     * 첨부파일 삭제 권한 확인
     */
    private function canDeleteAttachment($user, $attachment): bool
    {
        // 관리자는 모든 첨부파일 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 업로드한 사용자는 자신의 첨부파일 삭제 가능
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        // 민원인은 자신의 민원 첨부파일 삭제 가능
        if ($attachment->complaint->complainant_id === $user->id) {
            return true;
        }

        return false;
    }
}
