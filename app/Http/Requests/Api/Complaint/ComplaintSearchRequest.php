<?php

namespace App\Http\Requests\Api\Complaint;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ComplaintSearchRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 인증된 사용자만 민원 검색 가능
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:255',
            'search_fields' => 'nullable|array',
            'search_fields.*' => 'string|in:title,content,location,tags,complaint_number',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|array',
            'filters.status.*' => 'string|in:pending,assigned,in_progress,resolved,closed,cancelled',
            'filters.priority' => 'nullable|array',
            'filters.priority.*' => 'string|in:low,normal,high,urgent',
            'filters.categories' => 'nullable|array',
            'filters.categories.*' => 'integer|exists:categories,id',
            'filters.departments' => 'nullable|array',
            'filters.departments.*' => 'integer|exists:departments,id',
            'filters.assignees' => 'nullable|array',
            'filters.assignees.*' => 'integer|exists:users,id',
            'filters.submitters' => 'nullable|array',
            'filters.submitters.*' => 'integer|exists:users,id',
            'filters.date_range' => 'nullable|array',
            'filters.date_range.start' => 'nullable|date',
            'filters.date_range.end' => 'nullable|date|after_or_equal:filters.date_range.start',
            'filters.incident_date_range' => 'nullable|array',
            'filters.incident_date_range.start' => 'nullable|date',
            'filters.incident_date_range.end' => 'nullable|date|after_or_equal:filters.incident_date_range.start',
            'filters.damage_amount' => 'nullable|array',
            'filters.damage_amount.min' => 'nullable|numeric|min:0',
            'filters.damage_amount.max' => 'nullable|numeric|min:0|gte:filters.damage_amount.min',
            'filters.tags' => 'nullable|array',
            'filters.tags.*' => 'string|max:50',
            'filters.location' => 'nullable|string|max:255',
            'filters.has_attachments' => 'nullable|boolean',
            'filters.is_public' => 'nullable|boolean',
            'filters.is_anonymous' => 'nullable|boolean',
            'filters.is_overdue' => 'nullable|boolean',
            'filters.resolution_category' => 'nullable|array',
            'filters.resolution_category.*' => 'string|in:solved,duplicate,invalid,withdrawn,escalated',
            'sort' => 'nullable|array',
            'sort.field' => 'nullable|string|in:created_at,updated_at,title,priority,status,due_date,resolved_at',
            'sort.direction' => 'nullable|string|in:asc,desc',
            'pagination' => 'nullable|array',
            'pagination.page' => 'nullable|integer|min:1',
            'pagination.per_page' => 'nullable|integer|min:1|max:100',
            'aggregations' => 'nullable|array',
            'aggregations.*' => 'string|in:status,priority,category,department,date,resolution_category',
            'highlight' => 'nullable|boolean',
            'include_related' => 'nullable|boolean',
            'scope' => 'nullable|string|in:all,my_complaints,assigned_to_me,my_department',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'query.max' => '검색어는 255자 이하여야 합니다.',
            'search_fields.*.in' => '검색 필드는 제목, 내용, 위치, 태그, 민원번호 중 하나여야 합니다.',
            'filters.status.*.in' => '상태는 접수, 할당, 진행중, 해결, 완료, 취소 중 하나여야 합니다.',
            'filters.priority.*.in' => '우선순위는 낮음, 보통, 높음, 긴급 중 하나여야 합니다.',
            'filters.categories.*.exists' => '존재하지 않는 카테고리입니다.',
            'filters.departments.*.exists' => '존재하지 않는 부서입니다.',
            'filters.assignees.*.exists' => '존재하지 않는 담당자입니다.',
            'filters.submitters.*.exists' => '존재하지 않는 신청자입니다.',
            'filters.date_range.end.after_or_equal' => '종료일은 시작일 이후여야 합니다.',
            'filters.incident_date_range.end.after_or_equal' => '사건 종료일은 시작일 이후여야 합니다.',
            'filters.damage_amount.min.min' => '최소 피해 금액은 0 이상이어야 합니다.',
            'filters.damage_amount.max.gte' => '최대 피해 금액은 최소 피해 금액보다 크거나 같아야 합니다.',
            'filters.tags.*.max' => '각 태그는 50자 이하여야 합니다.',
            'filters.location.max' => '위치는 255자 이하여야 합니다.',
            'filters.resolution_category.*.in' => '해결 분류는 해결됨, 중복, 무효, 철회, 상급이관 중 하나여야 합니다.',
            'sort.field.in' => '정렬 필드는 생성일, 수정일, 제목, 우선순위, 상태, 기한, 해결일 중 하나여야 합니다.',
            'sort.direction.in' => '정렬 방향은 오름차순 또는 내림차순이어야 합니다.',
            'pagination.page.min' => '페이지 번호는 1 이상이어야 합니다.',
            'pagination.per_page.min' => '페이지당 항목 수는 1 이상이어야 합니다.',
            'pagination.per_page.max' => '페이지당 항목 수는 100 이하여야 합니다.',
            'aggregations.*.in' => '집계 필드는 상태, 우선순위, 카테고리, 부서, 날짜, 해결분류 중 하나여야 합니다.',
            'scope.in' => '범위는 전체, 내 민원, 내 할당, 내 부서 중 하나여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'query' => '검색어',
            'search_fields' => '검색 필드',
            'filters' => '필터',
            'filters.status' => '상태 필터',
            'filters.priority' => '우선순위 필터',
            'filters.categories' => '카테고리 필터',
            'filters.departments' => '부서 필터',
            'filters.assignees' => '담당자 필터',
            'filters.submitters' => '신청자 필터',
            'filters.date_range' => '날짜 범위',
            'filters.incident_date_range' => '사건 날짜 범위',
            'filters.damage_amount' => '피해 금액',
            'filters.tags' => '태그',
            'filters.location' => '위치',
            'filters.has_attachments' => '첨부파일 여부',
            'filters.is_public' => '공개 여부',
            'filters.is_anonymous' => '익명 여부',
            'filters.is_overdue' => '기한 초과 여부',
            'filters.resolution_category' => '해결 분류',
            'sort' => '정렬',
            'sort.field' => '정렬 필드',
            'sort.direction' => '정렬 방향',
            'pagination' => '페이지네이션',
            'pagination.page' => '페이지 번호',
            'pagination.per_page' => '페이지당 항목 수',
            'aggregations' => '집계',
            'highlight' => '하이라이트',
            'include_related' => '관련 민원 포함',
            'scope' => '범위',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'search_fields' => $this->input('search_fields', ['title', 'content']),
            'sort' => array_merge([
                'field' => 'created_at',
                'direction' => 'desc',
            ], $this->input('sort', [])),
            'pagination' => array_merge([
                'page' => 1,
                'per_page' => 20,
            ], $this->input('pagination', [])),
            'highlight' => $this->input('highlight', false),
            'include_related' => $this->input('include_related', false),
            'scope' => $this->input('scope', 'all'),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 검색어가 없으면 필터가 최소 하나는 있어야 함
            if (!$this->input('query') && !$this->input('filters')) {
                $validator->errors()->add(
                    'query',
                    '검색어 또는 필터 중 하나는 필수입니다.'
                );
            }
            
            // 범위 설정에 따른 권한 검증
            $scope = $this->input('scope');
            if ($scope === 'all' && !$this->user()->hasRole('admin')) {
                $validator->errors()->add(
                    'scope',
                    '전체 범위 검색은 관리자만 사용할 수 있습니다.'
                );
            }
            
            // 집계 기능은 관리자만 사용 가능
            if ($this->input('aggregations') && !$this->user()->hasRole('admin')) {
                $validator->errors()->add(
                    'aggregations',
                    '집계 기능은 관리자만 사용할 수 있습니다.'
                );
            }
        });
    }
}
