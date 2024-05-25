<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PDF;
use App\Models\Payment;

class OrderBoatMail extends Mailable
{
    public Booking $bookings;
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($bookings)
    {
        //
        $this->bookings = $bookings;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $payment = Payment::where('code',$this->bookings->code)->first();
        $subject = 'Booking Confirmation '.$payment->code;
        $pdf = PDF::loadView('mails.boat-mail',['bookings'=>$this->bookings]);
        $pdf_name = 'Tagihan-'.$payment->bill_no.'.pdf';
        if($payment->status=='Payment Sukses'){
            $subject = 'Pembayaran Booking '.$payment->code;
            $pdf_name = 'Invoice_PBDIGI-'.$payment->bill_no.'.pdf';
        }
        return $this->subject($subject)
        ->view('mails.boat-mail')
        ->attachData($pdf->output(), $pdf_name);
    }
}
