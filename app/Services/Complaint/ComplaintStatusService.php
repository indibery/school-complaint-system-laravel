<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\ComplaintRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComplaintStatusService implements ComplaintStatusServiceInterface
{
    public function __construct(
        private ComplaintRepositoryInterface $repository
    ) {}

    /**
     * 상태 변경
     */
    public function updateStatus(
        Complaint $complaint,
        string $newStatus,
        User $user,
        array $data = []
    ): Complaint {
        try {
            DB::beginTransaction();

            $oldStatus = $complaint->status;

            // 상태 변경 권한 확인
            if (!$this->canUpdateStatus($complaint, $newStatus, $user)) {
                throw new \Exception('상태를 변경할 권한이 없습니다.');
            }

            // 유효한 상태 전환인지 확인
            if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
                throw new \Exception('올바르지 않은 상태 전환입니다.');
            }

            // 상태 업데이트
            $updateData = [
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => $user->id,
            ];

            $complaint = $this->repository->update($complaint, $updateData);

            // 상태별 후속 처리
            $this->handleStatusChange($complaint, $oldStatus, $newStatus, $data);

            // 상태 변경 이력 저장
            $reason = $data['reason'] ?? '';
            $this->logStatusHistory(
                $complaint,
                'status_changed',
                "상태가 '{$this->getStatusLabel($oldStatus)}'에서 '{$this->getStatusLabel($newStatus)}'로 변경되었습니다." . 
                ($reason ? " 사유: {$reason}" : ''),
                $user,
                array_merge($data, ['old_status' => $oldStatus, 'new_status' => $newStatus])
            );

            DB::commit();

            Log::info('민원 상태 변경 완료', [
                'complaint_id' => $complaint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id
            ]);

            return $complaint;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 상태 변경 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'new_status' => $newStatus,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 상태 변경 권한 확인
     */
    public function canUpdateStatus(Complaint $complaint, string $newStatus, User $user): bool
    {
        // 관리자는 모든 상태 변경 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원의 상태 변경 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 부서장은 자신의 부서 민원 상태 변경 가능
        if ($user->hasRole('department_head') && 
            $complaint->department_id === $user->department_id) {
            return true;
        }

        // 교감/교장은 모든 민원 상태 변경 가능
        if ($user->hasRole(['vice_principal', 'principal'])) {
            return true;
        }

        // 특정 상태로의 변경은 작성자도 가능
        if ($complaint->created_by === $user->id && 
            in_array($newStatus, ['cancelled'])) {
            return true;
        }

        return false;
    }

    /**
     * 유효한 상태 전환인지 확인
     */
    public function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['assigned', 'in_progress', 'cancelled'],
            'assigned' => ['in_progress', 'pending', 'cancelled'],
            'in_progress' => ['resolved', 'assigned', 'cancelled'],
            'resolved' => ['closed', 'in_progress'],
            'closed' => ['in_progress'], // 재처리
            'cancelled' => ['pending'], // 취소 철회
        ];

        return isset($validTransitions[$currentStatus]) && 
               in_array($newStatus, $validTransitions[$currentStatus]);
    }

    /**
     * 상태별 후속 처리
     */
    public function handleStatusChange(
        Complaint $complaint,
        string $oldStatus,
        string $newStatus,
        array $data = []
    ): void {
        $updateData = [];

        switch ($newStatus) {
            case 'assigned':
                $updateData['assigned_at'] = now();
                break;
                
            case 'in_progress':
                $updateData['started_at'] = now();
                $updateData['started_by'] = auth()->id();
                break;
                
            case 'resolved':
                $updateData['resolved_at'] = now();
                $updateData['resolved_by'] = auth()->id();
                
                if (!empty($data['resolution_note'])) {
                    $updateData['resolution_note'] = $data['resolution_note'];
                }
                
                if (!empty($data['resolution_category'])) {
                    $updateData['resolution_category'] = $data['resolution_category'];
                }
                
                // 만족도 조사 예약
                if ($data['satisfaction_survey'] ?? false) {
                    $this->scheduleSatisfactionSurvey($complaint);
                }
                break;
                
            case 'closed':
                $updateData['closed_at'] = now();
                $updateData['closed_by'] = auth()->id();
                break;
                
            case 'cancelled':
                $updateData['cancelled_at'] = now();
                $updateData['cancelled_by'] = auth()->id();
                
                if (!empty($data['reason'])) {
                    $updateData['cancellation_reason'] = $data['reason'];
                }
                break;
        }

        if (!empty($updateData)) {
            $this->repository->update($complaint, $updateData);
        }

        // 후속 조치 설정
        if ($data['follow_up_required'] ?? false) {
            $this->scheduleFollowUp($complaint, $data['follow_up_date'] ?? null);
        }
    }

    /**
     * 상태 이력 저장
     */
    public function logStatusHistory(
        Complaint $complaint,
        string $action,
        string $description,
        User $user,
        array $metadata = []
    ): void {
        try {
            // 상태 이력 저장 (StatusHistory 모델이 있다고 가정)
            $historyData = [
                'complaint_id' => $complaint->id,
                'action' => $action,
                'description' => $description,
                'changed_by' => $user->id,
                'changed_at' => now(),
                'metadata' => array_merge($metadata, [
                    'user_name' => $user->name,
                    'user_role' => $user->roles->pluck('name')->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
            ];

            // StatusHistory 테이블에 저장
            DB::table('complaint_status_histories')->insert($historyData);

        } catch (\Exception $e) {
            Log::error('상태 이력 저장 실패', [
                'complaint_id' => $complaint->id,
                'action' => $action,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 상태 표시명 가져오기
     */
    public function getStatusLabel(string $status): string
    {
        $statusLabels = [
            'pending' => '접수',
            'assigned' => '할당',
            'in_progress' => '진행중',
            'resolved' => '해결',
            'closed' => '완료',
            'cancelled' => '취소',
        ];

        return $statusLabels[$status] ?? $status;
    }

    /**
     * 사용 가능한 상태 목록
     */
    public function getAvailableStatuses(): array
    {
        return [
            'pending' => '접수',
            'assigned' => '할당',
            'in_progress' => '진행중',
            'resolved' => '해결',
            'closed' => '완료',
            'cancelled' => '취소',
        ];
    }

    /**
     * 상태별 색상 클래스
     */
    public function getStatusColor(string $status): string
    {
        $statusColors = [
            'pending' => 'warning',
            'assigned' => 'info',
            'in_progress' => 'primary',
            'resolved' => 'success',
            'closed' => 'secondary',
            'cancelled' => 'danger',
        ];

        return $statusColors[$status] ?? 'secondary';
    }

    /**
     * 상태 변경 시 필요한 필드들
     */
    public function getRequiredFieldsForStatus(string $status): array
    {
        $requiredFields = [
            'resolved' => ['resolution_note'],
            'cancelled' => ['reason'],
            'closed' => [],
            'in_progress' => [],
            'assigned' => [],
            'pending' => [],
        ];

        return $requiredFields[$status] ?? [];
    }

    /**
     * 만족도 조사 예약
     */
    private function scheduleSatisfactionSurvey(Complaint $complaint): void
    {
        try {
            // 만족도 조사 스케줄링 로직
            $surveyData = [
                'complaint_id' => $complaint->id,
                'complainant_id' => $complaint->created_by,
                'scheduled_at' => now()->addDays(1),
                'expires_at' => now()->addDays(7),
                'survey_type' => 'resolution_satisfaction',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // 만족도 조사 테이블에 저장
            DB::table('satisfaction_surveys')->insert($surveyData);

            Log::info('만족도 조사 예약됨', [
                'complaint_id' => $complaint->id,
                'scheduled_at' => $surveyData['scheduled_at']
            ]);

        } catch (\Exception $e) {
            Log::error('만족도 조사 예약 실패', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 후속 조치 예약
     */
    private function scheduleFollowUp(Complaint $complaint, ?string $followUpDate): void
    {
        try {
            $date = $followUpDate ? Carbon::parse($followUpDate) : now()->addDays(7);

            $followUpData = [
                'complaint_id' => $complaint->id,
                'assigned_to' => $complaint->assigned_to,
                'scheduled_at' => $date,
                'follow_up_type' => 'status_check',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // 후속 조치 테이블에 저장
            DB::table('follow_up_actions')->insert($followUpData);

            Log::info('후속 조치 예약됨', [
                'complaint_id' => $complaint->id,
                'scheduled_at' => $date
            ]);

        } catch (\Exception $e) {
            Log::error('후속 조치 예약 실패', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
