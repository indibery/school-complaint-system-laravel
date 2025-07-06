<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $complaint;

    /**
     * Create a new notification instance.
     */
    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
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
            ->subject('새로운 민원이 접수되었습니다')
            ->greeting('안녕하세요!')
            ->line('새로운 민원이 접수되었습니다.')
            ->line('제목: ' . $this->complaint->title)
            ->line('카테고리: ' . $this->complaint->category->name)
            ->line('우선순위: ' . $this->complaint->priority_text)
            ->line('접수자: ' . $this->complaint->complainant->name)
            ->action('민원 확인하기', route('complaints.show', $this->complaint))
            ->line('빠른 확인과 처리 부탁드립니다.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint_created',
            'complaint_id' => $this->complaint->id,
            'title' => $this->complaint->title,
            'message' => "새로운 민원이 접수되었습니다: {$this->complaint->title}",
            'complainant' => $this->complaint->complainant->name,
            'category' => $this->complaint->category->name,
            'priority' => $this->complaint->priority,
            'created_at' => $this->complaint->created_at->toIso8601String(),
            'url' => route('complaints.show', $this->complaint)
        ];
    }
}
