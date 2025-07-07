<?php

namespace App\Http\Requests\Api\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintStatusRequest extends FormRequest
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
            'status' => 'required|string|in:pending,assigned,in_progress,resolved,closed,cancelled',
            'reason' => 'required|string|max:500',
            'resolution_note' => 'nullable|string|max:1000',
            'resolution_category' => 'nullable|string|max:50',
            'satisfaction_survey' => 'nullable|boolean',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date' => 'nullable|date|after:now',
            'notify_submitter' => 'nullable|boolean',
        ];
    }
}
