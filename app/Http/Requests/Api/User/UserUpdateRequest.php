<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        
        // 관리자는 모든 사용자 수정 가능
        if ($this->user()->hasRole('admin')) {
            return true;
        }
        
        // 본인만 수정 가능
        return $this->user()->id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => 'sometimes|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password|string|min:8',
            'phone' => 'nullable|string|max:20|regex:/^[0-9-+().\s]+$/',
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['admin', 'teacher', 'parent', 'staff', 'student'])
            ],
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_id')->ignore($userId)
            ],
            'student_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'student_id')->ignore($userId)
            ],
            'grade' => 'nullable|integer|min:1|max:12',
            'class_number' => 'nullable|integer|min:1|max:20',
            'department_id' => 'nullable|integer|exists:departments,id',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
            'metadata.homeroom_teacher' => 'nullable|boolean',
            'metadata.subject' => 'nullable|string|max:100',
            'metadata.hire_date' => 'nullable|date',
            'metadata.birth_date' => 'nullable|date|before:today',
            'metadata.gender' => 'nullable|string|in:male,female,other',
            'metadata.address' => 'nullable|string|max:500',
            'metadata.emergency_contact' => 'nullable|string|max:20',
            'metadata.emergency_contact_name' => 'nullable|string|max:100',
            'metadata.notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'role.required' => '역할은 필수 항목입니다.',
            'role.in' => '역할은 관리자, 교사, 학부모, 직원, 학생 중 하나여야 합니다.',
            'employee_id.unique' => '직원번호가 이미 사용 중입니다.',
            'student_id.unique' => '학번이 이미 사용 중입니다.',
            'grade.min' => '학년은 1학년 이상이어야 합니다.',
            'grade.max' => '학년은 12학년 이하여야 합니다.',
            'class_number.min' => '반은 1반 이상이어야 합니다.',
            'class_number.max' => '반은 20반 이하여야 합니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'phone.regex' => '전화번호 형식이 올바르지 않습니다.',
            'metadata.birth_date.before' => '생년월일은 오늘 이전이어야 합니다.',
            'metadata.gender.in' => '성별은 남성, 여성, 기타 중 하나여야 합니다.',
            'metadata.homeroom_teacher.boolean' => '담임교사 여부는 참/거짓 값이어야 합니다.',
            'required_with' => '비밀번호를 입력할 때 비밀번호 확인도 필요합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'employee_id' => '직원번호',
            'student_id' => '학번',
            'metadata.homeroom_teacher' => '담임교사 여부',
            'metadata.subject' => '담당과목',
            'metadata.hire_date' => '입사일',
            'metadata.birth_date' => '생년월일',
            'metadata.gender' => '성별',
            'metadata.address' => '주소',
            'metadata.emergency_contact' => '비상연락처',
            'metadata.emergency_contact_name' => '비상연락처 이름',
            'metadata.notes' => '비고',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 관리자가 아닌 경우 role 변경 불가
            if (!$this->user()->hasRole('admin') && $this->has('role')) {
                $validator->errors()->add('role', '역할은 관리자만 변경할 수 있습니다.');
            }

            // 학생인 경우 학번과 학년/반 필수
            if ($this->input('role') === 'student') {
                if ($this->has('student_id') && !$this->input('student_id')) {
                    $validator->errors()->add('student_id', '학생의 경우 학번은 필수입니다.');
                }
                if ($this->has('grade') && !$this->input('grade')) {
                    $validator->errors()->add('grade', '학생의 경우 학년은 필수입니다.');
                }
                if ($this->has('class_number') && !$this->input('class_number')) {
                    $validator->errors()->add('class_number', '학생의 경우 반은 필수입니다.');
                }
            }

            // 교사나 직원인 경우 직원번호 필수
            if (in_array($this->input('role'), ['teacher', 'staff'])) {
                if ($this->has('employee_id') && !$this->input('employee_id')) {
                    $validator->errors()->add('employee_id', '교사나 직원의 경우 직원번호는 필수입니다.');
                }
            }

            // 교사인 경우 부서 필수
            if ($this->input('role') === 'teacher') {
                if ($this->has('department_id') && !$this->input('department_id')) {
                    $validator->errors()->add('department_id', '교사의 경우 부서는 필수입니다.');
                }
            }
        });
    }
}
