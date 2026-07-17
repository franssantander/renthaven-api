<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestStatusChangedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MaintenanceRequest $maintenanceRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $status = $this->maintenanceRequest->status->value;

        $message = (new MailMessage)
            ->subject("Maintenance Request Update: \"{$this->maintenanceRequest->title}\"")
            ->line("Your maintenance request \"{$this->maintenanceRequest->title}\" is now: " . str_replace('_', ' ', $status) . '.');

        if ($this->maintenanceRequest->resolution_notes) {
            $message->line("Notes: {$this->maintenanceRequest->resolution_notes}");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
