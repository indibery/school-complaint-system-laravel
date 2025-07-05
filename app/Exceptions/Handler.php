<?php

namespace App\Exceptions;

use App\Http\Helpers\ApiResponseHelper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // API 요청인 경우 JSON 응답 반환
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * API 예외 처리
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // 인증 오류
        if ($e instanceof AuthenticationException) {
            return ApiResponseHelper::unauthorized('인증이 필요합니다.');
        }

        // 권한 오류
        if ($e instanceof AuthorizationException) {
            return ApiResponseHelper::forbidden('접근 권한이 없습니다.');
        }

        // Sanctum 권한 오류
        if ($e instanceof MissingAbilityException) {
            return ApiResponseHelper::forbidden('필요한 권한이 없습니다.');
        }

        // 모델 찾을 수 없음
        if ($e instanceof ModelNotFoundException) {
            return ApiResponseHelper::notFound('요청한 리소스를 찾을 수 없습니다.');
        }

        // 404 오류
        if ($e instanceof NotFoundHttpException) {
            return ApiResponseHelper::notFound('요청한 페이지를 찾을 수 없습니다.');
        }

        // 401 오류
        if ($e instanceof UnauthorizedHttpException) {
            return ApiResponseHelper::unauthorized('인증이 필요합니다.');
        }

        // 403 오류
        if ($e instanceof AccessDeniedHttpException) {
            return ApiResponseHelper::forbidden('접근 권한이 없습니다.');
        }

        // 405 오류
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponseHelper::error('허용되지 않은 HTTP 메서드입니다.', 405);
        }

        // 429 오류
        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponseHelper::error('요청 한도를 초과했습니다.', 429);
        }

        // 유효성 검증 오류
        if ($e instanceof ValidationException) {
            return ApiResponseHelper::validationError(
                $e->errors(),
                '입력값 검증에 실패했습니다.'
            );
        }

        // 개발 환경에서는 상세 오류 정보 제공
        if (config('app.debug')) {
            return ApiResponseHelper::serverError(
                $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }

        // 프로덕션 환경에서는 일반적인 오류 메시지
        return ApiResponseHelper::serverError('서버 오류가 발생했습니다.');
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return ApiResponseHelper::unauthorized('인증이 필요합니다.');
        }

        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
