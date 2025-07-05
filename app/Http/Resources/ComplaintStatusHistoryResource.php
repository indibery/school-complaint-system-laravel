<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ComplaintStatusHistoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'complaint_id' => $this->complaint_id,
            'from_status' => $this->from_status,
            'from_status_display' => $this->formatStatus($this->from_status),
            'to_status' => $this->to_status,
            'to_status_display' => $this->formatStatus($this->to_status),
            'reason' => $this->reason,
            'notes' => $this->notes,
            
            // 변경자 정보
            'changed_by' => $this->whenLoaded('changedBy', function () {
                return UserResource::summary($this->changedBy);
            }),
            
            // 타임스탬프
            'changed_at' => $this->formatDateTime($this->created_at),
            'changed_at_human' => $this->formatDateForHumans($this->created_at),
        ];
    }

    /**
     * 간단한 상태 히스토리 반환
     */
    public static function simple($resource): array
    {
        $instance = new self($resource);
        
        return [
            'from_status' => $instance->formatStatus($resource->from_status),
            'to_status' => $instance->formatStatus($resource->to_status),
            'changed_by' => $resource->changedBy?->name ?? '시스템',
            'changed_at' => $instance->formatDateTime($resource->created_at),
            'reason' => $resource->reason,
        ];
    }
}
