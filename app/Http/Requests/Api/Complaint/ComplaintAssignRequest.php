<?php

namespace App\Http\Requests\Api\Complaint;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ComplaintAssignRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 관리자와 부서장만 민원 할당 가능
        return $this->user()->hasRole(['admin', 'teacher']) || 
               $this->user()->hasPermission('assign_complaints');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assigned_to' => 'required|integer|exists:users,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => [
                'nullable',
                'string',
                Rule::in(['low', 'normal', 'high', 'urgent'])
            ],
            'due_date' => 'nullable|date|after:today',
            'assignment_note' => 'required|string|max:500',
            'notify_assignee' => 'boolean',
            'notify_submitter' => 'boolean',
            'escalation_level' => 'nullable|integer|min:1|max:5',
            'requires_approval' => 'boolean',
            'auto_reassign_if_overdue' => 'boolean',
            'reassign_after_days' => 'nullable|integer|min:1|max:30',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'assigned_to.required' => '담당자는 필수 항목입니다.',
            'assigned_to.exists' => '존재하지 않는 담당자입니다.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'priority.in' => '우선순위는 낮음, 보통, 높음, 긴급 중 하나여야 합니다.',
            'due_date.after' => '처리 기한은 내일 이후여야 합니다.',
            'assignment_note.required' => '할당 메모는 필수 항목입니다.',
            'assignment_note.max' => '할당 메모는 500자 이하여야 합니다.',
            'escalation_level.min' => '에스컬레이션 레벨은 1 이상이어야 합니다.',
            'escalation_level.max' => '에스컬레이션 레벨은 5 이하여야 합니다.',
            'reassign_after_days.min' => '재할당 일수는 1일 이상이어야 합니다.',
            'reassign_after_days.max' => '재할당 일수는 30일 이하여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'assigned_to' => '담당자',
            'department_id' => '담당 부서',
            'priority' => '우선순위',
            'due_date' => '처리 기한',
            'assignment_note' => '할당 메모',
            'notify_assignee' => '담당자 알림',
            'notify_submitter' => '신청자 알림',
            'escalation_level' => '에스컬레이션 레벨',
            'requires_approval' => '승인 필요',
            'auto_reassign_if_overdue' => '기한 초과 시 자동 재할당',
            'reassign_after_days' => '재할당 일수',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'notify_assignee' => $this->input('notify_assignee', true),
            'notify_submitter' => $this->input('notify_submitter', true),
            'requires_approval' => $this->input('requires_approval', false),
            'auto_reassign_if_overdue' => $this->input('auto_reassign_if_overdue', false),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 자동 재할당 설정 시 재할당 일수 필수
            if ($this->input('auto_reassign_if_overdue')) {
                if (!$this->input('reassign_after_days')) {
                    $validator->errors()->add(
                        'reassign_after_days',
                        '자동 재할당 설정 시 재할당 일수를 입력해주세요.'
                    );
                }
            }
            
            // 할당받는 사용자가 활성 상태인지 확인
            $assignedUser = \App\Models\User::find($this->input('assigned_to'));
            if ($assignedUser && !$assignedUser->is_active) {
                $validator->errors()->add(
                    'assigned_to',
                    '비활성 상태인 사용자에게는 민원을 할당할 수 없습니다.'
                );
            }
            
            // 할당받는 사용자가 민원 처리 권한이 있는지 확인
            if ($assignedUser && !$assignedUser->hasRole(['admin', 'teacher', 'staff'])) {
                $validator->errors()->add(
                    'assigned_to',
                    '민원 처리 권한이 없는 사용자에게는 민원을 할당할 수 없습니다.'
                );
            }
            
            // 부서가 지정된 경우 담당자가 해당 부서에 속하는지 확인
            if ($this->input('department_id') && $assignedUser) {
                if ($assignedUser->department_id !== $this->input('department_id')) {
                    $validator->errors()->add(
                        'assigned_to',
                        '담당자가 지정된 부서에 속하지 않습니다.'
                    );
                }
            }
        });
    }
}
