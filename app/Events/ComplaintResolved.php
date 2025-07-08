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

class ComplaintResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $complaint;
    public $resolvedBy;
    public $resolutionNote;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint, User $resolvedBy, string $resolutionNote = null)
    {
        $this->complaint = $complaint->load(['complainant', 'category', 'assignedTo']);
        $this->resolvedBy = $resolvedBy;
        $this->resolutionNote = $resolutionNote;
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
            new PrivateChannel('user.' . $this->complaint->complainant_id),
            new PrivateChannel('user.' . $this->complaint->assigned_to),
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
                'category' => $this->complaint->category->name,
                'resolved_by' => $this->resolvedBy->name,
                'resolution_note' => $this->resolutionNote,
                'resolved_at' => now()->format('Y-m-d H:i:s'),
            ],
            'message' => "민원이 해결되었습니다: {$this->complaint->title}"
        ];
    }
}
