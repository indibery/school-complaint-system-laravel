<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends BaseApiController
{
    /**
     * API 응답 테스트
     */
    public function testResponses(): JsonResponse
    {
        return $this->successResponse([
            'message' => 'API 응답 표준화 테스트',
            'formats' => [
                'success' => '성공 응답',
                'error' => '에러 응답',
                'paginated' => '페이지네이션 응답',
                'created' => '생성 응답',
                'updated' => '수정 응답',
                'deleted' => '삭제 응답',
            ],
            'status_codes' => [
                200 => 'OK',
                201 => 'Created',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                422 => 'Validation Error',
                500 => 'Internal Server Error',
            ],
        ], 'API 응답 표준화가 완료되었습니다.');
    }

    /**
     * 에러 응답 테스트
     */
    public function testError(): JsonResponse
    {
        return $this->errorResponse('테스트 에러 메시지', 400);
    }

    /**
     * 유효성 검증 에러 테스트
     */
    public function testValidation(): JsonResponse
    {
        return $this->validationErrorResponse([
            'email' => ['이메일은 필수입니다.'],
            'password' => ['비밀번호는 최소 8자 이상이어야 합니다.'],
        ], '유효성 검증에 실패했습니다.');
    }

    /**
     * 페이지네이션 테스트
     */
    public function testPagination(): JsonResponse
    {
        // 가상의 데이터 생성
        $data = collect(range(1, 100))->map(function ($i) {
            return [
                'id' => $i,
                'name' => "테스트 데이터 {$i}",
                'created_at' => now()->subDays($i)->toISOString(),
            ];
        });

        // 페이지네이션 처리
        $perPage = 10;
        $page = 1;
        $total = $data->count();
        $items = $data->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return $this->paginatedResponse($paginator, '페이지네이션 테스트 완료');
    }

    /**
     * 리소스 테스트
     */
    public function testResources(): JsonResponse
    {
        // 가상의 데이터 생성
        $userData = [
            'id' => 1,
            'name' => '홍길동',
            'email' => 'hong@example.com',
            'role' => 'teacher',
            'grade' => 3,
            'class_number' => 2,
            'department' => '교무부',
            'phone' => '010-1234-5678',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $complaintData = [
            'id' => 1,
            'complaint_number' => 'C2024-001',
            'title' => '시설 개선 요청',
            'content' => '교실 에어컨 수리가 필요합니다.',
            'status' => 'pending',
            'priority' => 'medium',
            'is_urgent' => false,
            'is_anonymous' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $this->successResponse([
            'user_resource' => [
                'description' => '사용자 리소스 변환 예시',
                'data' => (object) $userData,
            ],
            'complaint_resource' => [
                'description' => '민원 리소스 변환 예시',
                'data' => (object) $complaintData,
            ],
            'resources_created' => [
                'BaseResource' => '베이스 리소스 클래스',
                'UserResource' => '사용자 리소스 클래스',
                'ComplaintResource' => '민원 리소스 클래스',
                'CommentResource' => '댓글 리소스 클래스',
                'CategoryResource' => '카테고리 리소스 클래스',
                'DepartmentResource' => '부서 리소스 클래스',
                'AttachmentResource' => '첨부파일 리소스 클래스',
                'StudentResource' => '학생 리소스 클래스',
                'ComplaintStatusHistoryResource' => '민원 상태 히스토리 리소스 클래스',
                'UserCollection' => '사용자 컬렉션 클래스',
                'ComplaintCollection' => '민원 컬렉션 클래스',
            ],
            'features' => [
                'data_transformation' => '데이터 변환 및 필터링',
                'permission_based_access' => '권한 기반 데이터 접근',
                'sensitive_data_masking' => '민감정보 마스킹 처리',
                'korean_localization' => '한국어 현지화',
                'conditional_loading' => '조건부 데이터 로딩',
                'role_based_filtering' => '역할별 데이터 필터링',
                'summary_methods' => '요약 정보 메서드',
                'meta_information' => '메타 정보 포함',
            ],
        ], 'API 리소스 및 변환 클래스 생성 완료');
    }

    /**
     * 인증 API 테스트
     */
    public function testAuth(): JsonResponse
    {
        return $this->successResponse([
            'auth_endpoints' => [
                'login' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/login',
                    'description' => '로그인',
                    'parameters' => [
                        'email' => 'string|required',
                        'password' => 'string|required',
                        'remember' => 'boolean|optional',
                    ],
                ],
                'register' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/register',
                    'description' => '회원가입',
                    'parameters' => [
                        'name' => 'string|required',
                        'email' => 'string|required|unique',
                        'password' => 'string|required|min:8',
                        'password_confirmation' => 'string|required',
                        'role' => 'string|required|in:admin,teacher,parent,security_staff,ops_staff',
                        'grade' => 'integer|optional',
                        'class_number' => 'integer|optional',
                        'subject' => 'string|optional',
                        'department' => 'string|optional',
                        'phone' => 'string|optional',
                    ],
                ],
                'logout' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/logout',
                    'description' => '로그아웃',
                    'auth_required' => true,
                ],
                'logout_all' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/logout-all',
                    'description' => '모든 기기에서 로그아웃',
                    'auth_required' => true,
                ],
                'profile' => [
                    'method' => 'GET',
                    'url' => '/api/v1/auth/profile',
                    'description' => '프로필 조회',
                    'auth_required' => true,
                ],
                'update_profile' => [
                    'method' => 'PUT',
                    'url' => '/api/v1/auth/profile',
                    'description' => '프로필 수정',
                    'auth_required' => true,
                ],
                'change_password' => [
                    'method' => 'PUT',
                    'url' => '/api/v1/auth/change-password',
                    'description' => '비밀번호 변경',
                    'auth_required' => true,
                ],
                'refresh_token' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/refresh-token',
                    'description' => '토큰 갱신',
                    'auth_required' => true,
                ],
                'tokens' => [
                    'method' => 'GET',
                    'url' => '/api/v1/auth/tokens',
                    'description' => '활성 토큰 목록',
                    'auth_required' => true,
                ],
                'revoke_token' => [
                    'method' => 'DELETE',
                    'url' => '/api/v1/auth/tokens/{tokenId}',
                    'description' => '특정 토큰 삭제',
                    'auth_required' => true,
                ],
                'deactivate' => [
                    'method' => 'POST',
                    'url' => '/api/v1/auth/deactivate',
                    'description' => '계정 비활성화',
                    'auth_required' => true,
                ],
            ],
            'token_abilities' => [
                'admin' => ['*'],
                'teacher' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write', 'students:read'],
                'parent' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
                'security_staff' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
                'ops_staff' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
            ],
            'security_features' => [
                'rate_limiting' => '로그인 시도 제한 (5회/분)',
                'token_expiration' => '토큰 만료 시간 설정',
                'password_complexity' => '비밀번호 복잡도 검증',
                'audit_logging' => '인증 관련 로그 기록',
                'multiple_device_support' => '다중 기기 지원',
                'token_revocation' => '토큰 무효화',
                'account_deactivation' => '계정 비활성화',
            ],
        ], 'AuthController 및 인증 API 구현 완료');
    }

    /**
     * 권한 테스트
     */
    public function testPermission(): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return $this->unauthorizedResponse('로그인이 필요합니다.');
        }

        if (!$user->isAdmin()) {
            return $this->forbiddenResponse('관리자 권한이 필요합니다.');
        }

        return $this->successResponse([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'permissions' => ['admin'],
        ], '권한 확인 완료');
    }
}
