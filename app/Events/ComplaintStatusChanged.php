<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $complaint;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint, string $oldStatus, string $newStatus, $changedBy = null)
    {
        $this->complaint = $complaint->load(['complainant', 'category', 'assignedTo']);
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('complaints'),
            new PrivateChannel('user.' . $this->complaint->complainant_id),
        ];
        
        // 담당자가 있으면 해당 사용자 채널에도 브로드캐스트
        if ($this->complaint->assigned_to) {
            $channels[] = new PrivateChannel('user.' . $this->complaint->assigned_to);
        }
        
        return $channels;
    }
    
    /**
     * 브로드캐스트할 데이터
     */
    public function broadcastWith()
    {
        $statusLabels = [
            'pending' => '대기',
            'in_progress' => '처리중',
            'resolved' => '해결됨',
            'closed' => '종료'
        ];
        
        return [
            'complaint' => [
                'id' => $this->complaint->id,
                'title' => $this->complaint->title,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'old_status_label' => $statusLabels[$this->oldStatus] ?? $this->oldStatus,
                'new_status_label' => $statusLabels[$this->newStatus] ?? $this->newStatus,
                'changed_by' => $this->changedBy?->name ?? '시스템',
                'updated_at' => $this->complaint->updated_at->format('Y-m-d H:i:s'),
            ],
            'message' => "민원 상태가 변경되었습니다: {$this->complaint->title}"
        ];
    }
}
