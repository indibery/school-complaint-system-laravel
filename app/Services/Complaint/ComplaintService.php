<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\ComplaintRepositoryInterface;
use App\Services\Complaint\ComplaintStatusServiceInterface;
use App\Services\Complaint\ComplaintAssignmentServiceInterface;
use App\Services\Complaint\ComplaintFileServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ComplaintService implements ComplaintServiceInterface
{
    public function __construct(
        private ComplaintRepositoryInterface $repository,
        private ComplaintStatusServiceInterface $statusService,
        private ComplaintAssignmentServiceInterface $assignmentService,
        private ComplaintFileServiceInterface $fileService
    ) {}

    /**
     * 민원 목록 조회
     */
    public function getList(array $filters, User $user): LengthAwarePaginator
    {
        try {
            return $this->repository->getFilteredList($filters, $user);
        } catch (\Exception $e) {
            Log::error('민원 목록 조회 중 오류 발생', [
                'user_id' => $user->id,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 민원 생성
     */
    public function create(array $data, User $user): Complaint
    {
        try {
            DB::beginTransaction();

            // 민원 번호 생성
            $data['complaint_number'] = $this->generateComplaintNumber();
            $data['created_by'] = $user->id;

            // 학생 정보 추가 (학부모가 작성하는 경우)
            if ($user->hasRole('parent') && !empty($data['student_id'])) {
                $this->validateStudentAccess($user, $data['student_id']);
            }

            // 민원 생성
            $complaint = $this->repository->create($data);

            // 태그 처리
            if (!empty($data['tags'])) {
                $this->syncTags($complaint, $data['tags']);
            }

            DB::commit();

            Log::info('민원 생성 완료', [
                'complaint_id' => $complaint->id,
                'complaint_number' => $complaint->complaint_number,
                'user_id' => $user->id
            ]);

            return $complaint->load(['category', 'department', 'complainant', 'assignedTo']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 생성 중 오류 발생', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 민원 조회
     */
    public function find(int $id, User $user): ?Complaint
    {
        try {
            $complaint = $this->repository->find($id);
            
            if (!$complaint) {
                return null;
            }

            // 권한 확인
            if (!$this->canView($complaint, $user)) {
                throw new \Exception('해당 민원을 조회할 권한이 없습니다.');
            }

            // 조회수 증가
            $this->incrementViews($complaint);

            return $complaint->load([
                'category', 
                'department', 
                'complainant', 
                'assignedTo', 
                'student', 
                'attachments',
                'comments.author',
                'statusHistory.changedBy'
            ]);

        } catch (\Exception $e) {
            Log::error('민원 조회 중 오류 발생', [
                'complaint_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 민원 수정
     */
    public function update(Complaint $complaint, array $data, User $user): Complaint
    {
        try {
            DB::beginTransaction();

            // 권한 확인
            if (!$this->canUpdate($complaint, $user)) {
                throw new \Exception('해당 민원을 수정할 권한이 없습니다.');
            }

            // 수정자 정보 추가
            $data['updated_by'] = $user->id;

            // 민원 정보 업데이트
            $complaint = $this->repository->update($complaint, $data);

            // 태그 업데이트
            if (array_key_exists('tags', $data)) {
                $this->syncTags($complaint, $data['tags'] ?? []);
            }

            // 수정 이력 저장
            $this->statusService->logStatusHistory(
                $complaint,
                'updated',
                '민원 정보가 수정되었습니다.',
                $user
            );

            DB::commit();

            Log::info('민원 수정 완료', [
                'complaint_id' => $complaint->id,
                'user_id' => $user->id
            ]);

            return $complaint->load(['category', 'department', 'complainant', 'assignedTo']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 수정 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 민원 삭제
     */
    public function delete(Complaint $complaint, User $user): bool
    {
        try {
            DB::beginTransaction();

            // 권한 확인
            if (!$this->canDelete($complaint, $user)) {
                throw new \Exception('민원을 삭제할 권한이 없습니다.');
            }

            // 삭제 불가 상태 체크
            if (in_array($complaint->status, ['in_progress', 'resolved'])) {
                throw new \Exception('진행 중이거나 해결된 민원은 삭제할 수 없습니다.');
            }

            // 이력 저장
            $this->statusService->logStatusHistory(
                $complaint,
                'deleted',
                '민원이 삭제되었습니다.',
                $user
            );

            // 소프트 삭제 실행
            $result = $this->repository->delete($complaint);

            DB::commit();

            Log::info('민원 삭제 완료', [
                'complaint_id' => $complaint->id,
                'user_id' => $user->id
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('민원 삭제 중 오류 발생', [
                'complaint_id' => $complaint->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 내 민원 목록 조회
     */
    public function getMyComplaints(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->repository->getUserComplaints($user, $filters);
        } catch (\Exception $e) {
            Log::error('내 민원 목록 조회 중 오류 발생', [
                'user_id' => $user->id,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 내 할당 민원 목록 조회
     */
    public function getAssignedComplaints(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->repository->getAssignedComplaints($user, $filters);
        } catch (\Exception $e) {
            Log::error('내 할당 민원 목록 조회 중 오류 발생', [
                'user_id' => $user->id,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 민원 조회 권한 확인
     */
    public function canView(Complaint $complaint, User $user): bool
    {
        // 관리자는 모든 민원 조회 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원 조회 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 작성자는 본인 민원 조회 가능
        if ($complaint->created_by === $user->id) {
            return true;
        }

        // 공개 민원은 관련 사용자들이 조회 가능
        if ($complaint->is_public) {
            // 같은 부서 직원
            if ($user->department_id === $complaint->department_id) {
                return true;
            }
            
            // 학부모는 자녀 관련 민원 조회 가능
            if ($user->hasRole('parent') && $complaint->student_id) {
                $childrenIds = $user->children()->pluck('id');
                return $childrenIds->contains($complaint->student_id);
            }
            
            // 학생은 본인 관련 민원 조회 가능
            if ($user->hasRole('student') && $complaint->student_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * 민원 수정 권한 확인
     */
    public function canUpdate(Complaint $complaint, User $user): bool
    {
        // 관리자는 모든 민원 수정 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 담당자는 할당받은 민원 수정 가능
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // 작성자는 본인 민원 수정 가능 (단, 접수 또는 진행 중 상태일 때만)
        if ($complaint->created_by === $user->id && 
            in_array($complaint->status, ['pending', 'in_progress'])) {
            return true;
        }

        return false;
    }

    /**
     * 민원 삭제 권한 확인
     */
    public function canDelete(Complaint $complaint, User $user): bool
    {
        // 관리자는 모든 민원 삭제 가능
        if ($user->hasRole('admin')) {
            return true;
        }

        // 작성자는 본인 민원 삭제 가능 (접수 상태일 때만)
        if ($complaint->created_by === $user->id && $complaint->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * 민원 번호 생성
     */
    public function generateComplaintNumber(): string
    {
        $prefix = 'C' . date('Ymd');
        $lastNumber = $this->repository->getLastComplaintNumber($prefix);
        
        if ($lastNumber) {
            $lastSerial = (int) substr($lastNumber, -4);
            $newSerial = $lastSerial + 1;
        } else {
            $newSerial = 1;
        }
        
        return $prefix . str_pad($newSerial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 민원 조회수 증가
     */
    public function incrementViews(Complaint $complaint): void
    {
        try {
            $this->repository->incrementViews($complaint);
        } catch (\Exception $e) {
            // 조회수 증가 실패는 중요하지 않으므로 로그만 남기고 계속 진행
            Log::warning('민원 조회수 증가 실패', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 학생 접근 권한 확인
     */
    private function validateStudentAccess(User $user, int $studentId): void
    {
        if (!$user->hasRole('parent')) {
            throw new \Exception('학부모만 학생 정보를 설정할 수 있습니다.');
        }

        $childrenIds = $user->children()->pluck('id');
        if (!$childrenIds->contains($studentId)) {
            throw new \Exception('해당 학생에 대한 권한이 없습니다.');
        }
    }

    /**
     * 태그 동기화
     */
    private function syncTags(Complaint $complaint, array $tags): void
    {
        if (method_exists($complaint, 'syncTags')) {
            $complaint->syncTags($tags);
        }
    }
}
