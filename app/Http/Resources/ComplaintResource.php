<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'complaint_number' => $this->complaint_number,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ],
            'complainant' => [
                'id' => $this->complainant?->id,
                'name' => $this->is_anonymous ? '익명' : $this->complainant?->name,
                'email' => $this->is_anonymous ? null : $this->complainant?->email,
            ],
            'assigned_to' => [
                'id' => $this->assignedTo?->id,
                'name' => $this->assignedTo?->name,
                'email' => $this->assignedTo?->email,
            ],
            'student' => [
                'id' => $this->student?->id,
                'name' => $this->student?->name,
                'grade' => $this->student?->grade,
                'class' => $this->student?->class,
            ],
            'is_public' => $this->is_public,
            'is_anonymous' => $this->is_anonymous,
            'location' => $this->location,
            'incident_date' => $this->incident_date?->toDateString(),
            'expected_completion_at' => $this->expected_completion_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'satisfaction_rating' => $this->satisfaction_rating,
            'satisfaction_comment' => $this->satisfaction_comment,
            'attachments_count' => $this->whenCounted('attachments'),
            'comments_count' => $this->whenCounted('comments'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'status_history' => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
