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

class ComplaintCommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $complaint;
    public $comment;
    public $author;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint, $comment, User $author)
    {
        $this->complaint = $complaint->load(['complainant', 'assignedTo']);
        $this->comment = $comment;
        $this->author = $author;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('complaint.' . $this->complaint->id),
            new PrivateChannel('user.' . $this->complaint->complainant_id),
        ];
        
        // 담당자가 있고 댓글 작성자가 아닌 경우 알림
        if ($this->complaint->assigned_to && $this->complaint->assigned_to !== $this->author->id) {
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
            ],
            'comment' => [
                'id' => $this->comment->id ?? null,
                'content' => $this->comment->content ?? $this->comment,
                'author' => $this->author->name,
                'is_public' => $this->comment->is_public ?? true,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ],
            'message' => "새로운 댓글이 등록되었습니다: {$this->complaint->title}"
        ];
    }
}
