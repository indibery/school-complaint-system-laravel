<?php

namespace App\Http\Requests\Api\Complaint;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ComplaintUpdateRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $complaint = $this->route('complaint');
        
        // 관리자는 모든 민원 수정 가능
        if ($this->user()->hasRole('admin')) {
            return true;
        }
        
        // 담당자는 자신이 할당받은 민원 수정 가능
        if ($this->user()->hasRole(['teacher', 'staff']) && $complaint->assigned_to === $this->user()->id) {
            return true;
        }
        
        // 작성자는 자신의 민원만 수정 가능 (접수 상태일 때만)
        return $complaint->created_by === $this->user()->id && $complaint->status === 'pending';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string|max:5000',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['low', 'normal', 'high', 'urgent'])
            ],
            'is_public' => 'boolean',
            'expected_resolution_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
            'related_complaint_id' => 'nullable|integer|exists:complaints,id',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array',
            'metadata.incident_date' => 'nullable|date|before_or_equal:today',
            'metadata.incident_time' => 'nullable|string|max:10',
            'metadata.witness_count' => 'nullable|integer|min:0|max:100',
            'metadata.damage_amount' => 'nullable|numeric|min:0|max:999999999',
            'metadata.urgency_reason' => 'nullable|string|max:500',
            'metadata.contact_phone' => 'nullable|string|max:20|regex:/^[0-9-+().\s]+$/',
            'metadata.contact_email' => 'nullable|email|max:255',
            'metadata.preferred_contact_method' => 'nullable|string|in:email,phone,sms,visit',
            'metadata.external_reference' => 'nullable|string|max:100',
            'metadata.resolution_note' => 'nullable|string|max:1000',
            'metadata.admin_memo' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'integer|exists:attachments,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'title.required' => '제목은 필수 항목입니다.',
            'title.max' => '제목은 255자 이하여야 합니다.',
            'content.required' => '내용은 필수 항목입니다.',
            'content.max' => '내용은 5000자 이하여야 합니다.',
            'category_id.required' => '카테고리는 필수 항목입니다.',
            'category_id.exists' => '존재하지 않는 카테고리입니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'priority.required' => '우선순위는 필수 항목입니다.',
            'priority.in' => '우선순위는 낮음, 보통, 높음, 긴급 중 하나여야 합니다.',
            'expected_resolution_date.after' => '예상 해결일은 내일 이후여야 합니다.',
            'location.max' => '위치는 255자 이하여야 합니다.',
            'related_complaint_id.exists' => '존재하지 않는 관련 민원입니다.',
            'tags.max' => '태그는 최대 10개까지 입니다.',
            'tags.*.max' => '각 태그는 50자 이하여야 합니다.',
            'metadata.incident_date.before_or_equal' => '사건 발생일은 오늘 이전이어야 합니다.',
            'metadata.witness_count.min' => '목격자 수는 0 이상이어야 합니다.',
            'metadata.witness_count.max' => '목격자 수는 100명 이하여야 합니다.',
            'metadata.damage_amount.min' => '피해 금액은 0 이상이어야 합니다.',
            'metadata.damage_amount.max' => '피해 금액은 999,999,999원 이하여야 합니다.',
            'metadata.contact_phone.regex' => '연락처 형식이 올바르지 않습니다.',
            'metadata.contact_email.email' => '이메일 형식이 올바르지 않습니다.',
            'metadata.preferred_contact_method.in' => '선호 연락 방법은 이메일, 전화, SMS, 방문 중 하나여야 합니다.',
            'metadata.resolution_note.max' => '해결 메모는 1000자 이하여야 합니다.',
            'metadata.admin_memo.max' => '관리자 메모는 1000자 이하여야 합니다.',
            'attachments.max' => '첨부파일은 최대 10개까지 업로드할 수 있습니다.',
            'attachments.*.max' => '각 첨부파일은 10MB 이하여야 합니다.',
            'attachments.*.mimes' => '첨부파일은 jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt 형식만 가능합니다.',
            'remove_attachments.*.exists' => '삭제할 첨부파일이 존재하지 않습니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'category_id' => '카테고리',
            'department_id' => '담당 부서',
            'priority' => '우선순위',
            'is_public' => '공개 여부',
            'expected_resolution_date' => '예상 해결일',
            'location' => '위치',
            'related_complaint_id' => '관련 민원',
            'tags' => '태그',
            'metadata.incident_date' => '사건 발생일',
            'metadata.incident_time' => '사건 발생시간',
            'metadata.witness_count' => '목격자 수',
            'metadata.damage_amount' => '피해 금액',
            'metadata.urgency_reason' => '긴급 사유',
            'metadata.contact_phone' => '연락처',
            'metadata.contact_email' => '이메일',
            'metadata.preferred_contact_method' => '선호 연락 방법',
            'metadata.external_reference' => '외부 참조번호',
            'metadata.resolution_note' => '해결 메모',
            'metadata.admin_memo' => '관리자 메모',
            'attachments' => '첨부파일',
            'remove_attachments' => '삭제할 첨부파일',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 긴급 우선순위인 경우 사유 필수
            if ($this->input('priority') === 'urgent') {
                if (!$this->input('metadata.urgency_reason')) {
                    $validator->errors()->add(
                        'metadata.urgency_reason',
                        '긴급 민원의 경우 긴급 사유를 입력해주세요.'
                    );
                }
            }

            // 피해 금액이 있는 경우 관련 정보 검증
            if ($this->input('metadata.damage_amount') > 0) {
                if (!$this->input('metadata.incident_date')) {
                    $validator->errors()->add(
                        'metadata.incident_date',
                        '피해 금액이 있는 경우 사건 발생일을 입력해주세요.'
                    );
                }
            }

            // 관리자가 아닌 경우 일부 필드 수정 제한
            if (!$this->user()->hasRole('admin')) {
                $restrictedFields = ['metadata.admin_memo'];
                
                foreach ($restrictedFields as $field) {
                    if ($this->has($field)) {
                        $validator->errors()->add(
                            $field,
                            '해당 필드는 관리자만 수정할 수 있습니다.'
                        );
                    }
                }
            }
        });
    }
}
