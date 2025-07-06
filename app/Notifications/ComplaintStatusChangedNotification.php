<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $complaint;
    protected $oldStatus;
    protected $newStatus;
    protected $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Complaint $complaint, string $oldStatus, string $newStatus, $changedBy = null)
    {
        $this->complaint = $complaint;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
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
        $statusLabels = [
            'pending' => '대기',
            'in_progress' => '처리중',
            'resolved' => '해결됨',
            'closed' => '종료'
        ];
        
        $oldStatusLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newStatusLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;
        
        return (new MailMessage)
            ->subject('민원 상태가 변경되었습니다')
            ->greeting('안녕하세요!')
            ->line("민원 '{$this->complaint->title}'의 상태가 변경되었습니다.")
            ->line("변경 전: {$oldStatusLabel}")
            ->line("변경 후: {$newStatusLabel}")
            ->line("변경자: " . ($this->changedBy?->name ?? '시스템'))
            ->action('민원 확인하기', route('complaints.show', $this->complaint))
            ->line('감사합니다.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'pending' => '대기',
            'in_progress' => '처리중',
            'resolved' => '해결됨',
            'closed' => '종료'
        ];
        
        return [
            'type' => 'complaint_status_changed',
            'complaint_id' => $this->complaint->id,
            'title' => $this->complaint->title,
            'message' => "민원 상태가 '{$statusLabels[$this->oldStatus]}'에서 '{$statusLabels[$this->newStatus]}'로 변경되었습니다",
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy?->name ?? '시스템',
            'updated_at' => $this->complaint->updated_at->toIso8601String(),
            'url' => route('complaints.show', $this->complaint)
        ];
    }
}
