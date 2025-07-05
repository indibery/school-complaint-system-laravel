<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Comment\CommentStoreRequest;
use App\Http\Requests\Api\Comment\CommentUpdateRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends BaseApiController
{
    /**
     * Display a listing of comments for a specific complaint.
     */
    public function index(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewComments($request->user(), $complaint)) {
                return $this->errorResponse(
                    '댓글을 조회할 권한이 없습니다.',
                    403
                );
            }

            $query = $complaint->comments()
                ->with(['user', 'replies.user'])
                ->whereNull('parent_id'); // 최상위 댓글만 조회

            // 내부 댓글 필터링 (권한이 있는 사용자만)
            if (!$this->canViewInternalComments($request->user(), $complaint)) {
                $query->where('is_internal', false);
            }

            // 정렬
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // 페이지네이션
            $perPage = min($request->input('per_page', 20), 100);
            $comments = $query->paginate($perPage);

            return $this->paginatedResourceResponse(
                CommentResource::collection($comments),
                '댓글 목록을 조회했습니다.'
            );

        } catch (\Exception $e) {
            Log::error('댓글 목록 조회 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '댓글 목록 조회에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canCreateComment($request->user(), $complaint)) {
                return $this->errorResponse(
                    '댓글을 작성할 권한이 없습니다.',
                    403
                );
            }

            $validated = $request->validate([
                'content' => 'required|string|max:2000',
                'is_internal' => 'boolean',
                'parent_id' => 'nullable|exists:comments,id',
            ]);

            DB::beginTransaction();

            // 댓글 생성
            $comment = $complaint->comments()->create([
                'content' => $validated['content'],
                'is_internal' => $validated['is_internal'] ?? false,
                'parent_id' => $validated['parent_id'] ?? null,
                'user_id' => Auth::id(),
            ]);

            // 댓글 로드 (관계 포함)
            $comment->load(['user', 'replies.user']);

            // 민원 상태 업데이트 (필요시)
            $this->updateComplaintStatusOnComment($complaint, $comment);

            // 알림 발송 (향후 구현)
            // $this->sendCommentNotification($comment, $complaint);

            DB::commit();

            return $this->successResponse(
                new CommentResource($comment),
                '댓글이 성공적으로 작성되었습니다.',
                201
            );

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('댓글 작성 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '댓글 작성에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Display the specified comment.
     */
    public function show(Request $request, Comment $comment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewComment($request->user(), $comment)) {
                return $this->errorResponse(
                    '댓글을 조회할 권한이 없습니다.',
                    403
                );
            }

            $comment->load(['user', 'replies.user', 'complaint']);

            return $this->successResponse(
                new CommentResource($comment),
                '댓글을 조회했습니다.'
            );

        } catch (\Exception $e) {
            Log::error('댓글 조회 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '댓글 조회에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canUpdateComment($request->user(), $comment)) {
                return $this->errorResponse(
                    '댓글을 수정할 권한이 없습니다.',
                    403
                );
            }

            $validated = $request->validate([
                'content' => 'required|string|max:2000',
                'is_internal' => 'boolean',
            ]);

            DB::beginTransaction();

            // 댓글 업데이트
            $comment->update([
                'content' => $validated['content'],
                'is_internal' => $validated['is_internal'] ?? $comment->is_internal,
                'updated_at' => now(),
            ]);

            // 댓글 로드 (관계 포함)
            $comment->load(['user', 'replies.user']);

            DB::commit();

            return $this->successResponse(
                new CommentResource($comment),
                '댓글이 성공적으로 수정되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('댓글 수정 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '댓글 수정에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canDeleteComment($request->user(), $comment)) {
                return $this->errorResponse(
                    '댓글을 삭제할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            // 답글이 있는 경우 소프트 삭제
            if ($comment->replies()->count() > 0) {
                $comment->update([
                    'content' => '삭제된 댓글입니다.',
                    'is_deleted' => true,
                    'deleted_at' => now(),
                ]);
            } else {
                // 답글이 없는 경우 완전 삭제
                $comment->delete();
            }

            DB::commit();

            return $this->successResponse(
                null,
                '댓글이 성공적으로 삭제되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('댓글 삭제 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '댓글 삭제에 실패했습니다.',
                500
            );
        }
    }

    /**
     * Get recent comments for real-time updates
     */
    public function recent(Request $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewComments($request->user(), $complaint)) {
                return $this->errorResponse(
                    '댓글을 조회할 권한이 없습니다.',
                    403
                );
            }

            $since = $request->input('since', now()->subMinutes(5));
            
            $query = $complaint->comments()
                ->with(['user'])
                ->where('created_at', '>=', $since)
                ->orderBy('created_at', 'desc');

            // 내부 댓글 필터링
            if (!$this->canViewInternalComments($request->user(), $complaint)) {
                $query->where('is_internal', false);
            }

            $comments = $query->get();

            return $this->successResponse(
                CommentResource::collection($comments),
                '최근 댓글을 조회했습니다.'
            );

        } catch (\Exception $e) {
            Log::error('최근 댓글 조회 실패: ' . $e->getMessage());
            return $this->errorResponse(
                '최근 댓글 조회에 실패했습니다.',
                500
            );
        }
    }

    /**
     * 댓글 조회 권한 확인
     */
    private function canViewComments($user, $complaint): bool
    {
        // 관리자는 모든 댓글 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 민원인은 자신의 민원 댓글 조회 가능
        if ($complaint->complainant_id === $user->id) {
            return true;
        }

        // 담당자는 할당된 민원 댓글 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 관련 민원 댓글 조회 가능
        if ($user->hasRole(['teacher', 'staff', 'security_staff', 'ops_staff'])) {
            return true;
        }

        return false;
    }

    /**
     * 내부 댓글 조회 권한 확인
     */
    private function canViewInternalComments($user, $complaint): bool
    {
        // 관리자는 모든 내부 댓글 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당된 민원의 내부 댓글 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 내부 댓글 조회 가능
        if ($user->hasRole(['teacher', 'staff', 'security_staff', 'ops_staff'])) {
            return true;
        }

        return false;
    }

    /**
     * 댓글 작성 권한 확인
     */
    private function canCreateComment($user, $complaint): bool
    {
        // 관리자는 모든 댓글 작성 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 민원인은 자신의 민원에 댓글 작성 가능
        if ($complaint->complainant_id === $user->id) {
            return true;
        }

        // 담당자는 할당된 민원에 댓글 작성 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 관련 민원에 댓글 작성 가능
        if ($user->hasRole(['teacher', 'staff', 'security_staff', 'ops_staff'])) {
            return true;
        }

        return false;
    }

    /**
     * 댓글 조회 권한 확인 (개별)
     */
    private function canViewComment($user, $comment): bool
    {
        // 내부 댓글 확인
        if ($comment->is_internal && !$this->canViewInternalComments($user, $comment->complaint)) {
            return false;
        }

        return $this->canViewComments($user, $comment->complaint);
    }

    /**
     * 댓글 수정 권한 확인
     */
    private function canUpdateComment($user, $comment): bool
    {
        // 관리자는 모든 댓글 수정 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 자신의 댓글 수정 가능
        if ($comment->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 댓글 삭제 권한 확인
     */
    private function canDeleteComment($user, $comment): bool
    {
        // 관리자는 모든 댓글 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 자신의 댓글 삭제 가능
        if ($comment->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 댓글 작성 시 민원 상태 업데이트
     */
    private function updateComplaintStatusOnComment($complaint, $comment): void
    {
        // 민원이 대기 상태이고 담당자가 댓글을 작성한 경우 처리 중으로 변경
        if ($complaint->status === 'pending' && 
            $complaint->assigned_to === $comment->user_id) {
            $complaint->update(['status' => 'in_progress']);
            
            // 상태 로그 생성
            $complaint->statusLogs()->create([
                'status' => 'in_progress',
                'comment' => '담당자가 댓글을 작성하여 처리가 시작되었습니다.',
                'user_id' => $comment->user_id,
            ]);
        }
    }
}
