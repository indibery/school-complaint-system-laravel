<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Category;
use App\Models\User;
use App\Models\ComplaintStatusLog;
use App\Notifications\ComplaintAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComplaintController extends Controller
{
    /**
     * 민원 목록 표시 (필터링, 정렬, 검색 지원)
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['category', 'assignedTo'])
            ->withCount('attachments');

        // 검색 기능
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%")
                               ->orWhere('email', 'LIKE', "%{$search}%");
                  });
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
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // 날짜 범위 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 권한 기반 필터링
        $user = Auth::user();
        if ($user->role === 'teacher') {
            // 교사는 자신이 담당하는 민원만
            $query->where('assigned_to', $user->id);
        } elseif ($user->role === 'parent') {
            // 학부모는 자신이 등록한 민원만
            $query->where('user_id', $user->id);
        } elseif (in_array($user->role, ['security_staff', 'ops_staff'])) {
            // 특정 역할은 관련 카테고리만
            $relatedCategories = $this->getRelatedCategories($user->role);
            $query->whereIn('category_id', $relatedCategories);
        }
        // 관리자는 모든 민원

        // 정렬
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // 우선순위 정렬의 경우 특별 처리
        if ($sortBy === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') " . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $complaints = $query->paginate(20);

        // AJAX 요청인 경우
        if ($request->ajax()) {
            $html = view('complaints.partials.table-rows', compact('complaints'))->render();
            $pagination = $complaints->withQueryString()->links()->render();
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
                'total' => Complaint::count(),
                'filtered' => $complaints->total()
            ]);
        }

        // 필터링에 필요한 데이터
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $assignableUsers = User::whereIn('role', ['admin', 'teacher', 'security_staff', 'ops_staff'])
            ->orderBy('name')
            ->get();

        return view('complaints.index', compact('complaints', 'categories', 'assignableUsers'));
    }

    /**
     * 민원 상세 보기
     */
    public function show(Complaint $complaint, Request $request)
    {
        // 권한 확인
        $this->authorize('view', $complaint);

        $complaint->load(['category', 'assignedTo', 'user', 'student', 'comments.user', 'attachments', 'statusLogs.user']);

        // AJAX 요청인 경우 (실시간 업데이트용)
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'comments_count' => $complaint->comments->count(),
                'status' => $complaint->status,
                'assigned_to' => $complaint->assigned_to,
            ]);
        }

        // 할당 가능한 사용자 목록
        $assignableUsers = User::whereIn('role', ['admin', 'teacher', 'security_staff', 'ops_staff'])
            ->orderBy('name')
            ->get();

        return view('complaints.show', compact('complaint', 'assignableUsers'));
    }

    /**
     * 댓글 저장
     */
    public function storeComment(Request $request, Complaint $complaint)
    {
        $this->authorize('comment', $complaint);

        $request->validate([
            'content' => 'required|string|max:2000',
            'is_public' => 'boolean'
        ]);

        try {
            $comment = $complaint->comments()->create([
                'user_id' => Auth::id(),
                'content' => $request->content,
                'is_public' => $request->boolean('is_public', true)
            ]);

            return response()->json([
                'success' => true,
                'message' => '댓글이 성공적으로 등록되었습니다.',
                'comment' => $comment->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('댓글 등록 오류: ' . $e->getMessage());
            
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
            Log::error('댓글 삭제 오류: ' . $e->getMessage());
            
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
        $this->authorize('uploadAttachment', $complaint);

        $request->validate([
            'attachments' => 'required|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,gif'
        ]);

        try {
            $uploadedFiles = [];

            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaints/' . $complaint->id, 'public');
                
                $attachment = $complaint->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id()
                ]);

                $uploadedFiles[] = $attachment;
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . '개의 파일이 성공적으로 업로드되었습니다.',
                'files' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            Log::error('파일 업로드 오류: ' . $e->getMessage());
            
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
        $complaint = $attachment->complaint;
        $this->authorize('view', $complaint);

        $filePath = storage_path('app/public/' . $attachment->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return response()->download($filePath, $attachment->original_name);
    }

    /**
     * 첨부파일 삭제
     */
    public function deleteAttachment(Attachment $attachment)
    {
        $complaint = $attachment->complaint;
        $this->authorize('update', $complaint);

        try {
            // 파일 시스템에서 파일 삭제
            if ($attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // 데이터베이스에서 기록 삭제
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => '첨부파일이 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('첨부파일 삭제 오류: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '첨부파일 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 등록 폼
     */
    public function create()
    {
        $this->authorize('create', Complaint::class);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('complaints.create', compact('categories'));
    }

    /**
     * 민원 저장
     */
    public function store(Request $request)
    {
        $this->authorize('create', Complaint::class);

        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'complainant_name' => 'required|string|max:100',
            'complainant_email' => 'required|email|max:100',
            'complainant_phone' => 'nullable|string|max:20',
            'attachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,gif'
        ]);

        try {
            DB::beginTransaction();

            $complaint = Complaint::create([
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'priority' => $request->priority,
                'status' => 'submitted',
                'complainant_name' => $request->complainant_name,
                'complainant_email' => $request->complainant_email,
                'complainant_phone' => $request->complainant_phone,
                'complainant_id' => Auth::id(),
                'complaint_number' => $this->generateComplaintNumber()
            ]);

            // 첨부파일 처리
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/' . $complaint->id, 'public');
                    
                    $complaint->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => Auth::id()
                    ]);
                }
            }

            // 상태 로그 기록
            ComplaintStatusLog::create([
                'complaint_id' => $complaint->id,
                'status' => 'submitted',
                'changed_by' => Auth::id(),
                'notes' => '민원이 등록되었습니다.'
            ]);

            DB::commit();

            return redirect()->route('complaints.show', $complaint)
                ->with('success', '민원이 성공적으로 등록되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('민원 등록 오류: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', '민원 등록 중 오류가 발생했습니다.');
        }
    }

    /**
     * 민원 수정 폼
     */
    public function edit(Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('complaints.edit', compact('complaint', 'categories'));
    }

    /**
     * 민원 업데이트
     */
    public function update(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'complainant_name' => 'required|string|max:100',
            'complainant_email' => 'required|email|max:100',
            'complainant_phone' => 'nullable|string|max:20',
        ]);

        try {
            $complaint->update([
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'priority' => $request->priority,
                'complainant_name' => $request->complainant_name,
                'complainant_email' => $request->complainant_email,
                'complainant_phone' => $request->complainant_phone,
            ]);

            return redirect()->route('complaints.show', $complaint)
                ->with('success', '민원이 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            Log::error('민원 수정 오류: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', '민원 수정 중 오류가 발생했습니다.');
        }
    }

    /**
     * 대량 업데이트 처리
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'complaint_ids' => 'required|array',
            'complaint_ids.*' => 'exists:complaints,id',
            'status' => 'nullable|in:submitted,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        try {
            DB::beginTransaction();

            $complaintsQuery = Complaint::whereIn('id', $request->complaint_ids);
            
            // 권한 확인
            $user = Auth::user();
            if ($user->role === 'parent') {
                $complaintsQuery->where('complainant_id', $user->id);
            } elseif ($user->role === 'teacher') {
                $complaintsQuery->where('assigned_to', $user->id);
            } elseif (in_array($user->role, ['security_staff', 'ops_staff'])) {
                $relatedCategories = $this->getRelatedCategories($user->role);
                $complaintsQuery->whereIn('category_id', $relatedCategories);
            }

            $complaints = $complaintsQuery->get();
            
            if ($complaints->isEmpty()) {
                return response()->json(['success' => false, 'message' => '권한이 없거나 존재하지 않는 민원입니다.']);
            }

            $updatedCount = 0;

            foreach ($complaints as $complaint) {
                $originalData = $complaint->toArray();
                $changes = [];

                // 상태 변경
                if ($request->filled('status') && $complaint->status !== $request->status) {
                    $complaint->status = $request->status;
                    $changes['status'] = ['from' => $originalData['status'], 'to' => $request->status];
                }

                // 담당자 할당
                if ($request->filled('assigned_to') && $complaint->assigned_to != $request->assigned_to) {
                    $complaint->assigned_to = $request->assigned_to;
                    $changes['assigned_to'] = ['from' => $originalData['assigned_to'], 'to' => $request->assigned_to];
                }

                if (!empty($changes)) {
                    $complaint->save();

                    // 상태 로그 기록
                    if (isset($changes['status'])) {
                        ComplaintStatusLog::create([
                            'complaint_id' => $complaint->id,
                            'status' => $request->status,
                            'changed_by' => $user->id,
                            'notes' => '대량 업데이트로 상태가 변경되었습니다.'
                        ]);
                    }

                    $updatedCount++;

                    // 담당자 할당 알림
                    if (isset($changes['assigned_to']) && $changes['assigned_to']['to']) {
                        $assignedUser = User::find($changes['assigned_to']['to']);
                        if ($assignedUser) {
                            $assignedUser->notify(new ComplaintAssigned($complaint));
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount}개의 민원이 성공적으로 업데이트되었습니다.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('대량 업데이트 오류: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '대량 업데이트 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 민원 내보내기 (Excel)
     */
    public function export(Request $request)
    {
        // 필터 조건 적용
        $query = Complaint::with(['category', 'assignedTo', 'complainant']);

        // 검색 및 필터 적용 (index 메서드와 동일한 로직)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('complainant_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 권한 기반 필터링
        $user = Auth::user();
        if ($user->role === 'teacher') {
            $query->where('assigned_to', $user->id);
        } elseif ($user->role === 'parent') {
            $query->where('complainant_id', $user->id);
        } elseif (in_array($user->role, ['security_staff', 'ops_staff'])) {
            $relatedCategories = $this->getRelatedCategories($user->role);
            $query->whereIn('category_id', $relatedCategories);
        }

        $complaints = $query->orderBy('created_at', 'desc')->get();

        // CSV 내보내기
        $filename = '민원목록_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($complaints) {
            $file = fopen('php://output', 'w');
            
            // BOM 추가 (Excel에서 한글 깨짐 방지)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 헤더 작성
            fputcsv($file, [
                '민원번호',
                '제목',
                '민원인명',
                '민원인 이메일',
                '카테고리',
                '상태',
                '우선순위',
                '담당자',
                '등록일',
                '수정일'
            ]);
            
            // 데이터 작성
            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint->complaint_number,
                    $complaint->title,
                    $complaint->complainant_name,
                    $complaint->complainant_email,
                    $complaint->category->name ?? '',
                    $this->getStatusLabel($complaint->status),
                    $this->getPriorityLabel($complaint->priority),
                    $complaint->assignedTo->name ?? '미할당',
                    $complaint->created_at->format('Y-m-d H:i:s'),
                    $complaint->updated_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 민원번호 생성
     */
    private function generateComplaintNumber()
    {
        $date = now()->format('Ymd');
        $lastComplaint = Complaint::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastComplaint ? (int)substr($lastComplaint->complaint_number, -3) + 1 : 1;
        
        return $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 역할별 관련 카테고리 가져오기
     */
    private function getRelatedCategories($role)
    {
        $categoryMap = [
            'security_staff' => ['시설/환경', '교통/안전'],
            'ops_staff' => ['급식', '기타']
        ];

        $categoryNames = $categoryMap[$role] ?? [];
        
        return Category::whereIn('name', $categoryNames)->pluck('id')->toArray();
    }

    /**
     * 상태 라벨 가져오기
     */
    private function getStatusLabel($status)
    {
        $statusLabels = [
            'submitted' => '접수 완료',
            'in_progress' => '처리 중',
            'resolved' => '해결 완료',
            'closed' => '종료'
        ];

        return $statusLabels[$status] ?? $status;
    }

    /**
     * 우선순위 라벨 가져오기
     */
    private function getPriorityLabel($priority)
    {
        $priorityLabels = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급'
        ];

        return $priorityLabels[$priority] ?? $priority;
    }
}
