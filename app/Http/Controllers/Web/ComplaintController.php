<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Category;
use App\Models\User;
use App\Models\Comment;
use App\Models\Attachment;
use App\Models\ComplaintStatusLog;
use App\Notifications\ComplaintAssigned;
use App\Notifications\ComplaintCreatedNotification;
use App\Notifications\ComplaintStatusChangedNotification;
use App\Notifications\ComplaintCommentedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class ComplaintController extends Controller
{
    /**
     * 민원 목록 표시 (필터링, 정렬, 검색 지원)
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['category', 'assignedTo', 'complainant'])
            ->withCount(['attachments', 'comments']);

        // 접근 권한 필터링
        $this->applyAccessControl($query, $request);

        // 검색 필터
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%")
                  ->orWhere('complaint_number', 'LIKE', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 카테고리 필터  
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 우선순위 필터
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // 담당자 필터
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // 날짜 범위 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 정렬
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'priority':
                $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')");
                break;
            case 'status':
                $query->orderByRaw("FIELD(status, 'pending', 'in_progress', 'resolved', 'closed')");
                break;
            default:
                $query->latest();
        }

        $complaints = $query->paginate(20)->withQueryString();

        // 통계 데이터
        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'urgent' => Complaint::where('priority', 'urgent')->count(),
        ];

        // 필터 옵션
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $assignees = User::role(['admin', 'staff', 'teacher'])->orderBy('name')->get();

        return view('complaints.index', compact(
            'complaints',
            'stats',
            'categories',
            'assignees'
        ));
    }

    /**
     * 민원 상세 보기
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load([
            'category',
            'complainant',
            'assignedTo',
            'comments.user',
            'attachments',
            'statusLogs.user'
        ]);

        return view('complaints.show', compact('complaint'));
    }

    /**
     * 민원 등록 폼
     */
    public function create()
    {
        $this->authorize('create', Complaint::class);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $students = Auth::user()->students ?? collect(); // 학부모인 경우 자녀 목록

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
            'is_anonymous' => 'boolean',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'
        ]);

        try {
            DB::beginTransaction();

            // 민원 생성
            $complaint = new Complaint();
            $complaint->title = $validated['title'];
            $complaint->content = $validated['content'];
            $complaint->category_id = $validated['category_id'];
            $complaint->priority = $validated['priority'];
            $complaint->status = 'pending';
            $complaint->user_id = Auth::id();
            $complaint->student_id = $validated['student_id'] ?? null;
            $complaint->is_anonymous = $validated['is_anonymous'] ?? false;
            $complaint->complaint_number = $this->generateComplaintNumber();
            $complaint->save();

            // 첨부파일 처리
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints/' . $complaint->id, 'public');
                    
                    $complaint->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => Auth::id()
                    ]);
                }
            }

            // 상태 로그 생성
            $complaint->statusLogs()->create([
                'status' => 'pending',
                'comment' => '민원이 접수되었습니다.',
                'user_id' => Auth::id()
            ]);

            // 관리자에게 알림 발송
            $admins = User::role('admin')->get();
            Notification::send($admins, new ComplaintCreatedNotification($complaint));

            // 카테고리별 담당자에게도 알림 발송
            $categoryStaff = User::whereHas('categories', function($query) use ($complaint) {
                $query->where('categories.id', $complaint->category_id);
            })->get();
            
            if ($categoryStaff->isNotEmpty()) {
                Notification::send($categoryStaff, new ComplaintCreatedNotification($complaint));
            }

            DB::commit();

            return redirect()
                ->route('complaints.show', $complaint)
                ->with('success', '민원이 성공적으로 접수되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('민원 생�� 실패: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', '민원 접수 중 오류가 발생했습니다.');
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
            'is_anonymous' => 'boolean'
        ]);

        try {
            $complaint->update($validated);

            return redirect()
                ->route('complaints.show', $complaint)
                ->with('success', '민원이 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 수정 실패: ' . $e->getMessage());
            
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
            // 첨부파일 삭제
            foreach ($complaint->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $complaint->delete();

            return redirect()
                ->route('complaints.index')
                ->with('success', '민원이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 삭제 실패: ' . $e->getMessage());
            
            return back()->with('error', '민원 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 댓글 추가
     */
    public function storeComment(Request $request, Complaint $complaint)
    {
        $this->authorize('comment', $complaint);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'is_internal' => 'boolean'
        ]);

        try {
            $comment = $complaint->comments()->create([
                'user_id' => Auth::id(),
                'content' => $validated['content'],
                'is_internal' => $validated['is_internal'] ?? false
            ]);

            // 민원인에게 알림 발송 (내부 댓글이 아닌 경우)
            if (!$comment->is_internal && $complaint->user_id !== Auth::id()) {
                $complaint->complainant->notify(new ComplaintCommentedNotification($complaint, $comment));
            }

            return response()->json([
                'success' => true,
                'message' => '댓글이 성공적으로 등록되었습니다.',
                'comment' => $comment->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('댓글 등록 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '댓글 등록 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 댓글 삭제
     */
    public function destroyComment(Comment $comment)
    {
        $this->authorize('delete', $comment);

        try {
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => '댓글이 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('댓글 삭제 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '댓글 삭제 중 오류가 발생했습니다.'
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
            'attachments' => 'required|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'
        ]);

        try {
            $uploaded = [];

            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaints/' . $complaint->id, 'public');
                
                $attachment = $complaint->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id()
                ]);

                $uploaded[] = $attachment;
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) . '개의 파일이 업로드되었습니다.',
                'attachments' => $uploaded
            ]);

        } catch (\Exception $e) {
            Log::error('파일 업로드 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '파일 업로드 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 첨부파일 다운로드
     */
    public function downloadAttachment(Attachment $attachment)
    {
        $this->authorize('view', $attachment->complaint);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    /**
     * 첨부파일 삭제
     */
    public function deleteAttachment(Attachment $attachment)
    {
        $this->authorize('update', $attachment->complaint);

        try {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => '첨부파일이 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('첨부파일 삭제 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '첨부파일 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 상태 변경
     */
    public function updateStatus(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $complaint->status;
            $complaint->update(['status' => $validated['status']]);

            // 상태 로그 생성
            $complaint->statusLogs()->create([
                'status' => $validated['status'],
                'comment' => $validated['comment'] ?? "상태가 '{$oldStatus}'에서 '{$validated['status']}'로 변경되었습니다.",
                'user_id' => Auth::id(),
            ]);

            // 민원인에게 알림 발송
            if ($complaint->user_id !== Auth::id()) {
                $complaint->complainant->notify(new ComplaintStatusChangedNotification($complaint, $oldStatus, $validated['status']));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '민원 상태가 성공적으로 변경되었습니다.',
                'status' => $validated['status']
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('민원 상태 변경 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '민원 상태 변경에 실패했습니다.'
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
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $assignee = User::find($validated['assigned_to']);
            $complaint->update(['assigned_to' => $validated['assigned_to']]);

            // 상태 로그 생성
            $complaint->statusLogs()->create([
                'status' => $complaint->status,
                'comment' => $validated['comment'] ?? "담당자가 '{$assignee->name}'님으로 할당되었습니다.",
                'user_id' => Auth::id(),
            ]);

            // 담당자에게 알림 발송
            $assignee->notify(new ComplaintAssigned($complaint));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '담당자가 성공적으로 할당되었습니다.',
                'assigned_to' => $assignee->name
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('담당자 할당 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '담당자 할당에 실패했습니다.'
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
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldPriority = $complaint->priority;
            $complaint->update(['priority' => $validated['priority']]);

            // 상태 로그 생성
            $complaint->statusLogs()->create([
                'status' => $complaint->status,
                'comment' => $validated['comment'] ?? "우선순위가 '{$oldPriority}'에서 '{$validated['priority']}'로 변경되었습니다.",
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '민원 우선순위가 성공적으로 변경되었습니다.',
                'priority' => $validated['priority']
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('민원 우선순위 변경 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '민원 우선순위 변경에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 대량 업데이트
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'complaint_ids' => 'required|array',
            'complaint_ids.*' => 'exists:complaints,id',
            'action' => 'required|in:status,assign,priority',
            'value' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $complaints = Complaint::whereIn('id', $validated['complaint_ids'])->get();
            $updatedCount = 0;

            foreach ($complaints as $complaint) {
                if (!Auth::user()->can('update', $complaint)) {
                    continue;
                }

                switch ($validated['action']) {
                    case 'status':
                        $complaint->update(['status' => $validated['value']]);
                        break;
                    case 'assign':
                        $complaint->update(['assigned_to' => $validated['value']]);
                        if ($validated['value']) {
                            User::find($validated['value'])->notify(new ComplaintAssigned($complaint));
                        }
                        break;
                    case 'priority':
                        $complaint->update(['priority' => $validated['value']]);
                        break;
                }

                $updatedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount}개의 민원이 업데이트되었습니다."
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('대량 업데이트 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '대량 업데이트에 실패했습니다.'
            ], 500);
        }
    }

    /**
     * 접근 권한 필터링 적용
     */
    private function applyAccessControl($query, $request)
    {
        $user = Auth::user();

        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return;
        }

        // 일반 사용자는 자신의 민원만
        if ($user->hasRole('user') || $user->hasRole('parent')) {
            $query->where('user_id', $user->id);
            return;
        }

        // 교사/직원은 할당된 민원만
        if ($user->hasRole(['teacher', 'staff'])) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('category', function($catQuery) use ($user) {
                      $catQuery->whereHas('users', function($userQuery) use ($user) {
                          $userQuery->where('users.id', $user->id);
                      });
                  });
            });
        }
    }

    /**
     * 민원 번호 생성
     */
    private function generateComplaintNumber()
    {
        $today = now()->format('Ymd');
        $count = Complaint::whereDate('created_at', today())->count() + 1;
        
        return $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

        $complaints = Complaint::where('title', 'LIKE', "%{$query}%")
            ->orWhere('complaint_number', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'complaint_number', 'status']);

        return response()->json([
            'success' => true,
            'data' => $complaints
        ]);
    }

    /**
     * 민원 통계
     */
    public function statistics(Request $request)
    {
        $baseQuery = Complaint::query();
        $this->applyAccessControl($baseQuery, $request);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'by_status' => [
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
                'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            ],
            'by_priority' => [
                'urgent' => (clone $baseQuery)->where('priority', 'urgent')->count(),
                'high' => (clone $baseQuery)->where('priority', 'high')->count(),
                'normal' => (clone $baseQuery)->where('priority', 'normal')->count(),
                'low' => (clone $baseQuery)->where('priority', 'low')->count(),
            ],
            'by_category' => Category::withCount(['complaints' => function($query) use ($baseQuery) {
                    $query->whereIn('complaints.id', (clone $baseQuery)->pluck('id'));
                }])
                ->having('complaints_count', '>', 0)
                ->orderBy('complaints_count', 'desc')
                ->limit(10)
                ->get()
                ->pluck('complaints_count', 'name'),
            'trend' => (clone $baseQuery)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 민원 내보내기
     */
    public function export(Request $request)
    {
        $this->authorize('export', Complaint::class);

        $query = Complaint::with(['category', 'complainant', 'assignedTo']);
        $this->applyAccessControl($query, $request);

        // 필터 적용
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $complaints = $query->orderBy('created_at', 'desc')->get();

        // CSV 헤더
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="complaints_' . date('Y-m-d') . '.csv"',
        ];

        // CSV 내용 생성
        $callback = function() use ($complaints) {
            $file = fopen('php://output', 'w');
            
            // BOM 추가 (Excel에서 한글 깨짐 방지)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 헤더 행
            fputcsv($file, [
                '민원번호',
                '제목',
                '카테고리',
                '상태',
                '우선순위',
                '민원인',
                '담당자',
                '등록일',
                '처리일'
            ]);
            
            // 데이터 행
            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint->complaint_number,
                    $complaint->title,
                    $complaint->category->name,
                    $complaint->status_text,
                    $complaint->priority_text,
                    $complaint->complainant->name,
                    $complaint->assignedTo->name ?? '-',
                    $complaint->created_at->format('Y-m-d H:i'),
                    $complaint->resolved_at ? $complaint->resolved_at->format('Y-m-d H:i') : '-'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
