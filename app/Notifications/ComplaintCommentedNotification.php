<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintCommentedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment->load(['complaint', 'user']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('민원에 새로운 댓글이 등록되었습니다')
            ->greeting('안녕하세요!')
            ->line("민원 '{$this->comment->complaint->title}'에 새로운 댓글이 등록되었습니다.")
            ->line('작성자: ' . $this->comment->user->name)
            ->line('댓글 내용: ' . \Str::limit($this->comment->content, 100))
            ->action('민원 확인하기', route('complaints.show', $this->comment->complaint))
            ->line('감사합니다.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint_commented',
            'complaint_id' => $this->comment->complaint_id,
            'comment_id' => $this->comment->id,
            'title' => $this->comment->complaint->title,
            'message' => "{$this->comment->user->name}님이 댓글을 남겼습니다",
            'comment_excerpt' => \Str::limit($this->comment->content, 100),
            'commenter' => $this->comment->user->name,
            'created_at' => $this->comment->created_at->toIso8601String(),
            'url' => route('complaints.show', $this->comment->complaint) . '#comment-' . $this->comment->id
        ];
    }
}
