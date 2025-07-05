<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * 사용자 인증 시도
     */
    public function attemptLogin(array $credentials, string $ipAddress): ?User
    {
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // 로그인 실패 로그
            Log::warning('Login attempt failed', [
                'email' => $credentials['email'],
                'ip' => $ipAddress,
                'reason' => 'invalid_credentials'
            ]);
            
            return null;
        }

        // 비활성 사용자 확인
        if (!$user->isActive()) {
            Log::warning('Login attempt failed', [
                'email' => $credentials['email'],
                'ip' => $ipAddress,
                'reason' => 'inactive_account'
            ]);
            
            return null;
        }

        return $user;
    }

    /**
     * 사용자 토큰 생성
     */
    public function createToken(User $user, string $deviceName = 'default'): PersonalAccessToken
    {
        $abilities = $this->getTokenAbilities($user);
        
        return $user->createToken($deviceName, $abilities);
    }

    /**
     * 역할별 토큰 권한 반환
     */
    public function getTokenAbilities(User $user): array
    {
        return match($user->role) {
            'admin' => ['*'],
            'teacher' => [
                'complaints:read',
                'complaints:write',
                'comments:read',
                'comments:write',
                'students:read',
                'categories:read',
                'departments:read',
                'attachments:read',
                'attachments:write',
                'dashboard:read',
            ],
            'parent' => [
                'complaints:read',
                'complaints:write',
                'comments:read',
                'comments:write',
                'categories:read',
                'departments:read',
                'attachments:read',
                'attachments:write',
            ],
            'security_staff' => [
                'complaints:read',
                'complaints:write',
                'comments:read',
                'comments:write',
                'categories:read',
                'departments:read',
                'attachments:read',
                'attachments:write',
                'dashboard:read',
            ],
            'ops_staff' => [
                'complaints:read',
                'complaints:write',
                'comments:read',
                'comments:write',
                'categories:read',
                'departments:read',
                'attachments:read',
                'attachments:write',
                'dashboard:read',
            ],
            default => ['profile:read'],
        };
    }

    /**
     * 비밀번호 복잡도 검증
     */
    public function validatePasswordComplexity(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = '비밀번호는 최소 8자 이상이어야 합니다.';
        }
        
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[\W_]/', $password);
        
        $complexityCount = $hasLower + $hasUpper + $hasNumber + $hasSpecial;
        
        if ($complexityCount < 3) {
            $errors[] = '비밀번호는 소문자, 대문자, 숫자, 특수문자 중 최소 3종류를 포함해야 합니다.';
        }
        
        // 연속된 문자 확인
        if (preg_match('/(.)\1{2,}/', $password)) {
            $errors[] = '비밀번호에는 동일한 문자가 3번 이상 연속으로 올 수 없습니다.';
        }
        
        // 순차적인 문자 확인
        if (preg_match('/(?:abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz|123|234|345|456|567|678|789|890)/i', $password)) {
            $errors[] = '비밀번호에는 순차적인 문자나 숫자를 사용할 수 없습니다.';
        }
        
        return $errors;
    }

    /**
     * Rate limiting 체크
     */
    public function checkRateLimit(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Rate limiting 적용
     */
    public function hitRateLimit(string $key, int $decayMinutes = 1): void
    {
        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Rate limiting 초기화
     */
    public function clearRateLimit(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Rate limiting 남은 시간 반환
     */
    public function getRateLimitTimeRemaining(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /**
     * 사용자 세션 정보 기록
     */
    public function logUserSession(User $user, string $action, array $context = []): void
    {
        Log::info("User {$action}", array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'action' => $action,
        ], $context));
    }

    /**
     * 토큰 만료 시간 반환
     */
    public function getTokenExpirationTime(): ?int
    {
        return config('sanctum.expiration');
    }

    /**
     * 사용자 계정 잠금
     */
    public function lockAccount(User $user, string $reason = 'security'): void
    {
        $user->update(['is_active' => false]);
        
        // 모든 토큰 삭제
        $user->tokens()->delete();
        
        Log::warning('User account locked', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
        ]);
    }

    /**
     * 사용자 계정 잠금 해제
     */
    public function unlockAccount(User $user): void
    {
        $user->update(['is_active' => true]);
        
        Log::info('User account unlocked', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * 토큰 정보 반환
     */
    public function getTokenInfo(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'created_at' => $token->created_at->toISOString(),
        ];
    }

    /**
     * 만료된 토큰 정리
     */
    public function cleanupExpiredTokens(): int
    {
        $count = PersonalAccessToken::where('expires_at', '<', now())->count();
        PersonalAccessToken::where('expires_at', '<', now())->delete();
        
        Log::info('Expired tokens cleaned up', ['count' => $count]);
        
        return $count;
    }
}
