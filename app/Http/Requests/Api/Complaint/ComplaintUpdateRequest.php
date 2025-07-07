<?php

namespace App\Http\Requests\Api\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintUpdateRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'category_id' => 'nullable|integer|exists:categories,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'is_public' => 'nullable|boolean',
            'is_anonymous' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'incident_date' => 'nullable|date',
            'expected_completion_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'integer|exists:attachments,id',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get sanitized data
     */
    public function sanitized(): array
    {
        return $this->validated();
    }
}
