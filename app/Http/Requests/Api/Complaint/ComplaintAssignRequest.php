<?php

namespace App\Http\Requests\Api\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintAssignRequest extends FormRequest
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
        return [
            'assigned_to' => 'required|integer|exists:users,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'due_date' => 'nullable|date|after:now',
            'assignment_note' => 'required|string|max:500',
            'escalation_level' => 'nullable|integer|min:1|max:5',
            'requires_approval' => 'nullable|boolean',
            'auto_reassign_if_overdue' => 'nullable|boolean',
            'reassign_after_days' => 'nullable|integer|min:1|max:30',
            'notify_assignee' => 'nullable|boolean',
            'notify_submitter' => 'nullable|boolean',
        ];
    }
}
