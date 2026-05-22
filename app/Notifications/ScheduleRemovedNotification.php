<?php

namespace App\Notifications;

use App\Models\ClassSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleRemovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ClassSchedule $schedule,
        private readonly string $action,
        private readonly ?string $reason = null,
        private readonly ?string $teacherName = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $teacherName = $this->teacherName ?? 'Unknown teacher';
        $scheduleLabel = trim(($this->schedule->subject ?? 'Schedule') . ' - ' . ($this->schedule->grade_level ?? '') . ' ' . ($this->schedule->section_name ?? ''));
        $timeLabel = trim(($this->schedule->day_of_week ?? 'Unknown day') . ' ' . ($this->schedule->start_time ? substr($this->schedule->start_time, 0, 5) : ''));

        return (new MailMessage)
            ->subject('Schedule removed from approved schedules')
            ->greeting('Hello Principal,')
            ->line('A schedule was removed from the approved schedules list.')
            ->line('Teacher: ' . $teacherName)
            ->line('Schedule: ' . $scheduleLabel)
            ->line('When: ' . $timeLabel)
            ->when($this->reason, function (MailMessage $message) {
                return $message->line('Reason: ' . $this->reason);
            })
            ->line('The weekly timetable and class schedule views were updated to match this removal.');
    }
}
