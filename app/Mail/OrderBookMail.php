<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PDF;
use App\Models\Payment;

class OrderBookMail extends Mailable
{
    public Booking $booking;

    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($booking)
    {
        //
        $this->booking = $booking;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $payment = Payment::where('code',$this->booking->code)->first();
        $subject = 'Booking Confirmation '.$payment->code;
        $pdf = PDF::loadView('mails.booking-mail',['booking'=>$this->booking]);
        $pdf_name = 'Tagihan-'.$payment->bill_no.'.pdf';
        if($payment->status=='Payment Sukses'){
            $subject = 'Pembayaran Booking '.$payment->code;
            $pdf_name = 'Invoice_PBDIGI-'.$payment->bill_no.'.pdf';
        }
        return $this->subject($subject)
        ->view('mails.booking-mail')
        ->attachData($pdf->output(), $pdf_name);
    }
}
