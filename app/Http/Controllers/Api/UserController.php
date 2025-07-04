<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\User\UserStoreRequest;
use App\Http\Requests\Api\User\UserUpdateRequest;
use App\Http\Requests\Api\User\UserStatusRequest;
use App\Http\Requests\Api\User\UserIndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder;

class UserController extends BaseApiController
{
    /**
     * Display a listing of the users.
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        try {
            $query = User::with(['department']);
            
            // 권한 체크 - 관리자가 아닌 경우 자신의 정보만 조회
            if (!$request->user()->hasRole('admin')) {
                $query->where('id', $request->user()->id);
            }
            
            // 검색 조건 적용
            $this->applyFilters($query, $request);
            
            // 정렬 적용
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // 페이지네이션
            $perPage = min($request->input('per_page', 15), $this->maxPerPage);
            $users = $query->paginate($perPage);
            
            return $this->paginatedResourceResponse(
                UserResource::collection($users),
                '사용자 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '사용자 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->sanitized();
            
            // 비밀번호 해시화
            $data['password'] = Hash::make($data['password']);
            
            // 사용자 생성
            $user = User::create($data);
            
            // 역할 할당
            $user->assignRole($data['role']);
            
            DB::commit();
            
            return $this->createdResponse(
                new UserResource($user->load(['department', 'roles'])),
                '사용자가 성공적으로 생성되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '사용자 생성 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        try {
            // 권한 체크 - 관리자가 아닌 경우 자신의 정보만 조회 가능
            if (!$request->user()->hasRole('admin') && $request->user()->id !== $user->id) {
                return $this->errorResponse(
                    '해당 사용자의 정보를 조회할 권한이 없습니다.',
                    403
                );
            }
            
            $user->load(['department', 'roles']);
            
            return $this->successResponse(
                new UserResource($user),
                '사용자 정보를 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '사용자 정보 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->sanitized();
            
            // 비밀번호가 있는 경우 해시화
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            // 사용자 정보 업데이트
            $user->update($data);
            
            // 역할 변경 (관리자만 가능)
            if (isset($data['role']) && $request->user()->hasRole('admin')) {
                $user->syncRoles([$data['role']]);
            }
            
            DB::commit();
            
            return $this->updatedResponse(
                new UserResource($user->load(['department', 'roles'])),
                '사용자 정보가 성공적으로 수정되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '사용자 정보 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            // 권한 체크 - 관리자만 삭제 가능
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '사용자를 삭제할 권한이 없습니다.',
                    403
                );
            }
            
            // 자기 자신 삭제 방지
            if ($request->user()->id === $user->id) {
                return $this->errorResponse(
                    '자기 자신을 삭제할 수 없습니다.',
                    400
                );
            }
            
            DB::beginTransaction();
            
            // 소프트 삭제 실행
            $user->delete();
            
            DB::commit();
            
            return $this->deletedResponse(
                '사용자가 성공적으로 삭제되었습니다.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '사용자 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Apply filters to the user query.
     */
    private function applyFilters(Builder $query, UserIndexRequest $request): void
    {
        // 검색어 필터
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        // 역할 필터
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        // 부서 필터
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // 학년 필터
        if ($request->filled('grade')) {
            $query->where('grade', $request->input('grade'));
        }

        // 반 필터
        if ($request->filled('class_number')) {
            $query->where('class_number', $request->input('class_number'));
        }

        // 활성 상태 필터
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // 이메일 보유 여부 필터
        if ($request->filled('has_email')) {
            if ($request->input('has_email')) {
                $query->whereNotNull('email');
            } else {
                $query->whereNull('email');
            }
        }

        // 전화번호 보유 여부 필터
        if ($request->filled('has_phone')) {
            if ($request->input('has_phone')) {
                $query->whereNotNull('phone');
            } else {
                $query->whereNull('phone');
            }
        }

        // 생성일 필터
        if ($request->filled('created_after')) {
            $query->where('created_at', '>=', $request->input('created_after'));
        }

        if ($request->filled('created_before')) {
            $query->where('created_at', '<=', $request->input('created_before'));
        }

        // 수정일 필터
        if ($request->filled('updated_after')) {
            $query->where('updated_at', '>=', $request->input('updated_after'));
        }

        if ($request->filled('updated_before')) {
            $query->where('updated_at', '<=', $request->input('updated_before'));
        }
    }

    /**
     * Get available sort columns.
     */
    private function getAvailableSortColumns(): array
    {
        return [
            'id', 'name', 'email', 'employee_id', 'student_id',
            'grade', 'class_number', 'is_active', 'created_at', 'updated_at'
        ];
    }

    /**
     * Validate sort parameters.
     */
    private function validateSortParameters(string $sortBy, string $sortOrder): array
    {
        $availableColumns = $this->getAvailableSortColumns();
        $sortBy = in_array($sortBy, $availableColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        return [$sortBy, $sortOrder];
    }
}

    /**
     * Update user status (activate/deactivate).
     */
    public function updateStatus(UserStatusRequest $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // 사용자 상태 업데이트
            $user->update([
                'is_active' => $data['is_active'],
                'status_changed_at' => now(),
                'status_changed_by' => $request->user()->id,
            ]);
            
            // 상태 변경 로그 기록 (메타데이터에 저장)
            $statusHistory = $user->metadata['status_history'] ?? [];
            $statusHistory[] = [
                'changed_at' => now()->toISOString(),
                'changed_by' => $request->user()->id,
                'changed_by_name' => $request->user()->name,
                'from_status' => !$data['is_active'],
                'to_status' => $data['is_active'],
                'reason' => $data['reason'] ?? null,
            ];
            
            $metadata = $user->metadata ?? [];
            $metadata['status_history'] = $statusHistory;
            $user->update(['metadata' => $metadata]);
            
            DB::commit();
            
            $message = $data['is_active'] ? 
                '사용자가 활성화되었습니다.' : 
                '사용자가 비활성화되었습니다.';
            
            return $this->updatedResponse(
                new UserResource($user->load(['department', 'roles'])),
                $message
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->errorResponse(
                '사용자 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole(Request $request, string $role): JsonResponse
    {
        try {
            // 유효한 역할인지 확인
            $validRoles = ['admin', 'teacher', 'parent', 'staff', 'student'];
            if (!in_array($role, $validRoles)) {
                return $this->errorResponse(
                    '유효하지 않은 역할입니다.',
                    400
                );
            }
            
            $query = User::with(['department', 'roles'])
                ->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            
            // 관리자가 아닌 경우 활성 사용자만 조회
            if (!$request->user()->hasRole('admin')) {
                $query->where('is_active', true);
            }
            
            // 추가 필터링 적용
            $this->applyRoleSpecificFilters($query, $request, $role);
            
            // 정렬
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            [$sortBy, $sortOrder] = $this->validateSortParameters($sortBy, $sortOrder);
            $query->orderBy($sortBy, $sortOrder);
            
            // 페이지네이션
            $perPage = min($request->input('per_page', 15), $this->maxPerPage);
            $users = $query->paginate($perPage);
            
            $roleNames = [
                'admin' => '관리자',
                'teacher' => '교사',
                'parent' => '학부모',
                'staff' => '직원',
                'student' => '학생'
            ];
            
            return $this->paginatedResourceResponse(
                UserResource::collection($users),
                "{$roleNames[$role]} 목록을 조회했습니다."
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '역할별 사용자 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get teachers list.
     */
    public function getTeachers(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request, 'teacher');
    }

    /**
     * Get parents list.
     */
    public function getParents(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request, 'parent');
    }

    /**
     * Get staff list.
     */
    public function getStaff(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request, 'staff');
    }

    /**
     * Get students list.
     */
    public function getStudents(Request $request): JsonResponse
    {
        return $this->getUsersByRole($request, 'student');
    }

    /**
     * Get homeroom teachers.
     */
    public function getHomeroomTeachers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['department', 'roles'])
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'teacher');
                })
                ->where('is_active', true)
                ->whereJsonContains('metadata->homeroom_teacher', true);
            
            // 학년별 필터링
            if ($request->filled('grade')) {
                $query->where('grade', $request->input('grade'));
            }
            
            // 반별 필터링
            if ($request->filled('class_number')) {
                $query->where('class_number', $request->input('class_number'));
            }
            
            // 부서별 필터링
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->input('department_id'));
            }
            
            $query->orderBy('grade')->orderBy('class_number');
            
            $perPage = min($request->input('per_page', 15), $this->maxPerPage);
            $teachers = $query->paginate($perPage);
            
            return $this->paginatedResourceResponse(
                UserResource::collection($teachers),
                '담임교사 목록을 조회했습니다.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '담임교사 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get students by grade and class.
     */
    public function getStudentsByClass(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'grade' => 'required|integer|min:1|max:12',
                'class_number' => 'required|integer|min:1|max:20'
            ]);
            
            $query = User::with(['department', 'roles'])
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'student');
                })
                ->where('is_active', true)
                ->where('grade', $request->input('grade'))
                ->where('class_number', $request->input('class_number'));
            
            $query->orderBy('student_id');
            
            $perPage = min($request->input('per_page', 50), $this->maxPerPage);
            $students = $query->paginate($perPage);
            
            return $this->paginatedResourceResponse(
                UserResource::collection($students),
                "{$request->input('grade')}학년 {$request->input('class_number')}반 학생 목록을 조회했습니다."
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse(
                '학급별 학생 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Apply role-specific filters to the query.
     */
    private function applyRoleSpecificFilters(Builder $query, Request $request, string $role): void
    {
        // 공통 필터링
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // 역할별 특화 필터링
        switch ($role) {
            case 'teacher':
                $this->applyTeacherFilters($query, $request);
                break;
            case 'student':
                $this->applyStudentFilters($query, $request);
                break;
            case 'staff':
                $this->applyStaffFilters($query, $request);
                break;
            case 'parent':
                $this->applyParentFilters($query, $request);
                break;
        }
    }

    /**
     * Apply teacher-specific filters.
     */
    private function applyTeacherFilters(Builder $query, Request $request): void
    {
        if ($request->filled('subject')) {
            $query->whereJsonContains('metadata->subject', $request->input('subject'));
        }

        if ($request->filled('homeroom_teacher')) {
            $query->whereJsonContains('metadata->homeroom_teacher', $request->boolean('homeroom_teacher'));
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->input('grade'));
        }

        if ($request->filled('class_number')) {
            $query->where('class_number', $request->input('class_number'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', 'like', "%{$request->input('employee_id')}%");
        }
    }

    /**
     * Apply student-specific filters.
     */
    private function applyStudentFilters(Builder $query, Request $request): void
    {
        if ($request->filled('grade')) {
            $query->where('grade', $request->input('grade'));
        }

        if ($request->filled('class_number')) {
            $query->where('class_number', $request->input('class_number'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', 'like', "%{$request->input('student_id')}%");
        }
    }

    /**
     * Apply staff-specific filters.
     */
    private function applyStaffFilters(Builder $query, Request $request): void
    {
        if ($request->filled('employee_id')) {
            $query->where('employee_id', 'like', "%{$request->input('employee_id')}%");
        }
    }

    /**
     * Apply parent-specific filters.
     */
    private function applyParentFilters(Builder $query, Request $request): void
    {
        // 학부모의 경우 자녀 관련 필터링 추가 가능
        if ($request->filled('child_grade')) {
            // 자녀의 학년으로 필터링 (관계 테이블이 있다면)
            // $query->whereHas('children', function ($q) use ($request) {
            //     $q->where('grade', $request->input('child_grade'));
            // });
        }
    }

    /**
     * Get user statistics by role.
     */
    public function getUserStatistics(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '통계 조회 권한이 없습니다.',
                    403
                );
            }

            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'roles' => [
                    'admin' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'admin');
                    })->count(),
                    'teacher' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'teacher');
                    })->count(),
                    'student' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'student');
                    })->count(),
                    'parent' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'parent');
                    })->count(),
                    'staff' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'staff');
                    })->count(),
                ],
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'homeroom_teachers' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'teacher');
                })->whereJsonContains('metadata->homeroom_teacher', true)->count(),
            ];

            return $this->successResponse(
                $stats,
                '사용자 통계를 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '사용자 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Advanced search for users.
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'nullable|string|max:255',
                'filters' => 'nullable|array',
                'filters.roles' => 'nullable|array',
                'filters.departments' => 'nullable|array',
                'filters.grades' => 'nullable|array',
                'filters.classes' => 'nullable|array',
                'filters.status' => 'nullable|string|in:active,inactive,all',
                'filters.date_range' => 'nullable|array',
                'filters.date_range.start' => 'nullable|date',
                'filters.date_range.end' => 'nullable|date|after_or_equal:filters.date_range.start',
                'filters.metadata' => 'nullable|array',
                'sort' => 'nullable|array',
                'sort.field' => 'nullable|string|in:name,email,created_at,updated_at,grade,class_number',
                'sort.direction' => 'nullable|string|in:asc,desc',
                'pagination' => 'nullable|array',
                'pagination.page' => 'nullable|integer|min:1',
                'pagination.per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = User::with(['department', 'roles']);

            // 권한 체크
            if (!$request->user()->hasRole('admin')) {
                $query->where('id', $request->user()->id);
            }

            // 기본 검색어
            if ($request->filled('query')) {
                $searchTerm = $request->input('query');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('employee_id', 'like', "%{$searchTerm}%")
                      ->orWhere('student_id', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            }

            // 고급 필터링
            $this->applyAdvancedFilters($query, $request->input('filters', []));

            // 정렬
            $sort = $request->input('sort', []);
            $sortField = $sort['field'] ?? 'created_at';
            $sortDirection = $sort['direction'] ?? 'desc';
            $query->orderBy($sortField, $sortDirection);

            // 페이지네이션
            $pagination = $request->input('pagination', []);
            $page = $pagination['page'] ?? 1;
            $perPage = min($pagination['per_page'] ?? 15, 100);
            
            $users = $query->paginate($perPage, ['*'], 'page', $page);

            // 검색 결과 통계
            $stats = [
                'total_results' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total_pages' => $users->lastPage(),
                'filters_applied' => $this->getAppliedFiltersCount($request),
            ];

            return $this->successResponse([
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'has_more_pages' => $users->hasMorePages(),
                    'links' => [
                        'first' => $users->url(1),
                        'last' => $users->url($users->lastPage()),
                        'prev' => $users->previousPageUrl(),
                        'next' => $users->nextPageUrl(),
                    ]
                ],
                'stats' => $stats
            ], '고급 검색 결과를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '고급 검색 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get search suggestions.
     */
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:50',
                'type' => 'nullable|string|in:name,email,employee_id,student_id',
                'limit' => 'nullable|integer|min:1|max:20'
            ]);

            $query = $request->input('query');
            $type = $request->input('type', 'name');
            $limit = $request->input('limit', 10);

            $suggestions = [];

            // 권한 체크
            if (!$request->user()->hasRole('admin')) {
                return $this->successResponse([], '검색 제안을 조회했습니다.');
            }

            switch ($type) {
                case 'name':
                    $suggestions = User::select('id', 'name')
                        ->where('name', 'like', "%{$query}%")
                        ->where('is_active', true)
                        ->limit($limit)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'text' => $user->name,
                                'type' => 'name'
                            ];
                        });
                    break;

                case 'email':
                    $suggestions = User::select('id', 'email')
                        ->where('email', 'like', "%{$query}%")
                        ->whereNotNull('email')
                        ->where('is_active', true)
                        ->limit($limit)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'text' => $user->email,
                                'type' => 'email'
                            ];
                        });
                    break;

                case 'employee_id':
                    $suggestions = User::select('id', 'employee_id', 'name')
                        ->where('employee_id', 'like', "%{$query}%")
                        ->whereNotNull('employee_id')
                        ->where('is_active', true)
                        ->limit($limit)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'text' => $user->employee_id,
                                'label' => $user->name,
                                'type' => 'employee_id'
                            ];
                        });
                    break;

                case 'student_id':
                    $suggestions = User::select('id', 'student_id', 'name')
                        ->where('student_id', 'like', "%{$query}%")
                        ->whereNotNull('student_id')
                        ->where('is_active', true)
                        ->limit($limit)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'text' => $user->student_id,
                                'label' => $user->name,
                                'type' => 'student_id'
                            ];
                        });
                    break;
            }

            return $this->successResponse(
                $suggestions,
                '검색 제안을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '검색 제안 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get filter options for advanced search.
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '필터 옵션을 조회할 권한이 없습니다.',
                    403
                );
            }

            $options = [
                'roles' => [
                    ['value' => 'admin', 'label' => '관리자'],
                    ['value' => 'teacher', 'label' => '교사'],
                    ['value' => 'student', 'label' => '학생'],
                    ['value' => 'parent', 'label' => '학부모'],
                    ['value' => 'staff', 'label' => '직원'],
                ],
                'departments' => \App\Models\Department::select('id', 'name')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->map(function ($dept) {
                        return [
                            'value' => $dept->id,
                            'label' => $dept->name
                        ];
                    }),
                'grades' => collect(range(1, 12))->map(function ($grade) {
                    return [
                        'value' => $grade,
                        'label' => "{$grade}학년"
                    ];
                }),
                'classes' => collect(range(1, 20))->map(function ($class) {
                    return [
                        'value' => $class,
                        'label' => "{$class}반"
                    ];
                }),
                'status_options' => [
                    ['value' => 'active', 'label' => '활성'],
                    ['value' => 'inactive', 'label' => '비활성'],
                    ['value' => 'all', 'label' => '전체'],
                ],
                'sort_options' => [
                    ['value' => 'name', 'label' => '이름'],
                    ['value' => 'email', 'label' => '이메일'],
                    ['value' => 'created_at', 'label' => '생성일'],
                    ['value' => 'updated_at', 'label' => '수정일'],
                    ['value' => 'grade', 'label' => '학년'],
                    ['value' => 'class_number', 'label' => '반'],
                ],
                'sort_directions' => [
                    ['value' => 'asc', 'label' => '오름차순'],
                    ['value' => 'desc', 'label' => '내림차순'],
                ],
            ];

            return $this->successResponse(
                $options,
                '필터 옵션을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '필터 옵션 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Apply advanced filters to the query.
     */
    private function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // 역할 필터
        if (!empty($filters['roles'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['roles']);
            });
        }

        // 부서 필터
        if (!empty($filters['departments'])) {
            $query->whereIn('department_id', $filters['departments']);
        }

        // 학년 필터
        if (!empty($filters['grades'])) {
            $query->whereIn('grade', $filters['grades']);
        }

        // 반 필터
        if (!empty($filters['classes'])) {
            $query->whereIn('class_number', $filters['classes']);
        }

        // 상태 필터
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $isActive = $filters['status'] === 'active';
            $query->where('is_active', $isActive);
        }

        // 날짜 범위 필터
        if (!empty($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            if (!empty($dateRange['start'])) {
                $query->whereDate('created_at', '>=', $dateRange['start']);
            }
            if (!empty($dateRange['end'])) {
                $query->whereDate('created_at', '<=', $dateRange['end']);
            }
        }

        // 메타데이터 필터
        if (!empty($filters['metadata'])) {
            $this->applyMetadataFilters($query, $filters['metadata']);
        }
    }

    /**
     * Apply metadata filters to the query.
     */
    private function applyMetadataFilters(Builder $query, array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                switch ($key) {
                    case 'homeroom_teacher':
                        $query->whereJsonContains('metadata->homeroom_teacher', (bool) $value);
                        break;
                    case 'subject':
                        $query->whereJsonContains('metadata->subject', $value);
                        break;
                    case 'gender':
                        $query->whereJsonContains('metadata->gender', $value);
                        break;
                    case 'has_emergency_contact':
                        if ($value) {
                            $query->whereJsonContains('metadata->emergency_contact', function ($q) {
                                $q->whereNotNull('metadata->emergency_contact');
                            });
                        }
                        break;
                }
            }
        }
    }

    /**
     * Get count of applied filters.
     */
    private function getAppliedFiltersCount(Request $request): int
    {
        $count = 0;
        $filters = $request->input('filters', []);

        if ($request->filled('query')) {
            $count++;
        }

        if (!empty($filters['roles'])) {
            $count++;
        }

        if (!empty($filters['departments'])) {
            $count++;
        }

        if (!empty($filters['grades'])) {
            $count++;
        }

        if (!empty($filters['classes'])) {
            $count++;
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $count++;
        }

        if (!empty($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            if (!empty($dateRange['start']) || !empty($dateRange['end'])) {
                $count++;
            }
        }

        if (!empty($filters['metadata'])) {
            $count += count(array_filter($filters['metadata'], function ($value) {
                return $value !== null && $value !== '';
            }));
        }

        return $count;
    }

    /**
     * Export users to CSV.
     */
    public function exportUsers(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '사용자 내보내기 권한이 없습니다.',
                    403
                );
            }

            $request->validate([
                'format' => 'required|string|in:csv,xlsx',
                'filters' => 'nullable|array',
                'include_metadata' => 'nullable|boolean',
            ]);

            $query = User::with(['department', 'roles']);

            // 필터 적용
            if ($request->has('filters')) {
                $this->applyAdvancedFilters($query, $request->input('filters', []));
            }

            $users = $query->get();
            $format = $request->input('format', 'csv');
            $includeMetadata = $request->input('include_metadata', false);

            // 파일 생성을 위한 데이터 준비
            $exportData = $users->map(function ($user) use ($includeMetadata) {
                $data = [
                    'ID' => $user->id,
                    '이름' => $user->name,
                    '이메일' => $user->email,
                    '전화번호' => $user->phone,
                    '역할' => $user->roles->pluck('name')->implode(', '),
                    '부서' => $user->department?->name,
                    '직원번호' => $user->employee_id,
                    '학번' => $user->student_id,
                    '학년' => $user->grade,
                    '반' => $user->class_number,
                    '활성상태' => $user->is_active ? '활성' : '비활성',
                    '생성일' => $user->created_at?->format('Y-m-d H:i:s'),
                    '수정일' => $user->updated_at?->format('Y-m-d H:i:s'),
                ];

                if ($includeMetadata && $user->metadata) {
                    $data['성별'] = $user->metadata['gender'] ?? '';
                    $data['담당과목'] = $user->metadata['subject'] ?? '';
                    $data['담임교사'] = isset($user->metadata['homeroom_teacher']) && $user->metadata['homeroom_teacher'] ? '예' : '아니오';
                    $data['입사일'] = $user->metadata['hire_date'] ?? '';
                    $data['생년월일'] = $user->metadata['birth_date'] ?? '';
                    $data['주소'] = $user->metadata['address'] ?? '';
                    $data['비상연락처'] = $user->metadata['emergency_contact'] ?? '';
                }

                return $data;
            });

            // 임시 파일 생성 (실제 구현에서는 큐를 사용하는 것이 좋음)
            $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
            $filepath = storage_path('app/exports/' . $filename);

            // 디렉토리가 없으면 생성
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // CSV 파일 생성
            if ($format === 'csv') {
                $file = fopen($filepath, 'w');
                
                // UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
                fwrite($file, "\xEF\xBB\xBF");
                
                // 헤더 작성
                if ($exportData->isNotEmpty()) {
                    fputcsv($file, array_keys($exportData->first()));
                    
                    // 데이터 작성
                    foreach ($exportData as $row) {
                        fputcsv($file, $row);
                    }
                }
                
                fclose($file);
            }

            return $this->successResponse([
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => url('api/v1/users/download/' . $filename),
                'total_records' => $users->count(),
                'format' => $format,
                'created_at' => now()->toISOString(),
            ], '사용자 데이터 내보내기가 완료되었습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '사용자 내보내기 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get bulk operation options.
     */
    public function getBulkOptions(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->hasRole('admin')) {
                return $this->errorResponse(
                    '대량 작업 옵션을 조회할 권한이 없습니다.',
                    403
                );
            }

            $options = [
                'actions' => [
                    ['value' => 'activate', 'label' => '활성화', 'icon' => 'check'],
                    ['value' => 'deactivate', 'label' => '비활성화', 'icon' => 'x'],
                    ['value' => 'delete', 'label' => '삭제', 'icon' => 'trash', 'dangerous' => true],
                    ['value' => 'export', 'label' => '내보내기', 'icon' => 'download'],
                ],
                'export_formats' => [
                    ['value' => 'csv', 'label' => 'CSV'],
                    ['value' => 'xlsx', 'label' => 'Excel'],
                ],
                'max_selection' => 100,
                'confirmation_required' => ['delete', 'deactivate'],
            ];

            return $this->successResponse(
                $options,
                '대량 작업 옵션을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '대량 작업 옵션 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }
