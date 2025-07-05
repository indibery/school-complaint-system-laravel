<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiRequest;

class ChangePasswordRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
            'new_password_confirmation' => 'required|string|min:8',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => '현재 비밀번호를 입력해주세요.',
            'current_password.string' => '현재 비밀번호는 문자열이어야 합니다.',
            'new_password.required' => '새 비밀번호를 입력해주세요.',
            'new_password.string' => '새 비밀번호는 문자열이어야 합니다.',
            'new_password.min' => '새 비밀번호는 최소 8자 이상이어야 합니다.',
            'new_password.confirmed' => '새 비밀번호 확인이 일치하지 않습니다.',
            'new_password.different' => '새 비밀번호는 현재 비밀번호와 달라야 합니다.',
            'new_password_confirmation.required' => '새 비밀번호 확인을 입력해주세요.',
            'new_password_confirmation.string' => '새 비밀번호 확인은 문자열이어야 합니다.',
            'new_password_confirmation.min' => '새 비밀번호 확인은 최소 8자 이상이어야 합니다.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $newPassword = $this->input('new_password');
            
            // 비밀번호 복잡도 검증
            if ($newPassword) {
                $hasLower = preg_match('/[a-z]/', $newPassword);
                $hasUpper = preg_match('/[A-Z]/', $newPassword);
                $hasNumber = preg_match('/[0-9]/', $newPassword);
                $hasSpecial = preg_match('/[\W_]/', $newPassword);
                
                $complexityCount = $hasLower + $hasUpper + $hasNumber + $hasSpecial;
                
                if ($complexityCount < 3) {
                    $validator->errors()->add('new_password', '새 비밀번호는 소문자, 대문자, 숫자, 특수문자 중 최소 3종류를 포함해야 합니다.');
                }
                
                // 연속된 문자 확인
                if (preg_match('/(.)\1{2,}/', $newPassword)) {
                    $validator->errors()->add('new_password', '새 비밀번호에는 동일한 문자가 3번 이상 연속으로 올 수 없습니다.');
                }
                
                // 순차적인 문자 확인
                if (preg_match('/(?:abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz|123|234|345|456|567|678|789|890)/i', $newPassword)) {
                    $validator->errors()->add('new_password', '새 비밀번호에는 순차적인 문자나 숫자를 사용할 수 없습니다.');
                }
            }
        });
    }
}
