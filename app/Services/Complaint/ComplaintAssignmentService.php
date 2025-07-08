<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\ComplaintRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ComplaintAssignmentService implements ComplaintAssignmentServiceInterface
{
    public function __construct(
        private ComplaintRepositoryInterface $repository
    ) {}

    /**
     * 민원 할당
     */
    public function assign(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $data = []
    ): Complaint {
        try {
            DB::beginTransaction();

            // 할당 권한 확인
            if (!$this->canAssign($complaint, $assignedBy)) {
                throw new \Exception('민원을 할당할 권한이 없습니다.');
            }

            $oldAssignee = $complaint->assignedTo;

            // 할당 정보 업데이트
            $updateData = [
                'assigned_to' => $assignee->id,
                'department_id' => $data['department_id'] ?? $assignee->department_id ?? $complaint->department_id,
                'priority' => $data['priority'] ?? $complaint->priority,
                'due_date' => $data['due_date'] ?? $complaint->due_date,
                'status' => $complaint->status === 'pending' ? 'assigned' : $complaint->status,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy->id,
            ];

            $complaint = $this->repository->update($complaint, $updateData);

            // 할당 메타데이터 업데이트
            $this->updateAssignmentMetadata($complaint, $assignedBy, $data);

            // 할당 이력 저장
            $this->logAssignmentHistory($complaint, $assignee, $assignedBy, $data);

            DB::commit();

            Log::info('민원 할당 완료', [
                'complaint_id' => $complaint->id,
                'assignee_id' => $assignee->id,
                'assigned_by' => $assignedBy->id,
                'old_assignee_id' => $oldAssignee?->id
            ]);

            return $complaint;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 할당 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'assignee_id' => $assignee->id,
                'assigned_by' => $assignedBy->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 자동 할당
     */
    public function autoAssign(Complaint $complaint): ?Complaint
    {
        try {
            $assignee = $this->applyAutoAssignmentRules($complaint);
            
            if (!$assignee) {
                Log::info('자동 할당 대상자 없음', [
                    'complaint_id' => $complaint->id
                ]);
                return null;
            }

            // 시스템 사용자로 할당 (또는 관리자 계정)
            $systemUser = User::where('email', 'system@example.com')->first() ?? 
                         User::whereHas('roles', function ($q) {
                             $q->where('name', 'admin');
                         })->first();

            if (!$systemUser) {
                Log::warning('시스템 사용자 또는 관리자 계정을 찾을 수 없음');
                return null;
            }

            return $this->assign($complaint, $assignee, $systemUser, [
                'assignment_note' => '자동 할당',
                'auto_assigned' => true
            ]);

        } catch (\Exception $e) {
            Log::error('자동 할당 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * 할당 권한 확인
     */
    public function canAssign(Complaint $complaint, User $user): bool
    {
        // 관리자는 모든 민원 할당 가능
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // 부서장은 자신의 부서 민원 할당 가능
        if ($user->hasRole('department_head') && 
            $complaint->department_id === $user->department_id) {
            return true;
        }

        // 교감/교장은 모든 민원 할당 가능
        if ($user->hasRole(['vice_principal', 'principal'])) {
            return true;
        }

        // 현재 담당자는 다른 사람에게 재할당 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 할당 가능한 사용자 목록
     */
    public function getAssignableUsers(Complaint $complaint): array
    {
        $query = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'teacher', 'staff', 'department_head']);
        });

        // 부서가 설정된 경우 해당 부서 사용자 우선
        if ($complaint->department_id) {
            $query->where(function ($q) use ($complaint) {
                $q->where('department_id', $complaint->department_id)
                  ->orWhereHas('roles', function ($subQ) {
                      $subQ->whereIn('name', ['admin', 'principal', 'vice_principal']);
                  });
            });
        }

        return $query->with('roles', 'department')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'department' => $user->department?->name,
                            'roles' => $user->roles->pluck('name')->toArray(),
                            'is_available' => $this->isUserAvailable($user),
                        ];
                    })
                    ->toArray();
    }

    /**
     * 할당 이력 저장
     */
    public function logAssignmentHistory(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $metadata = []
    ): void {
        try {
            $historyData = [
                'complaint_id' => $complaint->id,
                'action' => 'assigned',
                'description' => "담당자가 '{$assignee->name}'로 할당되었습니다.",
                'changed_by' => $assignedBy->id,
                'changed_at' => now(),
                'metadata' => array_merge($metadata, [
                    'assignee_id' => $assignee->id,
                    'assignee_name' => $assignee->name,
                    'assigned_by_name' => $assignedBy->name,
                    'assignment_note' => $metadata['assignment_note'] ?? '',
                    'auto_assigned' => $metadata['auto_assigned'] ?? false,
                ])
            ];

            DB::table('complaint_status_histories')->insert($historyData);

        } catch (\Exception $e) {
            Log::error('할당 이력 저장 실패', [
                'complaint_id' => $complaint->id,
                'assignee_id' => $assignee->id,
                'assigned_by' => $assignedBy->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 할당 해제
     */
    public function unassign(Complaint $complaint, User $user): Complaint
    {
        try {
            DB::beginTransaction();

            if (!$this->canAssign($complaint, $user)) {
                throw new \Exception('민원 할당을 해제할 권한이 없습니다.');
            }

            $oldAssignee = $complaint->assignedTo;

            $updateData = [
                'assigned_to' => null,
                'status' => 'pending',
                'assigned_at' => null,
                'assigned_by' => null,
            ];

            $complaint = $this->repository->update($complaint, $updateData);

            // 할당 해제 이력 저장
            $historyData = [
                'complaint_id' => $complaint->id,
                'action' => 'unassigned',
                'description' => "담당자 할당이 해제되었습니다. (이전 담당자: {$oldAssignee->name})",
                'changed_by' => $user->id,
                'changed_at' => now(),
                'metadata' => [
                    'previous_assignee_id' => $oldAssignee->id,
                    'previous_assignee_name' => $oldAssignee->name,
                    'unassigned_by_name' => $user->name,
                ]
            ];

            DB::table('complaint_status_histories')->insert($historyData);

            DB::commit();

            Log::info('민원 할당 해제 완료', [
                'complaint_id' => $complaint->id,
                'previous_assignee_id' => $oldAssignee->id,
                'unassigned_by' => $user->id
            ]);

            return $complaint;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 할당 해제 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 재할당
     */
    public function reassign(
        Complaint $complaint,
        User $newAssignee,
        User $reassignedBy,
        array $data = []
    ): Complaint {
        $data['reassignment'] = true;
        $data['assignment_note'] = $data['assignment_note'] ?? '민원 재할당';
        
        return $this->assign($complaint, $newAssignee, $reassignedBy, $data);
    }

    /**
     * 자동 할당 규칙 적용
     */
    public function applyAutoAssignmentRules(Complaint $complaint): ?User
    {
        // 1. 카테고리 기반 자동 할당
        if ($complaint->category && $complaint->category->default_assignee_id) {
            $assignee = User::find($complaint->category->default_assignee_id);
            if ($assignee && $this->isUserAvailable($assignee)) {
                return $assignee;
            }
        }

        // 2. 부서 기반 자동 할당
        if ($complaint->department && $complaint->department->head_id) {
            $assignee = User::find($complaint->department->head_id);
            if ($assignee && $this->isUserAvailable($assignee)) {
                return $assignee;
            }
        }

        // 3. 우선순위 기반 자동 할당
        if ($complaint->priority === 'urgent') {
            $assignee = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->first();
            
            if ($assignee && $this->isUserAvailable($assignee)) {
                return $assignee;
            }
        }

        // 4. 부서 내 가장 적은 민원을 담당하는 사용자에게 할당
        if ($complaint->department_id) {
            $assignee = $this->getLeastBusyUserInDepartment($complaint->department_id);
            if ($assignee) {
                return $assignee;
            }
        }

        return null;
    }

    /**
     * 할당 알림 발송
     */
    public function sendAssignmentNotification(
        Complaint $complaint,
        User $assignee,
        array $data = []
    ): void {
        // 알림 서비스가 구현되면 여기서 호출
        // $this->notificationService->notifyAssigned($complaint, $assignee, $data);
        
        Log::info('할당 알림 발송 예정', [
            'complaint_id' => $complaint->id,
            'assignee_id' => $assignee->id
        ]);
    }

    /**
     * 할당 메타데이터 업데이트
     */
    private function updateAssignmentMetadata(Complaint $complaint, User $assignedBy, array $data): void
    {
        $metadata = $complaint->metadata ?? [];
        $metadata['assignment'] = [
            'assigned_at' => now()->toISOString(),
            'assigned_by' => $assignedBy->id,
            'assigned_by_name' => $assignedBy->name,
            'assignment_note' => $data['assignment_note'] ?? '',
            'escalation_level' => $data['escalation_level'] ?? 1,
            'requires_approval' => $data['requires_approval'] ?? false,
            'auto_reassign_if_overdue' => $data['auto_reassign_if_overdue'] ?? false,
            'reassign_after_days' => $data['reassign_after_days'] ?? null,
            'auto_assigned' => $data['auto_assigned'] ?? false,
        ];
        
        $this->repository->update($complaint, ['metadata' => $metadata]);
    }

    /**
     * 사용자 가용성 확인
     */
    private function isUserAvailable(User $user): bool
    {
        // 기본적으로 활성 사용자만 할당 가능
        if (!$user->is_active ?? true) {
            return false;
        }

        // 휴가 중인지 확인 (향후 구현)
        // if ($user->isOnLeave()) {
        //     return false;
        // }

        // 과도한 민원 할당 확인
        $assignedCount = $this->repository->count([
            'assigned_to' => $user->id,
            'status' => ['pending', 'assigned', 'in_progress']
        ]);

        $maxAssignments = $user->hasRole('admin') ? 50 : 20;
        
        return $assignedCount < $maxAssignments;
    }

    /**
     * 부서 내 가장 여유로운 사용자 찾기
     */
    private function getLeastBusyUserInDepartment(int $departmentId): ?User
    {
        $users = User::where('department_id', $departmentId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teacher', 'staff', 'department_head']);
            })
            ->get();

        $leastBusyUser = null;
        $minAssignments = PHP_INT_MAX;

        foreach ($users as $user) {
            if (!$this->isUserAvailable($user)) {
                continue;
            }

            $assignedCount = $this->repository->count([
                'assigned_to' => $user->id,
                'status' => ['pending', 'assigned', 'in_progress']
            ]);

            if ($assignedCount < $minAssignments) {
                $minAssignments = $assignedCount;
                $leastBusyUser = $user;
            }
        }

        return $leastBusyUser;
    }
}
