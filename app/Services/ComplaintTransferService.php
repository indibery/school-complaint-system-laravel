<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

/**
 * 민원 이관 서비스
 * 
 * 단순하고 유연한 이관 시스템:
 * 1. 부서 간 이관
 * 2. 담당자 간 이관  
 * 3. 권한 레벨별 이관
 * 4. 자동 이관 규칙
 */
class ComplaintTransferService
{
    /**
     * 이관 가능한 권한 매핑
     */
    const TRANSFER_HIERARCHY = [
        'student' => [],
        'parent' => [],
        'teacher' => ['department_head', 'vice_principal', 'principal'],
        'department_head' => ['vice_principal', 'principal'],
        'vice_principal' => ['principal'],
        'principal' => [],
        'admin' => ['principal'], // 관리자는 교장에게만 이관 가능
        'super_admin' => [] // 최고 관리자는 이관 불가
    ];

    /**
     * 자동 이관 규칙
     */
    const AUTO_TRANSFER_RULES = [
        'urgent' => [
            'condition' => 'priority = "urgent"',
            'target_role' => 'department_head',
            'timeout_hours' => 2
        ],
        'high_priority' => [
            'condition' => 'priority = "high"',
            'target_role' => 'department_head', 
            'timeout_hours' => 24
        ],
        'department_escalation' => [
            'condition' => 'no_response_hours >= 72',
            'target_role' => 'vice_principal',
            'timeout_hours' => 48
        ]
    ];

    /**
     * 부서 간 이관
     */
    public function transferToDepartment(Complaint $complaint, int $departmentId, ?string $reason = null, ?User $transferBy = null): bool
    {
        try {
            DB::beginTransaction();

            $targetDepartment = Department::findOrFail($departmentId);
            
            // 부서장 자동 할당
            $departmentHead = $targetDepartment->head;
            
            $complaint->update([
                'department_id' => $departmentId,
                'assigned_to' => $departmentHead?->id,
                'transferred_at' => now(),
                'transferred_by' => $transferBy?->id,
                'transfer_reason' => $reason ?? '부서 이관'
            ]);

            // 이관 이력 저장
            $this->logTransferHistory($complaint, 'department_transfer', [
                'from_department' => $complaint->getOriginal('department_id'),
                'to_department' => $departmentId,
                'to_user' => $departmentHead?->id,
                'reason' => $reason
            ], $transferBy);

            // 알림 발송
            $this->sendTransferNotification($complaint, $departmentHead, 'department_transfer');

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Department transfer failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 담당자 간 이관
     */
    public function transferToUser(Complaint $complaint, int $userId, ?string $reason = null, ?User $transferBy = null): bool
    {
        try {
            DB::beginTransaction();

            $targetUser = User::findOrFail($userId);
            
            // 권한 체크
            if (!$this->canTransferTo($transferBy, $targetUser)) {
                throw new \Exception('해당 사용자에게 이관할 권한이 없습니다.');
            }

            $complaint->update([
                'assigned_to' => $userId,
                'transferred_at' => now(),
                'transferred_by' => $transferBy?->id,
                'transfer_reason' => $reason ?? '담당자 이관'
            ]);

            // 이관 이력 저장
            $this->logTransferHistory($complaint, 'user_transfer', [
                'from_user' => $complaint->getOriginal('assigned_to'),
                'to_user' => $userId,
                'reason' => $reason
            ], $transferBy);

            // 알림 발송
            $this->sendTransferNotification($complaint, $targetUser, 'user_transfer');

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User transfer failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 권한 레벨별 상향 이관
     */
    public function escalateToHigherLevel(Complaint $complaint, ?string $reason = null, ?User $escalateBy = null): bool
    {
        try {
            DB::beginTransaction();

            $currentUser = $complaint->assignedTo ?? $escalateBy;
            if (!$currentUser) {
                throw new \Exception('이관할 현재 담당자가 없습니다.');
            }

            $targetUser = $this->getNextLevelUser($currentUser, $complaint->department_id);
            if (!$targetUser) {
                throw new \Exception('상위 레벨 담당자를 찾을 수 없습니다.');
            }

            $complaint->update([
                'assigned_to' => $targetUser->id,
                'escalated_at' => now(),
                'escalated_by' => $escalateBy?->id,
                'escalation_reason' => $reason ?? '상위 레벨 이관',
                'escalation_level' => ($complaint->escalation_level ?? 0) + 1
            ]);

            // 이관 이력 저장
            $this->logTransferHistory($complaint, 'level_escalation', [
                'from_user' => $currentUser->id,
                'to_user' => $targetUser->id,
                'from_level' => $currentUser->role,
                'to_level' => $targetUser->role,
                'reason' => $reason
            ], $escalateBy);

            // 알림 발송
            $this->sendTransferNotification($complaint, $targetUser, 'level_escalation');

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Level escalation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 자동 이관 규칙 적용
     */
    public function applyAutoTransferRules(Complaint $complaint): bool
    {
        foreach (self::AUTO_TRANSFER_RULES as $ruleName => $rule) {
            if ($this->shouldApplyRule($complaint, $rule)) {
                return $this->executeAutoTransfer($complaint, $rule, $ruleName);
            }
        }
        return false;
    }

    /**
     * 이관 가능 여부 확인
     */
    public function canTransferTo(?User $from, User $to): bool
    {
        if (!$from) return true; // 시스템 자동 이관

        $fromRole = $from->role;
        $toRole = $to->role;

        // 동일 권한 레벨 간 이관 허용
        if ($fromRole === $toRole) return true;

        // 상향 이관 허용 확인
        return in_array($toRole, self::TRANSFER_HIERARCHY[$fromRole] ?? []);
    }

    /**
     * 다음 레벨 사용자 찾기
     */
    private function getNextLevelUser(User $currentUser, ?int $departmentId): ?User
    {
        $currentRole = $currentUser->role;
        $nextRoles = self::TRANSFER_HIERARCHY[$currentRole] ?? [];

        if (empty($nextRoles)) return null;

        // 같은 부서 내에서 다음 레벨 사용자 찾기
        if ($departmentId) {
            foreach ($nextRoles as $role) {
                $user = User::where('role', $role)
                    ->where('department_id', $departmentId)
                    ->where('status', 'active')
                    ->first();
                if ($user) return $user;
            }
        }

        // 부서 상관없이 다음 레벨 사용자 찾기
        foreach ($nextRoles as $role) {
            $user = User::where('role', $role)
                ->where('status', 'active')
                ->first();
            if ($user) return $user;
        }

        return null;
    }

    /**
     * 자동 이관 규칙 적용 여부 확인
     */
    private function shouldApplyRule(Complaint $complaint, array $rule): bool
    {
        switch ($rule['condition']) {
            case 'priority = "urgent"':
                return $complaint->priority === 'urgent';
            case 'priority = "high"':
                return $complaint->priority === 'high';
            case 'no_response_hours >= 72':
                return $complaint->created_at->diffInHours(now()) >= 72 && 
                       $complaint->status === 'pending';
            default:
                return false;
        }
    }

    /**
     * 자동 이관 실행
     */
    private function executeAutoTransfer(Complaint $complaint, array $rule, string $ruleName): bool
    {
        $targetRole = $rule['target_role'];
        $targetUser = User::where('role', $targetRole)
            ->where('status', 'active')
            ->first();

        if (!$targetUser) return false;

        $complaint->update([
            'assigned_to' => $targetUser->id,
            'transferred_at' => now(),
            'transfer_reason' => "자동 이관 규칙 적용: {$ruleName}",
            'auto_transferred' => true
        ]);

        // 이관 이력 저장
        $this->logTransferHistory($complaint, 'auto_transfer', [
            'rule' => $ruleName,
            'to_user' => $targetUser->id,
            'reason' => "자동 이관 규칙 적용"
        ], null);

        // 알림 발송
        $this->sendTransferNotification($targetUser, $complaint, 'auto_transfer');

        return true;
    }

    /**
     * 이관 이력 저장
     */
    private function logTransferHistory(Complaint $complaint, string $type, array $data, ?User $user): void
    {
        $complaint->statusHistory()->create([
            'status' => 'transferred',
            'changed_by' => $user?->id,
            'changed_at' => now(),
            'comment' => json_encode([
                'type' => $type,
                'data' => $data
            ])
        ]);
    }

    /**
     * 이관 알림 발송
     */
    private function sendTransferNotification(Complaint $complaint, User $targetUser, string $type): void
    {
        // 알림 발송 로직 구현
        // Notification::send($targetUser, new ComplaintTransferredNotification($complaint, $type));
    }

    /**
     * 이관 통계 조회
     */
    public function getTransferStatistics(?int $departmentId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Complaint::query();

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total_complaints' => $query->count(),
            'transferred_complaints' => $query->whereNotNull('transferred_at')->count(),
            'escalated_complaints' => $query->whereNotNull('escalated_at')->count(),
            'auto_transferred' => $query->where('auto_transferred', true)->count(),
            'avg_transfer_time' => $query->whereNotNull('transferred_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, transferred_at)) as avg_hours')
                ->value('avg_hours'),
            'transfer_by_department' => $query->whereNotNull('transferred_at')
                ->groupBy('department_id')
                ->selectRaw('department_id, COUNT(*) as count')
                ->pluck('count', 'department_id')
                ->toArray()
        ];
    }
}
