<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use Illuminate\Support\Carbon;



class quantityReminder extends Notification
{
    use Queueable;

    public $product;
    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
{
    return [
        'title' => $this->product->quantity == 1 ? "الكمية على وشك النفاد" : "الكمية نفدت" ,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'quantity' => $this->product->quantity,
        'url' => route('products.show', $this->product->id),
    ];
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

    public function databaseType(object $notifiable): string
{
    return 'Quantity_Reminder';
}

    public function initialDatabaseReadAtValue(): ?Carbon
{
    return null;
}
}
