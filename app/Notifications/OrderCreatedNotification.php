<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Constructor - store the order in the notification.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Define which channels this notification will use.
     * Here we only use the "mail" channel.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Define queue names for each channel.
     * In this case, mail notifications go to the "emails" queue.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails', // Email-specific queue
        ];
    }

    /**
     * Build the mail message that will be sent.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A new order has been created')
            ->greeting('Hello ' . $notifiable->name)
            ->line('A new order has been created with ID #' . $this->order->id)
            ->line('Invoice details are available.')
            ->action('View Invoice', route('orders.invoice', $this->order->id))
            ->line('Please check the dashboard to follow up.');
    }

    /**
     * Array representation (used for broadcast or JSON responses).
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
