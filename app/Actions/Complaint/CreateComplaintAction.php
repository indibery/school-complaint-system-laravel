<?php

namespace App\Actions\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Services\Complaint\ComplaintServiceInterface;
use App\Services\Complaint\ComplaintFileServiceInterface;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Events\ComplaintCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateComplaintAction
{
    public function __construct(
        private ComplaintServiceInterface $complaintService,
        private ComplaintFileServiceInterface $fileService,
        private ComplaintAssignmentServiceInterface $assignmentService
    ) {}

    /**
     * 민원 생성 액션
     */
    public function execute(array $data, User $user, ?array $files = null): Complaint
    {
        return DB::transaction(function () use ($data, $user, $files) {
            // 1. 민원 번호 생성
            $data['complaint_number'] = $this->complaintService->generateComplaintNumber();
            $data['created_by'] = $user->id;

            // 2. 민원 생성
            $complaint = $this->complaintService->create($data, $user);

            // 3. 첨부파일 처리
            if ($files && !empty($files)) {
                $this->fileService->uploadFiles($complaint, $files, $user);
            }

            // 4. 자동 할당 처리
            $this->assignmentService->autoAssign($complaint);

            // 5. 민원 생성 이벤트 발생
            event(new ComplaintCreated($complaint->fresh(['category', 'department', 'complainant', 'assignedTo'])));

            Log::info('민원 생성 완료', [
                'complaint_id' => $complaint->id,
                'complaint_number' => $complaint->complaint_number,
                'user_id' => $user->id,
                'files_count' => $files ? count($files) : 0
            ]);

            return $complaint->fresh(['category', 'department', 'complainant', 'assignedTo']);
        });
    }
}
