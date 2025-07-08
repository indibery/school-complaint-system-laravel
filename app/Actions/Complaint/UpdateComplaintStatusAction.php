<?php

namespace App\Actions\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Services\Complaint\ComplaintStatusServiceInterface;
use App\Events\ComplaintStatusChanged;
use App\Events\ComplaintResolved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateComplaintStatusAction
{
    public function __construct(
        private ComplaintStatusServiceInterface $statusService
    ) {}

    /**
     * 민원 상태 변경 액션
     */
    public function execute(
        Complaint $complaint,
        string $newStatus,
        User $user,
        array $data = []
    ): Complaint {
        return DB::transaction(function () use ($complaint, $newStatus, $user, $data) {
            $oldStatus = $complaint->status;

            // 1. 상태 변경 권한 확인
            if (!$this->statusService->canUpdateStatus($complaint, $newStatus, $user)) {
                throw new \Exception('상태를 변경할 권한이 없습니다.');
            }

            // 2. 유효한 상태 전환인지 확인
            if (!$this->statusService->isValidStatusTransition($oldStatus, $newStatus)) {
                throw new \Exception('올바르지 않은 상태 전환입니다.');
            }

            // 3. 상태 변경 실행
            $complaint = $this->statusService->updateStatus($complaint, $newStatus, $user, $data);

            // 4. 상태 변경 이벤트 발생
            event(new ComplaintStatusChanged(
                $complaint->fresh(['category', 'department', 'complainant', 'assignedTo']),
                $oldStatus,
                $newStatus,
                $user
            ));

            // 5. 해결됨 상태인 경우 추가 이벤트 발생
            if ($newStatus === 'resolved') {
                event(new ComplaintResolved(
                    $complaint,
                    $user,
                    $data['resolution_note'] ?? null
                ));
            }

            Log::info('민원 상태 변경 완료', [
                'complaint_id' => $complaint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id
            ]);

            return $complaint->fresh(['category', 'department', 'complainant', 'assignedTo']);
        });
    }
}
