<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiRequest;

class UpdateProfileRequest extends BaseApiRequest
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
        $user = $this->user();
        
        return [
            'name' => 'sometimes|string|max:255|regex:/^[가-힣a-zA-Z\s]+$/',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'grade' => 'sometimes|nullable|integer|min:1|max:6',
            'class_number' => 'sometimes|nullable|integer|min:1|max:20',
            'subject' => 'sometimes|nullable|string|max:100',
            'department' => 'sometimes|nullable|string|max:100',
            'phone' => 'sometimes|nullable|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => '이름은 문자열이어야 합니다.',
            'name.max' => '이름은 255자 이하로 입력해주세요.',
            'name.regex' => '이름은 한글, 영문, 공백만 입력 가능합니다.',
            'email.string' => '이메일은 문자열이어야 합니다.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.max' => '이메일은 255자 이하로 입력해주세요.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'grade.integer' => '학년은 숫자여야 합니다.',
            'grade.min' => '학년은 1 이상이어야 합니다.',
            'grade.max' => '학년은 6 이하여야 합니다.',
            'class_number.integer' => '반은 숫자여야 합니다.',
            'class_number.min' => '반은 1 이상이어야 합니다.',
            'class_number.max' => '반은 20 이하여야 합니다.',
            'subject.string' => '과목은 문자열이어야 합니다.',
            'subject.max' => '과목은 100자 이하로 입력해주세요.',
            'department.string' => '부서는 문자열이어야 합니다.',
            'department.max' => '부서는 100자 이하로 입력해주세요.',
            'phone.string' => '전화번호는 문자열이어야 합니다.',
            'phone.regex' => '올바른 휴대폰 번호 형식을 입력해주세요. (예: 010-1234-5678)',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            
            // 교사인 경우 학년과 반 정보 필수
            if ($user->role === 'teacher') {
                if ($this->has('grade') && empty($this->input('grade'))) {
                    $validator->errors()->add('grade', '교사는 담당 학년을 입력해야 합니다.');
                }
                if ($this->has('class_number') && empty($this->input('class_number'))) {
                    $validator->errors()->add('class_number', '교사는 담당 반을 입력해야 합니다.');
                }
            }

            // 직원인 경우 부서 정보 필수
            if (in_array($user->role, ['security_staff', 'ops_staff'])) {
                if ($this->has('department') && empty($this->input('department'))) {
                    $validator->errors()->add('department', '직원은 소속 부서를 입력해야 합니다.');
                }
            }
        });
    }
}
