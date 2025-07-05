<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ComplaintCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($complaint) {
                return ComplaintResource::summary($complaint);
            }),
            'meta' => [
                'total' => $this->collection->count(),
                'status_counts' => $this->getStatusCounts(),
                'priority_counts' => $this->getPriorityCounts(),
                'urgent_count' => $this->collection->where('is_urgent', true)->count(),
                'overdue_count' => $this->collection->where('is_overdue', true)->count(),
                'anonymous_count' => $this->collection->where('is_anonymous', true)->count(),
            ],
        ];
    }

    /**
     * 상태별 민원 수 계산
     */
    protected function getStatusCounts(): array
    {
        $counts = [];
        $statuses = ['pending', 'acknowledged', 'in_progress', 'resolved', 'closed', 'cancelled'];
        
        foreach ($statuses as $status) {
            $counts[$status] = $this->collection->where('status', $status)->count();
        }
        
        return $counts;
    }

    /**
     * 우선순위별 민원 수 계산
     */
    protected function getPriorityCounts(): array
    {
        $counts = [];
        $priorities = ['low', 'medium', 'high', 'urgent', 'critical'];
        
        foreach ($priorities as $priority) {
            $counts[$priority] = $this->collection->where('priority', $priority)->count();
        }
        
        return $counts;
    }
}
