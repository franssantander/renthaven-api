<?php

namespace App\Notifications;

use App\Models\LedgerEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueRentReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public LedgerEntry $ledgerEntry,
        public string $token,
    ) {
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
        $url = sprintf(
            '%s/portal/verify?token=%s',
            rtrim(config('app.frontend_url'), '/'),
            $this->token,
        );

        $daysOverdue = now()->diffInDays($this->ledgerEntry->due_date);

        return (new MailMessage)
            ->subject('Your Rent Payment Is Overdue')
            ->line("Your rent payment of {$this->ledgerEntry->amount} for the period {$this->ledgerEntry->period_start->toFormattedDateString()} - {$this->ledgerEntry->period_end->toFormattedDateString()} is now {$daysOverdue} day(s) overdue.")
            ->action('View My Ledger', $url)
            ->line('This link will expire in 15 minutes and can only be used once.')
            ->line('If you have already paid, please disregard this notice — your landlord will update your records shortly.');
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
