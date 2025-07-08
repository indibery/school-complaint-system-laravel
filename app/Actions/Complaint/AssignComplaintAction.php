<?php

namespace App\Actions\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Events\ComplaintAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignComplaintAction
{
    public function __construct(
        private ComplaintAssignmentServiceInterface $assignmentService
    ) {}

    /**
     * 민원 할당 액션
     */
    public function execute(
        Complaint $complaint,
        User $assignee,
        User $assignedBy,
        array $data = []
    ): Complaint {
        return DB::transaction(function () use ($complaint, $assignee, $assignedBy, $data) {
            // 1. 할당 권한 확인
            if (!$this->assignmentService->canAssign($complaint, $assignedBy)) {
                throw new \Exception('민원을 할당할 권한이 없습니다.');
            }

            // 2. 민원 할당 실행
            $complaint = $this->assignmentService->assign($complaint, $assignee, $assignedBy, $data);

            // 3. 할당 이벤트 발생
            event(new ComplaintAssigned(
                $complaint->fresh(['category', 'department', 'complainant', 'assignedTo']),
                $assignee,
                $assignedBy
            ));

            Log::info('민원 할당 완료', [
                'complaint_id' => $complaint->id,
                'assignee_id' => $assignee->id,
                'assigned_by' => $assignedBy->id
            ]);

            return $complaint->fresh(['category', 'department', 'complainant', 'assignedTo']);
        });
    }
}
