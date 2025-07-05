<?php

namespace App\Http\Requests\Api\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 권한 체크는 컨트롤러에서 수행
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')->ignore($this->department->id),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('departments', 'code')->ignore($this->department->id),
            ],
            'description' => 'nullable|string|max:500',
            'head_id' => 'nullable|integer|exists:users,id',
            'status' => 'required|in:active,inactive',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => '부서명을 입력해주세요.',
            'name.max' => '부서명은 100자를 초과할 수 없습니다.',
            'name.unique' => '이미 존재하는 부서명입니다.',
            'code.required' => '부서 코드를 입력해주세요.',
            'code.max' => '부서 코드는 20자를 초과할 수 없습니다.',
            'code.unique' => '이미 존재하는 부서 코드입니다.',
            'description.max' => '부서 설명은 500자를 초과할 수 없습니다.',
            'head_id.exists' => '존재하지 않는 사용자입니다.',
            'status.in' => '상태는 active 또는 inactive여야 합니다.',
            'contact_email.email' => '올바른 이메일 형식을 입력해주세요.',
            'contact_phone.max' => '연락처는 20자를 초과할 수 없습니다.',
            'location.max' => '위치는 255자를 초과할 수 없습니다.',
            'budget.numeric' => '예산은 숫자여야 합니다.',
            'budget.min' => '예산은 0 이상이어야 합니다.',
            'established_date.date' => '설립일은 유효한 날짜여야 합니다.',
        ];
    }
}
