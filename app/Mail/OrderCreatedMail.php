<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public bool $adminCopy = false
    ) {
    }

    public function build(): self
    {
        $subject = $this->adminCopy
            ? "Новый заказ №{$this->order->id} с сайта"
            : "Ваш заказ №{$this->order->id} принят";

        return $this->subject($subject)
            ->view('emails.cart')
            ->with([
                'order' => $this->order,
                'adminCopy' => $this->adminCopy,
            ]);
    }
}
