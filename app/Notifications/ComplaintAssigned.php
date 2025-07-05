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

    public $complaint;

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
                    ->subject('민원이 할당되었습니다 - ' . $this->complaint->complaint_number)
                    ->greeting('안녕하세요, ' . $notifiable->name . '님!')
                    ->line('새로운 민원이 귀하에게 할당되었습니다.')
                    ->line('민원번호: ' . $this->complaint->complaint_number)
                    ->line('제목: ' . $this->complaint->title)
                    ->line('우선순위: ' . $this->getPriorityLabel($this->complaint->priority))
                    ->action('민원 확인하기', route('complaints.show', $this->complaint))
                    ->line('신속한 처리 부탁드립니다.')
                    ->salutation('학교 민원 관리 시스템');
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
            'priority' => $this->complaint->priority,
            'assigned_at' => now(),
        ];
    }

    /**
     * 우선순위 라벨 반환
     */
    private function getPriorityLabel($priority): string
    {
        $labels = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급'
        ];

        return $labels[$priority] ?? $priority;
    }
}
