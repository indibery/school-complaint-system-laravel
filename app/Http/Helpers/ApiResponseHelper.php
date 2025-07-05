<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiResponseHelper
{
    /**
     * 성공 응답 반환
     */
    public static function success(
        $data = null, 
        string $message = null, 
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message ?? __('api.success'),
            'data' => $data,
        ];

        // 메타 데이터가 있으면 추가
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 에러 응답 반환
     */
    public static function error(
        string $message = null, 
        int $statusCode = 400,
        $errors = null,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message ?? __('api.bad_request'),
            'data' => null,
        ];

        // 에러 상세 정보가 있으면 추가
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // 메타 데이터가 있으면 추가
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 페이지네이션 응답 반환
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message ?? __('api.success'),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        // 메타 데이터가 있으면 추가
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, 200);
    }

    /**
     * 리소스 컬렉션 페이지네이션 응답 반환
     */
    public static function paginatedResource(
        AnonymousResourceCollection $collection,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        $response = $collection->response()->getData(true);
        
        // 표준 응답 포맷으로 변환
        $standardResponse = [
            'success' => true,
            'message' => $message ?? __('api.success'),
            'data' => $response['data'],
        ];

        // 페이지네이션 정보가 있으면 추가
        if (isset($response['links']) && isset($response['meta'])) {
            $standardResponse['pagination'] = [
                'current_page' => $response['meta']['current_page'],
                'per_page' => $response['meta']['per_page'],
                'total' => $response['meta']['total'],
                'last_page' => $response['meta']['last_page'],
                'from' => $response['meta']['from'],
                'to' => $response['meta']['to'],
                'has_more_pages' => $response['meta']['current_page'] < $response['meta']['last_page'],
            ];
        }

        // 메타 데이터가 있으면 추가
        if (!empty($meta)) {
            $standardResponse['meta'] = $meta;
        }

        return response()->json($standardResponse, 200);
    }

    /**
     * 생성 성공 응답 반환
     */
    public static function created(
        $data = null, 
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::success($data, $message ?? __('api.created'), 201, $meta);
    }

    /**
     * 수정 성공 응답 반환
     */
    public static function updated(
        $data = null, 
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::success($data, $message ?? __('api.updated'), 200, $meta);
    }

    /**
     * 삭제 성공 응답 반환
     */
    public static function deleted(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::success(null, $message ?? __('api.deleted'), 200, $meta);
    }

    /**
     * 404 Not Found 응답 반환
     */
    public static function notFound(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::error($message ?? __('api.not_found'), 404, null, $meta);
    }

    /**
     * 401 Unauthorized 응답 반환
     */
    public static function unauthorized(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::error($message ?? __('api.unauthorized'), 401, null, $meta);
    }

    /**
     * 403 Forbidden 응답 반환
     */
    public static function forbidden(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::error($message ?? __('api.forbidden'), 403, null, $meta);
    }

    /**
     * 422 Validation Error 응답 반환
     */
    public static function validationError(
        $errors = null,
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::error($message ?? __('api.validation_failed'), 422, $errors, $meta);
    }

    /**
     * 500 Internal Server Error 응답 반환
     */
    public static function serverError(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::error($message ?? __('api.server_error'), 500, null, $meta);
    }

    /**
     * 204 No Content 응답 반환
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * 컨텐츠 없음 응답 반환 (200 with empty data)
     */
    public static function noData(
        string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::success([], $message ?? __('api.no_content'), 200, $meta);
    }
}
