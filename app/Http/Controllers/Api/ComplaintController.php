<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Complaint\ComplaintStoreRequest;
use App\Http\Requests\Api\Complaint\ComplaintUpdateRequest;
use App\Http\Requests\Api\Complaint\ComplaintIndexRequest;
use App\Http\Requests\Api\Complaint\ComplaintStatusRequest;
use App\Http\Requests\Api\Complaint\ComplaintAssignRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Services\Complaint\ComplaintServiceInterface;
use App\Services\Complaint\ComplaintStatusServiceInterface;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Services\Complaint\ComplaintFileServiceInterface;
use App\Services\Complaint\ComplaintStatisticsServiceInterface;
use App\Actions\Complaint\CreateComplaintAction;
use App\Actions\Complaint\UpdateComplaintStatusAction;
use App\Actions\Complaint\AssignComplaintAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends BaseApiController
{
    public function __construct(
        private ComplaintServiceInterface $complaintService,
        private ComplaintStatusServiceInterface $statusService,
        private ComplaintAssignmentServiceInterface $assignmentService,
        private ComplaintFileServiceInterface $fileService,
        private ComplaintStatisticsServiceInterface $statisticsService,
        private CreateComplaintAction $createAction,
        private UpdateComplaintStatusAction $updateStatusAction,
        private AssignComplaintAction $assignAction
    ) {}

    /**
     * Display a listing of the complaints.
     */
    public function index(ComplaintIndexRequest $request): JsonResponse
    {
        try {
            $complaints = $this->complaintService->getList(
                $request->validated(),
                $request->user()
            );
            
            $meta = [];
            if ($request->input('with_statistics') && $request->user()->hasRole('admin')) {
                $meta['statistics'] = $this->statisticsService->getDashboardStats($request->user());
            }
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '민원 목록을 조회했습니다.',
                $meta
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(ComplaintStoreRequest $request): JsonResponse
    {
        try {
            $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
            
            $complaint = $this->createAction->execute(
                $request->validated(),
                $request->user(),
                $files
            );
            
            return $this->createdResponse(
                new ComplaintResource($complaint),
                '민원이 성공적으로 접수되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 접수 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified complaint.
     */
    public function show(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $complaint = $this->complaintService->find($complaint->id, $request->user());
            
            if (!$complaint) {
                return $this->notFoundResponse('민원을 찾을 수 없습니다.');
            }
            
            return $this->successResponse(
                new ComplaintResource($complaint),
                '민원 정보를 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 정보 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified complaint in storage.
     */
    public function update(ComplaintUpdateRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            $complaint = $this->complaintService->update(
                $complaint,
                $request->validated(),
                $request->user()
            );
            
            // 첨부파일 처리
            if ($request->hasFile('attachments')) {
                $this->fileService->uploadFiles(
                    $complaint,
                    $request->file('attachments'),
                    $request->user()
                );
            }
            
            // 첨부파일 삭제
            if ($request->has('remove_attachments')) {
                $this->fileService->deleteFiles(
                    $complaint,
                    $request->input('remove_attachments'),
                    $request->user()
                );
            }
            
            return $this->updatedResponse(
                new ComplaintResource($complaint),
                '민원 정보가 성공적으로 수정되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 정보 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified complaint from storage.
     */
    public function destroy(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $this->complaintService->delete($complaint, $request->user());
            
            return $this->deletedResponse(
                '민원이 성공적으로 삭제되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get my complaints.
     */
    public function myComplaints(Request $request): JsonResponse
    {
        try {
            $complaints = $this->complaintService->getMyComplaints(
                $request->user(),
                $request->all()
            );
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '내 민원 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '내 민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get complaints assigned to me.
     */
    public function assignedToMe(Request $request): JsonResponse
    {
        try {
            $complaints = $this->complaintService->getAssignedComplaints(
                $request->user(),
                $request->all()
            );
            
            return $this->paginatedResourceResponse(
                ComplaintResource::collection($complaints),
                '내 할당 민원 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '내 할당 민원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update complaint status.
     */
    public function updateStatus(ComplaintStatusRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            $complaint = $this->updateStatusAction->execute(
                $complaint,
                $request->input('status'),
                $request->user(),
                $request->validated()
            );
            
            return $this->updatedResponse(
                new ComplaintResource($complaint),
                '민원 상태가 성공적으로 변경되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Assign complaint to user.
     */
    public function assign(ComplaintAssignRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            $assignee = \App\Models\User::findOrFail($request->input('assigned_to'));
            
            $complaint = $this->assignAction->execute(
                $complaint,
                $assignee,
                $request->user(),
                $request->validated()
            );
            
            return $this->updatedResponse(
                new ComplaintResource($complaint),
                '민원이 성공적으로 할당되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '민원 할당 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get assignable users for complaint.
     */
    public function getAssignableUsers(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $users = $this->assignmentService->getAssignableUsers($complaint);
            
            return $this->successResponse(
                $users,
                '할당 가능한 사용자 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '할당 가능한 사용자 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get complaint statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'dashboard');
            $user = $request->user();
            
            $statistics = match ($type) {
                'dashboard' => $this->statisticsService->getDashboardStats($user),
                'status' => $this->statisticsService->getStatusBreakdown($user),
                'priority' => $this->statisticsService->getPriorityBreakdown($user),
                'category' => $this->statisticsService->getCategoryBreakdown($user),
                'department' => $this->statisticsService->getDepartmentBreakdown($user),
                'processing_time' => $this->statisticsService->getProcessingTimeStats($user),
                'satisfaction' => $this->statisticsService->getSatisfactionStats($user),
                'performance' => $this->statisticsService->getPerformanceMetrics($user),
                'trends' => $this->statisticsService->getTrendAnalysis($user),
                default => $this->statisticsService->getDashboardStats($user)
            };
            
            return $this->successResponse(
                $statistics,
                '통계 정보를 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '통계 정보 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Export complaints statistics.
     */
    public function exportStatistics(Request $request): JsonResponse
    {
        try {
            $format = $request->input('format', 'excel');
            $filters = $request->only(['status', 'priority', 'category_id', 'department_id']);
            
            $filePath = $this->statisticsService->exportStats(
                $request->user(),
                $filters,
                $format
            );
            
            return $this->successResponse([
                'file_path' => $filePath,
                'download_url' => route('api.complaints.download-export', [
                    'file' => basename($filePath)
                ])
            ], '통계 데이터 내보내기가 완료되었습니다.');
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '통계 데이터 내보내기 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Download exported file.
     */
    public function downloadExport(Request $request, string $file): JsonResponse
    {
        try {
            $filePath = storage_path('app/exports/' . $file);
            
            if (!file_exists($filePath)) {
                return $this->notFoundResponse('파일을 찾을 수 없습니다.');
            }
            
            return response()->download($filePath);
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '파일 다운로드 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Upload complaint attachments.
     */
    public function uploadAttachments(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $request->validate([
                'attachments' => 'required|array|max:10',
                'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt,xls,xlsx'
            ]);
            
            $result = $this->fileService->uploadFiles(
                $complaint,
                $request->file('attachments'),
                $request->user()
            );
            
            return $this->successResponse(
                $result,
                '파일이 성공적으로 업로드되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '파일 업로드 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Delete complaint attachments.
     */
    public function deleteAttachments(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $request->validate([
                'attachment_ids' => 'required|array',
                'attachment_ids.*' => 'integer|exists:complaint_attachments,id'
            ]);
            
            $result = $this->fileService->deleteFiles(
                $complaint,
                $request->input('attachment_ids'),
                $request->user()
            );
            
            return $this->successResponse(
                ['deleted' => $result],
                '파일이 성공적으로 삭제되었습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '파일 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Download complaint attachment.
     */
    public function downloadAttachment(Request $request, Complaint $complaint, int $attachmentId): JsonResponse
    {
        try {
            $downloadUrl = $this->fileService->getDownloadUrl($attachmentId, $request->user());
            
            if (!$downloadUrl) {
                return $this->notFoundResponse('파일을 찾을 수 없거나 접근 권한이 없습니다.');
            }
            
            return $this->successResponse([
                'download_url' => $downloadUrl
            ], '다운로드 URL을 생성했습니다.');
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '파일 다운로드 URL 생성 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get available status options.
     */
    public function getStatusOptions(): JsonResponse
    {
        return $this->successResponse(
            $this->statusService->getAvailableStatuses(),
            '상태 옵션을 조회했습니다.'
        );
    }

    /**
     * Get status transition rules.
     */
    public function getStatusTransitions(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            $currentStatus = $complaint->status;
            $availableStatuses = $this->statusService->getAvailableStatuses();
            $validTransitions = [];
            
            foreach ($availableStatuses as $status => $label) {
                if ($this->statusService->isValidStatusTransition($currentStatus, $status)) {
                    $validTransitions[$status] = [
                        'label' => $label,
                        'color' => $this->statusService->getStatusColor($status),
                        'required_fields' => $this->statusService->getRequiredFieldsForStatus($status)
                    ];
                }
            }
            
            return $this->successResponse(
                $validTransitions,
                '상태 전환 규칙을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '상태 전환 규칙 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
