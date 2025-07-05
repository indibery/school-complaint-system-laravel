<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BaseApiController extends Controller
{
    /**
     * 한 페이지당 기본 아이템 수
     */
    protected int $perPage = 15;

    /**
     * 최대 페이지당 아이템 수
     */
    protected int $maxPerPage = 100;

    /**
     * 성공 응답 반환
     */
    protected function successResponse(
        $data = null, 
        string $message = null, 
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::success($data, $message, $statusCode, $meta);
    }

    /**
     * 에러 응답 반환
     */
    protected function errorResponse(
        string $message = null, 
        int $statusCode = 400,
        $errors = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::error($message, $statusCode, $errors, $meta);
    }

    /**
     * 페이지네이션 응답 반환
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::paginated($paginator, $message, $meta);
    }

    /**
     * 리소스 컬렉션 페이지네이션 응답 반환
     */
    protected function paginatedResourceResponse(
        AnonymousResourceCollection $collection,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::paginatedResource($collection, $message, $meta);
    }

    /**
     * 생성 성공 응답 반환
     */
    protected function createdResponse(
        $data = null, 
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::created($data, $message, $meta);
    }

    /**
     * 수정 성공 응답 반환
     */
    protected function updatedResponse(
        $data = null, 
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::updated($data, $message, $meta);
    }

    /**
     * 삭제 성공 응답 반환
     */
    protected function deletedResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::deleted($message, $meta);
    }

    /**
     * 404 Not Found 응답 반환
     */
    protected function notFoundResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::notFound($message, $meta);
    }

    /**
     * 401 Unauthorized 응답 반환
     */
    protected function unauthorizedResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::unauthorized($message, $meta);
    }

    /**
     * 403 Forbidden 응답 반환
     */
    protected function forbiddenResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::forbidden($message, $meta);
    }

    /**
     * 422 Validation Error 응답 반환
     */
    protected function validationErrorResponse(
        $errors = null,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::validationError($errors, $message, $meta);
    }

    /**
     * 500 Internal Server Error 응답 반환
     */
    protected function serverErrorResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::serverError($message, $meta);
    }

    /**
     * 204 No Content 응답 반환
     */
    protected function noContentResponse(): JsonResponse
    {
        return ApiResponseHelper::noContent();
    }

    /**
     * 컨텐츠 없음 응답 반환 (200 with empty data)
     */
    protected function noDataResponse(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponseHelper::noData($message, $meta);
    }

    /**
     * 페이지네이션 파라미터 검증 및 반환
     */
    protected function getPaginationParams(Request $request): array
    {
        $perPage = (int) $request->input('per_page', $this->perPage);
        $page = (int) $request->input('page', 1);

        // 한 페이지당 최대 아이템 수 제한
        if ($perPage > $this->maxPerPage) {
            $perPage = $this->maxPerPage;
        }

        // 최소값 검증
        if ($perPage < 1) {
            $perPage = $this->perPage;
        }

        if ($page < 1) {
            $page = 1;
        }

        return [
            'per_page' => $perPage,
            'page' => $page,
        ];
    }

    /**
     * 검색 조건 적용
     */
    protected function applySearchConditions(Builder $query, Request $request): Builder
    {
        // 기본 검색 조건 (하위 클래스에서 오버라이드)
        return $query;
    }

    /**
     * 정렬 조건 적용
     */
    protected function applySortConditions(Builder $query, Request $request): Builder
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // 허용된 정렬 필드인지 확인 (하위 클래스에서 오버라이드)
        if ($this->isValidSortField($sortBy)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }

    /**
     * 유효한 정렬 필드인지 확인
     */
    protected function isValidSortField(string $field): bool
    {
        // 기본 허용 필드 (하위 클래스에서 오버라이드)
        $allowedFields = ['id', 'created_at', 'updated_at'];
        return in_array($field, $allowedFields);
    }

    /**
     * 입력 데이터 검증
     */
    protected function validateInput(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 사용자 권한 확인
     */
    protected function checkUserPermission(string $permission): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // 관리자는 모든 권한 허용
        if ($user->isAdmin()) {
            return true;
        }

        // 권한 로직 구현 (하위 클래스에서 오버라이드)
        return $this->hasPermission($user, $permission);
    }

    /**
     * 특정 권한 확인 (하위 클래스에서 구현)
     */
    protected function hasPermission($user, string $permission): bool
    {
        return false;
    }

    /**
     * 모델 존재 확인
     */
    protected function findModelOrFail(string $modelClass, $id): Model
    {
        $model = $modelClass::find($id);
        
        if (!$model) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $model;
    }

    /**
     * 소프트 삭제 확인
     */
    protected function checkSoftDeleted(Model $model): bool
    {
        return method_exists($model, 'trashed') && $model->trashed();
    }

    /**
     * 현재 인증된 사용자 반환
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * 요청 IP 주소 반환
     */
    protected function getRequestIP(Request $request): string
    {
        return $request->ip();
    }

    /**
     * 사용자 에이전트 반환
     */
    protected function getUserAgent(Request $request): string
    {
        return $request->userAgent() ?? '';
    }

    /**
     * 로그 컨텍스트 생성
     */
    protected function createLogContext(Request $request, array $additional = []): array
    {
        $user = $this->getCurrentUser();

        return array_merge([
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'ip_address' => $this->getRequestIP($request),
            'user_agent' => $this->getUserAgent($request),
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->method(),
        ], $additional);
    }
}
