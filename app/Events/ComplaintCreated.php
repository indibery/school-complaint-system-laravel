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

class ComplaintCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $complaint;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint->load(['complainant', 'category', 'assignedTo']);
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
        return [
            'complaint' => [
                'id' => $this->complaint->id,
                'title' => $this->complaint->title,
                'content' => $this->complaint->content,
                'status' => $this->complaint->status,
                'priority' => $this->complaint->priority,
                'complainant' => $this->complaint->complainant->name,
                'category' => $this->complaint->category->name,
                'assigned_to' => $this->complaint->assignedTo?->name,
                'created_at' => $this->complaint->created_at->format('Y-m-d H:i:s'),
            ],
            'message' => "새로운 민원이 접수되었습니다: {$this->complaint->title}"
        ];
    }
}
