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
                ->with(['author', 'replies.author'])
                ->whereNull('parent_id'); // 최상위 댓글만 조회

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
            return $this->errorResponse(
                '댓글 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created comment.
     */
    public function store(CommentStoreRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canCreateComment($request->user(), $complaint)) {
                return $this->errorResponse(
                    '댓글을 작성할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['complaint_id'] = $complaint->id;
            $data['author_id'] = $request->user()->id;

            $comment = Comment::create($data);

            // 댓글 알림 처리
            $this->sendCommentNotification($comment, 'created');

            DB::commit();

            return $this->createdResponse(
                new CommentResource($comment->load('author')),
                '댓글이 성공적으로 등록되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '댓글 등록 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
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

            $comment->load(['author', 'replies.author']);

            return $this->successResponse(
                new CommentResource($comment),
                '댓글을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '댓글 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified comment.
     */
    public function update(CommentUpdateRequest $request, Comment $comment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canUpdateComment($request->user(), $comment)) {
                return $this->errorResponse(
                    '댓글을 수정할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['is_edited'] = true;
            $data['edited_at'] = now();

            $comment->update($data);

            // 댓글 알림 처리
            $this->sendCommentNotification($comment, 'updated');

            DB::commit();

            return $this->updatedResponse(
                new CommentResource($comment->load('author')),
                '댓글이 성공적으로 수정되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '댓글 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
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

            // 대댓글이 있는 경우 소프트 삭제, 없는 경우 완전 삭제
            if ($comment->replies()->exists()) {
                $comment->update([
                    'content' => '삭제된 댓글입니다.',
                    'is_deleted' => true,
                    'deleted_at' => now()
                ]);
            } else {
                $comment->delete();
            }

            DB::commit();

            return $this->deletedResponse('댓글이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '댓글 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get replies for a specific comment.
     */
    public function replies(Request $request, Comment $comment): JsonResponse
    {
        try {
            // 권한 체크
            if (!$this->canViewComment($request->user(), $comment)) {
                return $this->errorResponse(
                    '댓글을 조회할 권한이 없습니다.',
                    403
                );
            }

            $replies = $comment->replies()
                ->with('author')
                ->orderBy('created_at', 'asc')
                ->get();

            return $this->successResponse(
                CommentResource::collection($replies),
                '대댓글 목록을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '대댓글 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Bulk delete comments.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'comment_ids' => 'required|array|min:1|max:100',
                'comment_ids.*' => 'integer|exists:comments,id',
            ]);

            // 권한 체크 (관리자만 가능)
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '댓글을 대량 삭제할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $commentIds = $request->input('comment_ids');
            $deletedCount = 0;

            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                if ($comment) {
                    if ($comment->replies()->exists()) {
                        $comment->update([
                            'content' => '삭제된 댓글입니다.',
                            'is_deleted' => true,
                            'deleted_at' => now()
                        ]);
                    } else {
                        $comment->delete();
                    }
                    $deletedCount++;
                }
            }

            DB::commit();

            return $this->successResponse(
                ['deleted_count' => $deletedCount],
                "{$deletedCount}개의 댓글이 성공적으로 삭제되었습니다."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '댓글 대량 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get comment statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '댓글 통계를 조회할 권한이 없습니다.',
                    403
                );
            }

            $stats = [
                'total_comments' => Comment::count(),
                'comments_today' => Comment::whereDate('created_at', today())->count(),
                'comments_this_week' => Comment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'comments_this_month' => Comment::whereMonth('created_at', now()->month)->count(),
                'active_comments' => Comment::where('is_deleted', false)->count(),
                'deleted_comments' => Comment::where('is_deleted', true)->count(),
                'avg_comments_per_complaint' => Comment::count() / max(Complaint::count(), 1),
                'top_commenters' => Comment::join('users', 'comments.author_id', '=', 'users.id')
                    ->select('users.name', DB::raw('COUNT(comments.id) as comment_count'))
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('comment_count')
                    ->limit(10)
                    ->get(),
            ];

            return $this->successResponse($stats, '댓글 통계를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '댓글 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Check if user can view comments.
     */
    private function canViewComments($user, Complaint $complaint): bool
    {
        return $this->canViewComplaint($user, $complaint);
    }

    /**
     * Check if user can view a specific comment.
     */
    private function canViewComment($user, Comment $comment): bool
    {
        return $this->canViewComplaint($user, $comment->complaint);
    }

    /**
     * Check if user can create comment.
     */
    private function canCreateComment($user, Complaint $complaint): bool
    {
        // 관리자는 모든 댓글 작성 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 민원 제기자는 자신의 민원에 댓글 작성 가능
        if ($complaint->created_by === $user->id) {
            return true;
        }

        // 담당자는 담당 민원에 댓글 작성 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 교직원은 모든 댓글 작성 가능
        if ($user->hasRole(['teacher', 'staff', 'department_head', 'vice_principal', 'principal'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update comment.
     */
    private function canUpdateComment($user, Comment $comment): bool
    {
        // 관리자는 모든 댓글 수정 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 작성자는 자신의 댓글 수정 가능 (24시간 이내)
        if ($comment->author_id === $user->id) {
            return $comment->created_at->diffInHours(now()) <= 24;
        }

        return false;
    }

    /**
     * Check if user can delete comment.
     */
    private function canDeleteComment($user, Comment $comment): bool
    {
        // 관리자는 모든 댓글 삭제 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 작성자는 자신의 댓글 삭제 가능
        if ($comment->author_id === $user->id) {
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

    /**
     * Send comment notification.
     */
    private function sendCommentNotification(Comment $comment, string $action): void
    {
        // 알림 로직 구현
        // 민원 제기자, 담당자, 관련자들에게 알림 발송
    }
}
