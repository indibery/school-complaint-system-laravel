<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintAssigned extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('새로운 민원이 할당되었습니다')
            ->greeting('안녕하세요, ' . $notifiable->name . '님')
            ->line('새로운 민원이 귀하에게 할당되었습니다.')
            ->line('민원번호: ' . $this->complaint->complaint_number)
            ->line('제목: ' . $this->complaint->title)
            ->line('카테고리: ' . $this->complaint->category->name)
            ->line('우선순위: ' . $this->complaint->priority_text)
            ->action('민원 확인하기', route('complaints.show', $this->complaint))
            ->line('빠른 처리를 부탁드립니다.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'complaint_number' => $this->complaint->complaint_number,
            'title' => $this->complaint->title,
            'message' => '새로운 민원이 할당되었습니다: ' . $this->complaint->title,
            'url' => route('complaints.show', $this->complaint)
        ];
    }
}
