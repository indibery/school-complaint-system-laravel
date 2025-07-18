<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Complaint;
use App\Models\Category;
use App\Models\User;
use App\Services\Complaint\ComplaintServiceInterface;
use App\Services\Complaint\ComplaintStatusServiceInterface;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Services\Complaint\ComplaintFileServiceInterface;
use App\Services\Complaint\ComplaintStatisticsServiceInterface;
use App\Actions\Complaint\CreateComplaintAction;
use App\Actions\Complaint\UpdateComplaintStatusAction;
use App\Actions\Complaint\AssignComplaintAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComplaintController extends Controller
{
    use AuthorizesRequests;
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
     * 민원 목록 표시
     */
    public function index(Request $request)
    {
        try {
            $complaints = $this->complaintService->getList(
                $request->all(),
                $request->user()
            );

            // 통계 데이터
            $stats = $this->statisticsService->getDashboardStats($request->user());

            // 필터 옵션
            $categories = Category::where('is_active', true)->orderBy('name')->get();
            $assignees = User::role(['admin', 'staff'])->orderBy('name')->get();

            return view('complaints.index', compact(
                'complaints',
                'stats',
                'categories',
                'assignees'
            ));

        } catch (\Exception $e) {
            Log::error('민원 목록 조회 실패', ['error' => $e->getMessage()]);
            return back()->with('error', '민원 목록 조회 중 오류가 발생했습니다.');
        }
    }

    /**
     * 민원 상세 보기
     */
    public function show(Complaint $complaint)
    {
        try {
            $this->authorize('view', $complaint);

            $complaint = $this->complaintService->find($complaint->id, Auth::user());

            if (!$complaint) {
                return redirect()->route('complaints.index')
                    ->with('error', '민원을 찾을 수 없습니다.');
            }

            return view('complaints.show', compact('complaint'));

        } catch (\Exception $e) {
            Log::error('민원 상세 조회 실패', ['error' => $e->getMessage()]);
            return back()->with('error', '민원 조회 중 오류가 발생했습니다.');
        }
    }

    /**
     * 민원 등록 폼
     */
    public function create()
    {
        $this->authorize('create', Complaint::class);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $students = Auth::user()->students ?? collect();

        return view('complaints.create', compact('categories', 'students'));
    }

    /**
     * 민원 저장
     */
    public function store(Request $request)
    {
        $this->authorize('create', Complaint::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'student_id' => 'nullable|exists:students,id',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'
        ]);

        try {
            // 서비스 레이어를 사용하여 민원 생성
            $complaint = $this->complaintService->create($validated, $request->user());

            // 첨부파일 처리 (서비스 레이어 사용)
            if ($request->hasFile('attachments')) {
                $this->fileService->uploadFiles(
                    $complaint,
                    $request->file('attachments'),
                    $request->user()
                );
            }

            return redirect()
                ->route('web.complaints.index')
                ->with('success', '민원이 성공적으로 접수되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 생성 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'validated_data' => $validated
            ]);
            
            return back()
                ->withInput()
                ->with('error', '민원 접수 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 민원 수정 폼
     */
    public function edit(Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $students = Auth::user()->students ?? collect();

        return view('complaints.edit', compact('complaint', 'categories', 'students'));
    }

    /**
     * 민원 업데이트
     */
    public function update(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'student_id' => 'nullable|exists:students,id',
            'is_anonymous' => 'boolean',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'integer|exists:complaint_attachments,id'
        ]);

        try {
            $complaint = $this->complaintService->update(
                $complaint,
                $validated,
                $request->user()
            );

            // 새 첨부파일 처리
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

            return redirect()
                ->route('complaints.show', $complaint)
                ->with('success', '민원이 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 수정 실패', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', '민원 수정 중 오류가 발생했습니다.');
        }
    }

    /**
     * 민원 삭제
     */
    public function destroy(Complaint $complaint)
    {
        $this->authorize('delete', $complaint);

        try {
            $this->complaintService->delete($complaint, Auth::user());

            return redirect()
                ->route('complaints.index')
                ->with('success', '민원이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 삭제 실패', ['error' => $e->getMessage()]);
            return back()->with('error', '민원 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 민원 상태 변경
     */
    public function updateStatus(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'status' => 'required|in:pending,assigned,in_progress,resolved,closed,cancelled',
            'reason' => 'nullable|string|max:1000',
            'notify_submitter' => 'boolean'
        ]);

        try {
            $complaint = $this->updateStatusAction->execute(
                $complaint,
                $validated['status'],
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => '민원 상태가 성공적으로 변경되었습니다.',
                'status' => $validated['status'],
                'status_label' => $this->statusService->getStatusLabel($validated['status'])
            ]);

        } catch (\Exception $e) {
            Log::error('민원 상태 변경 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '민원 상태 변경 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 담당자 할당
     */
    public function assignUser(Request $request, Complaint $complaint)
    {
        $this->authorize('assign', $complaint);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'assignment_note' => 'nullable|string|max:1000',
            'notify_assignee' => 'boolean',
            'notify_submitter' => 'boolean'
        ]);

        try {
            $assignee = User::findOrFail($validated['assigned_to']);
            
            $complaint = $this->assignAction->execute(
                $complaint,
                $assignee,
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => '담당자가 성공적으로 할당되었습니다.',
                'assigned_to' => $assignee->name
            ]);

        } catch (\Exception $e) {
            Log::error('담당자 할당 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '담당자 할당 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 우선순위 변경
     */
    public function updatePriority(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $complaint = $this->complaintService->update(
                $complaint,
                ['priority' => $validated['priority']],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => '민원 우선순위가 성공적으로 변경되었습니다.',
                'priority' => $validated['priority']
            ]);

        } catch (\Exception $e) {
            Log::error('민원 우선순위 변경 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '민원 우선순위 변경 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 첨부파일 업로드
     */
    public function uploadAttachment(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $request->validate([
            'attachments' => 'required|array|max:10',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'
        ]);

        try {
            $result = $this->fileService->uploadFiles(
                $complaint,
                $request->file('attachments'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => count($result['uploaded_files']) . '개의 파일이 업로드되었습니다.',
                'attachments' => $result['uploaded_files'],
                'errors' => $result['errors']
            ]);

        } catch (\Exception $e) {
            Log::error('파일 업로드 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '파일 업로드 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 첨부파일 다운로드
     */
    public function downloadAttachment(Complaint $complaint, int $attachmentId)
    {
        $this->authorize('view', $complaint);

        try {
            $downloadUrl = $this->fileService->getDownloadUrl($attachmentId, Auth::user());

            if (!$downloadUrl) {
                abort(404, '파일을 찾을 수 없거나 접근 권한이 없습니다.');
            }

            return redirect($downloadUrl);

        } catch (\Exception $e) {
            Log::error('파일 다운로드 실패', ['error' => $e->getMessage()]);
            abort(500, '파일 다운로드 중 오류가 발생했습니다.');
        }
    }

    /**
     * 첨부파일 삭제
     */
    public function deleteAttachment(Request $request, Complaint $complaint, int $attachmentId)
    {
        $this->authorize('update', $complaint);

        try {
            $result = $this->fileService->deleteFiles(
                $complaint,
                [$attachmentId],
                $request->user()
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? '첨부파일이 성공적으로 삭제되었습니다.' : '파일 삭제에 실패했습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('첨부파일 삭제 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '첨부파일 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 할당 가능한 사용자 목록
     */
    public function getAssignableUsers(Complaint $complaint)
    {
        $this->authorize('assign', $complaint);

        try {
            $users = $this->assignmentService->getAssignableUsers($complaint);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('할당 가능한 사용자 조회 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '할당 가능한 사용자 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 상태 전환 옵션 조회
     */
    public function getStatusTransitions(Complaint $complaint)
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

            return response()->json([
                'success' => true,
                'data' => $validTransitions
            ]);

        } catch (\Exception $e) {
            Log::error('상태 전환 옵션 조회 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '상태 전환 옵션 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 통계
     */
    public function statistics(Request $request)
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
                'trends' => $this->statisticsService->getTrendAnalysis($user),
                default => $this->statisticsService->getDashboardStats($user)
            };

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('통계 조회 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '통계 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 내보내기
     */
    public function export(Request $request)
    {
        $this->authorize('export', Complaint::class);

        try {
            $format = $request->input('format', 'csv');
            $filters = $request->only([
                'status', 'priority', 'category_id', 'assigned_to',
                'date_from', 'date_to'
            ]);

            if ($format === 'csv') {
                return $this->exportToCsv($filters, $request->user());
            }

            $filePath = $this->statisticsService->exportStats(
                $request->user(),
                $filters,
                $format
            );

            return response()->download($filePath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('민원 내보내기 실패', ['error' => $e->getMessage()]);
            return back()->with('error', '민원 내보내기 중 오류가 발생했습니다.');
        }
    }

    /**
     * CSV 내보내기
     */
    private function exportToCsv(array $filters, $user)
    {
        $complaints = $this->complaintService->getList($filters, $user);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="complaints_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($complaints) {
            $file = fopen('php://output', 'w');
            
            // BOM 추가 (Excel에서 한글 깨짐 방지)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 헤더 행
            fputcsv($file, [
                '민원번호', '제목', '카테고리', '상태', '우선순위',
                '민원인', '담당자', '등록일', '처리일'
            ]);
            
            // 데이터 행
            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint->complaint_number,
                    $complaint->title,
                    $complaint->category->name ?? '',
                    $this->statusService->getStatusLabel($complaint->status),
                    Str::title($complaint->priority),
                    $complaint->complainant->name ?? '',
                    $complaint->assignedTo->name ?? '-',
                    $complaint->created_at->format('Y-m-d H:i'),
                    $complaint->resolved_at ? $complaint->resolved_at->format('Y-m-d H:i') : '-'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 민원 검색 (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }

        try {
            $complaints = $this->complaintService->getList([
                'search' => $query,
                'per_page' => 10
            ], $request->user());

            return response()->json([
                'success' => true,
                'data' => $complaints->items()
            ]);

        } catch (\Exception $e) {
            Log::error('민원 검색 실패', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '검색 중 오류가 발생했습니다.'
            ], 500);
        }
    }
}
