<?php

namespace App\Events;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $complaint;
    public $assignedTo;
    public $assignedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint, User $assignedTo, User $assignedBy)
    {
        $this->complaint = $complaint->load(['complainant', 'category']);
        $this->assignedTo = $assignedTo;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('complaints'),
            new PrivateChannel('user.' . $this->assignedTo->id),
            new PrivateChannel('user.' . $this->complaint->complainant_id),
        ];
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
                'assigned_to' => $this->assignedTo->name,
                'assigned_by' => $this->assignedBy->name,
                'category' => $this->complaint->category->name,
                'priority' => $this->complaint->priority,
                'assigned_at' => now()->format('Y-m-d H:i:s'),
            ],
            'message' => "민원이 할당되었습니다: {$this->complaint->title}"
        ];
    }
}
