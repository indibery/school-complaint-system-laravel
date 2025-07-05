<?php

namespace App\Http\Requests\Api\Complaint;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ComplaintIndexRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 인증된 사용자만 민원 목록 조회 가능
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
                'status' => [
                    'nullable',
                    'string',
                    Rule::in(['pending', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'])
                ],
                'priority' => [
                    'nullable',
                    'string',
                    Rule::in(['low', 'normal', 'high', 'urgent'])
                ],
                'category_id' => 'nullable|integer|exists:categories,id',
                'department_id' => 'nullable|integer|exists:departments,id',
                'assigned_to' => 'nullable|integer|exists:users,id',
                'created_by' => 'nullable|integer|exists:users,id',
                'is_public' => 'nullable|boolean',
                'is_anonymous' => 'nullable|boolean',
                'has_attachments' => 'nullable|boolean',
                'overdue' => 'nullable|boolean',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'location' => 'nullable|string|max:255',
                'incident_date_from' => 'nullable|date',
                'incident_date_to' => 'nullable|date|after_or_equal:incident_date_from',
                'damage_amount_min' => 'nullable|numeric|min:0',
                'damage_amount_max' => 'nullable|numeric|min:0|gte:damage_amount_min',
                'resolution_category' => [
                    'nullable',
                    'string',
                    Rule::in(['solved', 'duplicate', 'invalid', 'withdrawn', 'escalated'])
                ],
                'with_statistics' => 'nullable|boolean',
                'only_my_complaints' => 'nullable|boolean',
                'only_assigned_to_me' => 'nullable|boolean',
                'include_related' => 'nullable|boolean',
                'group_by' => [
                    'nullable',
                    'string',
                    Rule::in(['status', 'priority', 'category', 'department', 'date'])
                ],
                'export_format' => [
                    'nullable',
                    'string',
                    Rule::in(['csv', 'xlsx', 'pdf'])
                ],
                'fields' => 'nullable|array',
                'fields.*' => 'string|max:100',
            ]
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.in' => '상태는 접수, 할당, 진행중, 해결, 완료, 취소 중 하나여야 합니다.',
            'priority.in' => '우선순위는 낮음, 보통, 높음, 긴급 중 하나여야 합니다.',
            'category_id.exists' => '존재하지 않는 카테고리입니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'assigned_to.exists' => '존재하지 않는 담당자입니다.',
            'created_by.exists' => '존재하지 않는 작성자입니다.',
            'tags.*.max' => '각 태그는 50자 이하여야 합니다.',
            'location.max' => '위치는 255자 이하여야 합니다.',
            'incident_date_to.after_or_equal' => '사건 발생 종료일은 시작일 이후여야 합니다.',
            'damage_amount_min.min' => '최소 피해 금액은 0 이상이어야 합니다.',
            'damage_amount_max.min' => '최대 피해 금액은 0 이상이어야 합니다.',
            'damage_amount_max.gte' => '최대 피해 금액은 최소 피해 금액보다 크거나 같아야 합니다.',
            'resolution_category.in' => '해결 분류는 해결됨, 중복, 무효, 철회, 상급이관 중 하나여야 합니다.',
            'group_by.in' => '그룹화는 상태, 우선순위, 카테고리, 부서, 날짜 중 하나여야 합니다.',
            'export_format.in' => '내보내기 형식은 CSV, Excel, PDF 중 하나여야 합니다.',
            'fields.*.max' => '각 필드명은 100자 이하여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'status' => '상태',
            'priority' => '우선순위',
            'category_id' => '카테고리',
            'department_id' => '담당 부서',
            'assigned_to' => '담당자',
            'created_by' => '작성자',
            'is_public' => '공개 여부',
            'is_anonymous' => '익명 여부',
            'has_attachments' => '첨부파일 여부',
            'overdue' => '기한 초과',
            'tags' => '태그',
            'location' => '위치',
            'incident_date_from' => '사건 발생 시작일',
            'incident_date_to' => '사건 발생 종료일',
            'damage_amount_min' => '최소 피해 금액',
            'damage_amount_max' => '최대 피해 금액',
            'resolution_category' => '해결 분류',
            'with_statistics' => '통계 포함',
            'only_my_complaints' => '내 민원만',
            'only_assigned_to_me' => '내 할당 민원만',
            'include_related' => '관련 민원 포함',
            'group_by' => '그룹화',
            'export_format' => '내보내기 형식',
            'fields' => '필드',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'per_page' => $this->input('per_page', 20),
            'page' => $this->input('page', 1),
            'sort_by' => $this->input('sort_by', 'created_at'),
            'sort_order' => $this->input('sort_order', 'desc'),
            'with_statistics' => $this->input('with_statistics', false),
            'only_my_complaints' => $this->input('only_my_complaints', false),
            'only_assigned_to_me' => $this->input('only_assigned_to_me', false),
            'include_related' => $this->input('include_related', false),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 내 민원만 조회와 내 할당 민원만 조회는 동시에 사용 불가
            if ($this->input('only_my_complaints') && $this->input('only_assigned_to_me')) {
                $validator->errors()->add(
                    'only_my_complaints',
                    '내 민원만 조회와 내 할당 민원만 조회는 동시에 사용할 수 없습니다.'
                );
            }
            
            // 내보내기 형식이 지정된 경우 관리자 권한 필요
            if ($this->input('export_format') && !$this->user()->hasRole('admin')) {
                $validator->errors()->add(
                    'export_format',
                    '데이터 내보내기는 관리자만 사용할 수 있습니다.'
                );
            }
            
            // 통계 포함 옵션은 관리자만 사용 가능
            if ($this->input('with_statistics') && !$this->user()->hasRole('admin')) {
                $validator->errors()->add(
                    'with_statistics',
                    '통계 포함 옵션은 관리자만 사용할 수 있습니다.'
                );
            }
        });
    }
}
