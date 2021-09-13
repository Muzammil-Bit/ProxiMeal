<?php

namespace App\Mail;

use App\Criteria\Orders\OrdersOfUserCriteria;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Repositories\OrderRepository;

class EmailOrder extends Mailable
{
    use Queueable, SerializesModels;

    /** @var  OrderRepository */
    private $orderRepository;

    public $subject = 'Order Notification';
    public $order;
    public $payment;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, OrderRepository $orderRepo, $payment)
    {
        $this->order = $order;
        $this->orderRepository = $orderRepo;
        $this->payment = $payment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = $this->orderRepository->findWithoutFail($this->order->id);
        $subtotal = 0;

        foreach ($order->foodOrders as $foodOrder) {
            foreach ($foodOrder->extras as $extra) {
                $foodOrder->price += $extra->price;
            }
            $subtotal += $foodOrder->price * $foodOrder->quantity;
        }

        $total = $subtotal + $order['delivery_fee'];
        $taxAmount = $total * $order['tax'] / 100;
        $total += $taxAmount;

        return $this->markdown('emails.order', ["order" => $order, "payment" => $this->payment, "total" => $total, "subtotal" => $subtotal,"taxAmount" => $taxAmount]);
    }
}