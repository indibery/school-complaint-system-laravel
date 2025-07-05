<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class UserRelationshipRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 관리자만 사용자 관계 설정 가능
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'related_user_id' => 'required|integer|exists:users,id',
            'relationship_type' => [
                'required',
                'string',
                Rule::in(['parent_child', 'teacher_student', 'homeroom_teacher'])
            ],
            'is_primary' => 'boolean',
            'metadata' => 'nullable|array',
            'metadata.notes' => 'nullable|string|max:500',
            'metadata.start_date' => 'nullable|date',
            'metadata.end_date' => 'nullable|date|after_or_equal:metadata.start_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'related_user_id.required' => '관련 사용자는 필수 항목입니다.',
            'related_user_id.exists' => '존재하지 않는 사용자입니다.',
            'relationship_type.required' => '관계 유형은 필수 항목입니다.',
            'relationship_type.in' => '관계 유형은 학부모-자녀, 교사-학생, 담임교사 중 하나여야 합니다.',
            'is_primary.boolean' => '주 관계 여부는 참/거짓 값이어야 합니다.',
            'metadata.end_date.after_or_equal' => '종료일은 시작일 이후여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'related_user_id' => '관련 사용자',
            'metadata.start_date' => '시작일',
            'metadata.end_date' => '종료일',
            'metadata.notes' => '비고',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_primary' => $this->input('is_primary', false),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 자기 자신과 관계 설정 불가
            if ($this->input('related_user_id') === $this->route('user')->id) {
                $validator->errors()->add('related_user_id', '자기 자신과는 관계를 설정할 수 없습니다.');
            }
        });
    }
}
