<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseApiController
{
    /**
     * 로그인
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        
        // Rate limiting 체크
        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->errorResponse(
                "로그인 시도가 너무 많습니다. {$seconds}초 후에 다시 시도해주세요.",
                429
            );
        }

        // 사용자 인증
        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60); // 1분 동안 제한
            
            Log::warning('Failed login attempt', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->errorResponse('이메일 또는 비밀번호가 올바르지 않습니다.', 401);
        }

        $user = Auth::user();

        // 비활성 사용자 확인
        if (!$user->isActive()) {
            return $this->errorResponse('비활성화된 계정입니다. 관리자에게 문의하세요.', 403);
        }

        // Rate limiting 초기화
        RateLimiter::clear($key);

        // 토큰 생성
        $deviceName = $request->header('User-Agent', 'unknown');
        $token = $user->createToken($deviceName, $this->getTokenAbilities($user));

        // 로그인 기록
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', null),
        ], '로그인에 성공했습니다.');
    }

    /**
     * 회원가입
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        
        // 비밀번호 해싱
        $validatedData['password'] = Hash::make($validatedData['password']);
        
        // 기본값 설정
        $validatedData['is_active'] = true;
        
        // 사용자 생성
        $user = User::create($validatedData);
        
        // 토큰 생성
        $deviceName = $request->header('User-Agent', 'unknown');
        $token = $user->createToken($deviceName, $this->getTokenAbilities($user));

        // 회원가입 기록
        Log::info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return $this->createdResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', null),
        ], '회원가입이 완료되었습니다.');
    }

    /**
     * 로그아웃
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 현재 토큰 삭제
        $request->user()->currentAccessToken()->delete();
        
        // 로그아웃 기록
        Log::info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, '로그아웃되었습니다.');
    }

    /**
     * 모든 기기에서 로그아웃
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 모든 토큰 삭제
        $user->tokens()->delete();
        
        // 로그아웃 기록
        Log::info('User logged out from all devices', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, '모든 기기에서 로그아웃되었습니다.');
    }

    /**
     * 프로필 조회
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->successResponse(
            new UserResource($user),
            '프로필 정보를 조회했습니다.'
        );
    }

    /**
     * 프로필 수정
     * 
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();
        
        // 프로필 업데이트
        $user->update($validatedData);
        
        // 프로필 수정 기록
        Log::info('User profile updated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'updated_fields' => array_keys($validatedData),
            'ip' => $request->ip(),
        ]);

        return $this->updatedResponse(
            new UserResource($user->fresh()),
            '프로필이 업데이트되었습니다.'
        );
    }

    /**
     * 비밀번호 변경
     * 
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();
        
        // 현재 비밀번호 확인
        if (!Hash::check($validatedData['current_password'], $user->password)) {
            return $this->errorResponse('현재 비밀번호가 올바르지 않습니다.', 400);
        }
        
        // 새 비밀번호로 업데이트
        $user->update([
            'password' => Hash::make($validatedData['new_password'])
        ]);
        
        // 비밀번호 변경 기록
        Log::info('User password changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, '비밀번호가 변경되었습니다.');
    }

    /**
     * 토큰 갱신
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        
        // 새 토큰 생성
        $deviceName = $request->header('User-Agent', 'unknown');
        $newToken = $user->createToken($deviceName, $this->getTokenAbilities($user));
        
        // 기존 토큰 삭제
        $currentToken->delete();
        
        // 토큰 갱신 기록
        Log::info('Token refreshed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'access_token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', null),
        ], '토큰이 갱신되었습니다.');
    }

    /**
     * 활성 토큰 목록 조회
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function tokens(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $tokens = $user->tokens()->get()->map(function ($token) use ($request) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'created_at' => $token->created_at->diffForHumans(),
                'is_current' => $token->id === $request->user()->currentAccessToken()->id,
            ];
        });

        return $this->successResponse($tokens, '활성 토큰 목록을 조회했습니다.');
    }

    /**
     * 특정 토큰 삭제
     * 
     * @param Request $request
     * @param string $tokenId
     * @return JsonResponse
     */
    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $user = $request->user();
        
        $token = $user->tokens()->where('id', $tokenId)->first();
        
        if (!$token) {
            return $this->notFoundResponse('토큰을 찾을 수 없습니다.');
        }
        
        // 현재 토큰은 삭제할 수 없음
        if ($token->id === $request->user()->currentAccessToken()->id) {
            return $this->errorResponse('현재 사용 중인 토큰은 삭제할 수 없습니다.', 400);
        }
        
        $token->delete();
        
        // 토큰 삭제 기록
        Log::info('Token revoked', [
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, '토큰이 삭제되었습니다.');
    }

    /**
     * 계정 비활성화
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 관리자는 비활성화할 수 없음
        if ($user->isAdmin()) {
            return $this->errorResponse('관리자 계정은 비활성화할 수 없습니다.', 400);
        }
        
        // 계정 비활성화
        $user->update(['is_active' => false]);
        
        // 모든 토큰 삭제
        $user->tokens()->delete();
        
        // 비활성화 기록
        Log::info('User account deactivated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, '계정이 비활성화되었습니다.');
    }

    /**
     * 역할별 토큰 권한 반환
     * 
     * @param User $user
     * @return array
     */
    private function getTokenAbilities(User $user): array
    {
        return match($user->role) {
            'admin' => ['*'],
            'teacher' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write', 'students:read'],
            'parent' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
            'security_staff' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
            'ops_staff' => ['complaints:read', 'complaints:write', 'comments:read', 'comments:write'],
            default => ['profile:read'],
        };
    }
}
