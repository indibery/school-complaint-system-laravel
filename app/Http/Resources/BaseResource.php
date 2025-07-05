<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * 한국어 날짜 포맷 반환
     */
    protected function formatDate($date, string $format = 'Y-m-d H:i:s'): ?string
    {
        return $date ? $date->format($format) : null;
    }

    /**
     * 한국어 날짜 포맷 반환 (사용자 친화적)
     */
    protected function formatDateForHumans($date): ?string
    {
        if (!$date) {
            return null;
        }

        return $date->diffForHumans();
    }

    /**
     * 한국어 날짜 포맷 반환 (년-월-일)
     */
    protected function formatDateOnly($date): ?string
    {
        return $date ? $date->format('Y-m-d') : null;
    }

    /**
     * 한국어 시간 포맷 반환 (시:분)
     */
    protected function formatTimeOnly($date): ?string
    {
        return $date ? $date->format('H:i') : null;
    }

    /**
     * 한국어 날짜시간 포맷 반환 (년-월-일 시:분)
     */
    protected function formatDateTime($date): ?string
    {
        return $date ? $date->format('Y-m-d H:i') : null;
    }

    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < 4) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }

    /**
     * 상태 코드를 한국어로 변환
     */
    protected function formatStatus(string $status): string
    {
        $statusMap = [
            'pending' => '대기중',
            'in_progress' => '진행중',
            'completed' => '완료',
            'cancelled' => '취소됨',
            'rejected' => '거부됨',
            'on_hold' => '보류중',
            'resolved' => '해결됨',
            'closed' => '종료됨',
            'active' => '활성',
            'inactive' => '비활성',
            'draft' => '임시저장',
            'published' => '게시됨',
            'archived' => '보관됨',
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * 우선순위를 한국어로 변환
     */
    protected function formatPriority(string $priority): string
    {
        $priorityMap = [
            'low' => '낮음',
            'medium' => '보통',
            'high' => '높음',
            'urgent' => '긴급',
            'critical' => '매우긴급',
        ];

        return $priorityMap[$priority] ?? $priority;
    }

    /**
     * 사용자 역할을 한국어로 변환
     */
    protected function formatRole(string $role): string
    {
        $roleMap = [
            'admin' => '관리자',
            'teacher' => '교사',
            'parent' => '학부모',
            'security_staff' => '학교지킴이',
            'ops_staff' => '운영팀',
            'student' => '학생',
        ];

        return $roleMap[$role] ?? $role;
    }

    /**
     * 민감한 정보 마스킹 처리
     */
    protected function maskSensitiveData(string $data, int $visibleChars = 3): string
    {
        if (strlen($data) <= $visibleChars) {
            return str_repeat('*', strlen($data));
        }

        $visible = substr($data, 0, $visibleChars);
        $masked = str_repeat('*', strlen($data) - $visibleChars);
        
        return $visible . $masked;
    }

    /**
     * 이메일 마스킹 처리
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = $this->maskSensitiveData($username, 2);
        
        return $maskedUsername . '@' . $domain;
    }

    /**
     * 전화번호 마스킹 처리
     */
    protected function maskPhone(string $phone): string
    {
        // 010-1234-5678 형태의 번호 마스킹
        $pattern = '/(\d{3})-(\d{4})-(\d{4})/';
        if (preg_match($pattern, $phone, $matches)) {
            return $matches[1] . '-****-' . $matches[3];
        }

        // 일반적인 번호 마스킹
        return $this->maskSensitiveData($phone, 3);
    }

    /**
     * 조건부 데이터 포함
     */
    protected function whenUserCan(string $permission, $value, $default = null)
    {
        $user = request()->user();
        
        if (!$user) {
            return $default;
        }

        // 관리자는 모든 데이터 접근 가능
        if ($user->isAdmin()) {
            return $value;
        }

        // 권한에 따른 데이터 반환 로직
        return $this->checkPermission($user, $permission) ? $value : $default;
    }

    /**
     * 권한 확인 (하위 클래스에서 구현)
     */
    protected function checkPermission($user, string $permission): bool
    {
        // 기본적으로 false 반환 (하위 클래스에서 오버라이드)
        return false;
    }

    /**
     * 사용자 역할에 따른 데이터 필터링
     */
    protected function filterByRole($data, array $allowedRoles): mixed
    {
        $user = request()->user();
        
        if (!$user || !in_array($user->role, $allowedRoles)) {
            return null;
        }

        return $data;
    }

    /**
     * 소유자 확인
     */
    protected function isOwner($ownerId): bool
    {
        $user = request()->user();
        return $user && $user->id === $ownerId;
    }

    /**
     * 추가 메타데이터 포함
     */
    protected function withMeta(array $meta): array
    {
        return array_merge(parent::toArray(request()), ['meta' => $meta]);
    }

    /**
     * 타임스탬프 정보 반환
     */
    protected function getTimestamps(): array
    {
        return [
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
            'created_at_human' => $this->formatDateForHumans($this->created_at),
            'updated_at_human' => $this->formatDateForHumans($this->updated_at),
        ];
    }

    /**
     * 소프트 삭제 정보 포함
     */
    protected function withSoftDeletes(): array
    {
        $data = [];
        
        if (method_exists($this->resource, 'trashed')) {
            $data['is_deleted'] = $this->resource->trashed();
            if ($this->resource->trashed()) {
                $data['deleted_at'] = $this->formatDateTime($this->deleted_at);
                $data['deleted_at_human'] = $this->formatDateForHumans($this->deleted_at);
            }
        }

        return $data;
    }
}
