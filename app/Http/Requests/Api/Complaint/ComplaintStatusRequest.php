<?php

namespace App\Http\Requests\Api\Complaint;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ComplaintStatusRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $complaint = $this->route('complaint');
        
        // 관리자는 모든 민원 상태 변경 가능
        if ($this->user()->hasRole('admin')) {
            return true;
        }
        
        // 담당자는 자신이 할당받은 민원 상태 변경 가능
        if ($this->user()->hasRole(['teacher', 'staff']) && $complaint->assigned_to === $this->user()->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['pending', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'])
            ],
            'reason' => 'required|string|max:500',
            'resolution_note' => 'nullable|string|max:1000',
            'internal_memo' => 'nullable|string|max:1000',
            'notify_submitter' => 'boolean',
            'estimated_resolution_date' => 'nullable|date|after:today',
            'resolution_category' => [
                'nullable',
                'string',
                Rule::in(['solved', 'duplicate', 'invalid', 'withdrawn', 'escalated'])
            ],
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:today',
            'satisfaction_survey' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.required' => '상태는 필수 항목입니다.',
            'status.in' => '상태는 접수, 할당, 진행중, 해결, 완료, 취소 중 하나여야 합니다.',
            'reason.required' => '상태 변경 사유는 필수 항목입니다.',
            'reason.max' => '상태 변경 사유는 500자 이하여야 합니다.',
            'resolution_note.max' => '해결 메모는 1000자 이하여야 합니다.',
            'internal_memo.max' => '내부 메모는 1000자 이하여야 합니다.',
            'estimated_resolution_date.after' => '예상 해결일은 내일 이후여야 합니다.',
            'resolution_category.in' => '해결 분류는 해결됨, 중복, 무효, 철회, 상급이관 중 하나여야 합니다.',
            'follow_up_date.after' => '후속 조치일은 내일 이후여야 합니다.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'status' => '상태',
            'reason' => '변경 사유',
            'resolution_note' => '해결 메모',
            'internal_memo' => '내부 메모',
            'notify_submitter' => '신청자 알림',
            'estimated_resolution_date' => '예상 해결일',
            'resolution_category' => '해결 분류',
            'follow_up_required' => '후속 조치 필요',
            'follow_up_date' => '후속 조치일',
            'satisfaction_survey' => '만족도 조사',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'notify_submitter' => $this->input('notify_submitter', true),
            'follow_up_required' => $this->input('follow_up_required', false),
            'satisfaction_survey' => $this->input('satisfaction_survey', false),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $status = $this->input('status');
            
            // 해결 상태인 경우 해결 메모 필수
            if ($status === 'resolved') {
                if (!$this->input('resolution_note')) {
                    $validator->errors()->add(
                        'resolution_note',
                        '민원을 해결 상태로 변경할 때는 해결 메모가 필요합니다.'
                    );
                }
                
                if (!$this->input('resolution_category')) {
                    $validator->errors()->add(
                        'resolution_category',
                        '민원을 해결 상태로 변경할 때는 해결 분류가 필요합니다.'
                    );
                }
            }
            
            // 취소 상태인 경우 상세 사유 필수
            if ($status === 'cancelled') {
                if (strlen($this->input('reason', '')) < 20) {
                    $validator->errors()->add(
                        'reason',
                        '민원을 취소할 때는 20자 이상의 상세한 사유가 필요합니다.'
                    );
                }
            }
            
            // 후속 조치 필요한 경우 날짜 필수
            if ($this->input('follow_up_required')) {
                if (!$this->input('follow_up_date')) {
                    $validator->errors()->add(
                        'follow_up_date',
                        '후속 조치가 필요한 경우 후속 조치일을 입력해주세요.'
                    );
                }
            }
            
            // 현재 상태에서 변경 불가능한 상태 체크
            $complaint = $this->route('complaint');
            $currentStatus = $complaint->status;
            
            $invalidTransitions = [
                'closed' => ['pending', 'assigned', 'in_progress'],
                'cancelled' => ['pending', 'assigned', 'in_progress', 'resolved'],
            ];
            
            if (isset($invalidTransitions[$currentStatus]) && 
                in_array($status, $invalidTransitions[$currentStatus])) {
                $validator->errors()->add(
                    'status',
                    "현재 상태({$currentStatus})에서 선택한 상태({$status})로 변경할 수 없습니다."
                );
            }
        });
    }
}
