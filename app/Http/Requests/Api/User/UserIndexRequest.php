<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 인증된 사용자만 사용자 목록 조회 가능
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(
            $this->getPaginationValidationRules(),
            $this->getSearchValidationRules(),
            $this->getDateRangeValidationRules(),
            [
                'role' => [
                    'nullable',
                    'string',
                    Rule::in(['admin', 'teacher', 'parent', 'staff', 'student'])
                ],
                'department_id' => 'nullable|integer|exists:departments,id',
                'grade' => 'nullable|integer|min:1|max:12',
                'class_number' => 'nullable|integer|min:1|max:20',
                'is_active' => 'nullable|boolean',
                'has_email' => 'nullable|boolean',
                'has_phone' => 'nullable|boolean',
                'with_metadata' => 'nullable|boolean',
                'created_after' => 'nullable|date',
                'created_before' => 'nullable|date|after_or_equal:created_after',
                'updated_after' => 'nullable|date',
                'updated_before' => 'nullable|date|after_or_equal:updated_after',
            ]
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'role.in' => '역할은 관리자, 교사, 학부모, 직원, 학생 중 하나여야 합니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'grade.min' => '학년은 1학년 이상이어야 합니다.',
            'grade.max' => '학년은 12학년 이하여야 합니다.',
            'class_number.min' => '반은 1반 이상이어야 합니다.',
            'class_number.max' => '반은 20반 이하여야 합니다.',
            'created_before.after_or_equal' => '생성 종료일은 생성 시작일 이후여야 합니다.',
            'updated_before.after_or_equal' => '수정 종료일은 수정 시작일 이후여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'has_email' => '이메일 보유 여부',
            'has_phone' => '전화번호 보유 여부',
            'with_metadata' => '메타데이터 포함 여부',
            'created_after' => '생성 시작일',
            'created_before' => '생성 종료일',
            'updated_after' => '수정 시작일',
            'updated_before' => '수정 종료일',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'sort_by' => $this->input('sort_by', 'created_at'),
            'sort_order' => $this->input('sort_order', 'desc'),
        ]);
    }
}
