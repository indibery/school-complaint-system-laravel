<?php

namespace App\Http\Requests\Api\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintIndexRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,assigned,in_progress,resolved,closed,cancelled',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
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
            'damage_amount_max' => 'nullable|numeric|min:0',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'only_my_complaints' => 'nullable|boolean',
            'only_assigned_to_me' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:id,complaint_number,title,status,priority,created_at,updated_at,due_date,resolved_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'with_statistics' => 'nullable|boolean',
        ];
    }
}
