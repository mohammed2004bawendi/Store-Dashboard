<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class quantityReminder extends Notification
{
    public $product;

    /**
     * Constructor - store the product in the notification.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Define which channels this notification will use.
     * Here we only use the "database" channel.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Define queue names for each channel.
     * This allows us to prioritize this notification.
     * In this case, database notifications will go to the "high" queue.
     */
    public function viaQueues(): array
    {
        return [
            'database' => 'high', // High priority queue
        ];
    }

    /**
     * Example mail method (not used here since via() doesn't return 'mail').
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Data that will be stored in the "database" notifications table.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->product->quantity == 1
                ? 'Stock is about to run out'
                : 'Stock is out',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => $this->product->quantity,
            'url' => route('products.show', $this->product->id),
        ];
    }

    /**
     * Array representation (used for broadcast or JSON responses).
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    /**
     * Custom database type name for this notification.
     */
    public function databaseType(object $notifiable): string
    {
        return 'Quantity_Reminder';
    }

    /**
     * Set initial "read_at" value for the database record.
     */
    public function initialDatabaseReadAtValue(): ?Carbon
    {
        return null;
    }
}
