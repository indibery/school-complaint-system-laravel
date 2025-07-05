<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Department\DepartmentStoreRequest;
use App\Http\Requests\Api\Department\DepartmentUpdateRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends BaseApiController
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Department::with(['head', 'members']);

            // 활성/비활성 필터
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // 검색
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 정렬
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // 페이지네이션 또는 전체 조회
            if ($request->boolean('paginate', true)) {
                $perPage = min($request->input('per_page', 20), 100);
                $departments = $query->paginate($perPage);
                
                return $this->paginatedResourceResponse(
                    DepartmentResource::collection($departments),
                    '부서 목록을 조회했습니다.'
                );
            } else {
                $departments = $query->get();
                
                return $this->successResponse(
                    DepartmentResource::collection($departments),
                    '부서 목록을 조회했습니다.'
                );
            }

        } catch (\Exception $e) {
            return $this->errorResponse(
                '부서 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created department.
     */
    public function store(DepartmentStoreRequest $request): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서를 생성할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            $department = Department::create($data);

            // 부서장 지정
            if (isset($data['head_id'])) {
                $head = User::find($data['head_id']);
                if ($head) {
                    $head->update(['department_id' => $department->id]);
                }
            }

            DB::commit();

            return $this->createdResponse(
                new DepartmentResource($department->load(['head', 'members'])),
                '부서가 성공적으로 생성되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서 생성 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified department.
     */
    public function show(Request $request, Department $department): JsonResponse
    {
        try {
            // 관계 데이터 로드
            $department->load(['head', 'members', 'complaints']);

            // 통계 정보 포함 여부
            if ($request->boolean('with_stats')) {
                $department->loadCount([
                    'members',
                    'complaints',
                    'complaints as active_complaints_count' => function ($query) {
                        $query->whereNotIn('status', ['closed', 'cancelled']);
                    }
                ]);
            }

            return $this->successResponse(
                new DepartmentResource($department),
                '부서 정보를 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '부서 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified department.
     */
    public function update(DepartmentUpdateRequest $request, Department $department): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서를 수정할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            
            // 기존 부서장 정보 저장
            $oldHeadId = $department->head_id;
            
            $department->update($data);

            // 부서장 변경 처리
            if (isset($data['head_id']) && $data['head_id'] != $oldHeadId) {
                // 기존 부서장 해제
                if ($oldHeadId) {
                    $oldHead = User::find($oldHeadId);
                    if ($oldHead && $oldHead->department_id == $department->id) {
                        $oldHead->update(['department_id' => null]);
                    }
                }

                // 새 부서장 지정
                if ($data['head_id']) {
                    $newHead = User::find($data['head_id']);
                    if ($newHead) {
                        $newHead->update(['department_id' => $department->id]);
                    }
                }
            }

            DB::commit();

            return $this->updatedResponse(
                new DepartmentResource($department->load(['head', 'members'])),
                '부서가 성공적으로 수정되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Request $request, Department $department): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서를 삭제할 권한이 없습니다.',
                    403
                );
            }

            // 부서원 존재 확인
            if ($department->members()->exists()) {
                return $this->errorResponse(
                    '부서원이 있는 부서는 삭제할 수 없습니다.',
                    422
                );
            }

            // 관련 민원 존재 확인
            if ($department->complaints()->exists()) {
                return $this->errorResponse(
                    '관련 민원이 있는 부서는 삭제할 수 없습니다.',
                    422
                );
            }

            DB::beginTransaction();

            $department->delete();

            DB::commit();

            return $this->deletedResponse('부서가 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get department members.
     */
    public function getMembers(Request $request, Department $department): JsonResponse
    {
        try {
            $query = $department->members();

            // 역할별 필터
            if ($request->has('role')) {
                $query->where('role', $request->input('role'));
            }

            // 상태별 필터
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // 정렬
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $members = $query->get();

            return $this->successResponse(
                $members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->role,
                        'status' => $member->status,
                        'is_head' => $member->id === $member->department->head_id,
                        'joined_at' => $member->created_at->toDateString(),
                    ];
                }),
                '부서원 목록을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '부서원 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Add member to department.
     */
    public function addMember(Request $request, Department $department): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서원을 추가할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $user = User::find($request->input('user_id'));
            
            // 이미 다른 부서에 속한 경우 확인
            if ($user->department_id && $user->department_id != $department->id) {
                return $this->errorResponse(
                    '이미 다른 부서에 소속된 사용자입니다.',
                    422
                );
            }

            $user->update(['department_id' => $department->id]);

            DB::commit();

            return $this->successResponse(
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $department->name,
                ],
                '부서원이 성공적으로 추가되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서원 추가 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove member from department.
     */
    public function removeMember(Request $request, Department $department, User $user): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서원을 제거할 권한이 없습니다.',
                    403
                );
            }

            // 부서원 확인
            if ($user->department_id != $department->id) {
                return $this->errorResponse(
                    '해당 사용자는 이 부서에 소속되지 않습니다.',
                    422
                );
            }

            // 부서장인 경우 확인
            if ($department->head_id == $user->id) {
                return $this->errorResponse(
                    '부서장은 부서에서 제거할 수 없습니다. 먼저 부서장을 변경해주세요.',
                    422
                );
            }

            DB::beginTransaction();

            $user->update(['department_id' => null]);

            DB::commit();

            return $this->successResponse(
                null,
                '부서원이 성공적으로 제거되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서원 제거 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get department statistics.
     */
    public function getStatistics(Request $request, Department $department): JsonResponse
    {
        try {
            $stats = [
                'members_count' => $department->members()->count(),
                'complaints_count' => $department->complaints()->count(),
                'active_complaints_count' => $department->complaints()
                    ->whereNotIn('status', ['closed', 'cancelled'])
                    ->count(),
                'completed_complaints_count' => $department->complaints()
                    ->where('status', 'closed')
                    ->count(),
                'pending_complaints_count' => $department->complaints()
                    ->where('status', 'pending')
                    ->count(),
                'in_progress_complaints_count' => $department->complaints()
                    ->where('status', 'in_progress')
                    ->count(),
                'avg_resolution_time' => $this->getAverageResolutionTime($department),
                'monthly_complaints' => $this->getMonthlyComplaintsData($department),
                'complaint_by_category' => $this->getComplaintsByCategory($department),
                'top_performers' => $this->getTopPerformers($department),
            ];

            return $this->successResponse($stats, '부서 통계를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '부서 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get all departments for select dropdown.
     */
    public function getSelectOptions(Request $request): JsonResponse
    {
        try {
            $departments = Department::where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($department) {
                    return [
                        'value' => $department->id,
                        'label' => $department->name,
                        'code' => $department->code,
                        'head' => $department->head?->name,
                    ];
                });

            return $this->successResponse(
                $departments,
                '부서 선택 옵션을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '부서 선택 옵션 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Toggle department status.
     */
    public function toggleStatus(Request $request, Department $department): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '부서 상태를 변경할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $newStatus = $department->status === 'active' ? 'inactive' : 'active';
            $department->update(['status' => $newStatus]);

            DB::commit();

            return $this->updatedResponse(
                new DepartmentResource($department),
                "부서가 성공적으로 {$newStatus}되었습니다."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '부서 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get average resolution time for department.
     */
    private function getAverageResolutionTime(Department $department): ?float
    {
        $resolvedComplaints = $department->complaints()
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolvedComplaints->isEmpty()) {
            return null;
        }

        $totalDays = $resolvedComplaints->sum(function ($complaint) {
            return $complaint->created_at->diffInDays($complaint->resolved_at);
        });

        return round($totalDays / $resolvedComplaints->count(), 1);
    }

    /**
     * Get monthly complaints data for department.
     */
    private function getMonthlyComplaintsData(Department $department): array
    {
        $monthlyData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = $department->complaints()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $monthlyData[] = [
                'month' => $date->format('Y-m'),
                'count' => $count,
            ];
        }

        return $monthlyData;
    }

    /**
     * Get complaints by category for department.
     */
    private function getComplaintsByCategory(Department $department): array
    {
        return $department->complaints()
            ->join('categories', 'complaints.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get top performers in department.
     */
    private function getTopPerformers(Department $department): array
    {
        return $department->members()
            ->join('complaints', 'users.id', '=', 'complaints.assigned_to')
            ->where('complaints.status', 'closed')
            ->select('users.name', DB::raw('COUNT(*) as resolved_count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved_count')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
