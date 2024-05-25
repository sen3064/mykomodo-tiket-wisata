<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PDF;
use App\Models\Payment;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        //
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $payment = Payment::where('code',$this->order->code_booking)->first();
        $subject = 'Order Confirmation '.$payment->code;
        $pdf = PDF::loadView('mails.order-mail',['order'=>$this->order]);
        $pdf_name = 'Tagihan-'.$payment->bill_no.'.pdf';
        if($payment->status=='Payment Sukses'){
            $subject = 'Pembayaran Order '.$payment->code;
            $pdf_name = 'Invoice_PBDIGI-'.$payment->bill_no.'.pdf';
        }
        return $this->subject($subject)
        ->view('mails.order-mail')
        ->attachData($pdf->output(), $pdf_name);
    }
}
