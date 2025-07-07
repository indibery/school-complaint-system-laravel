<?php

namespace App\Http\Requests\Api\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintStoreRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'category_id' => 'required|integer|exists:categories,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'student_id' => 'nullable|integer|exists:students,id',
            'is_public' => 'nullable|boolean',
            'is_anonymous' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'incident_date' => 'nullable|date',
            'expected_completion_at' => 'nullable|date|after:now',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get sanitized data
     */
    public function sanitized(): array
    {
        $data = $this->validated();
        
        // Set defaults
        $data['status'] = 'pending';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['is_public'] = $data['is_public'] ?? true;
        $data['is_anonymous'] = $data['is_anonymous'] ?? false;
        
        return $data;
    }
}
