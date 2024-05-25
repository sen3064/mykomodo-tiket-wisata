<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $connection="kabtour_db";
    protected $table      = 'bravo_bookings';
    const DRAFT      = 'draft'; // New booking, before payment processing
    const UNPAID     = 'unpaid'; // Require payment
    const PROCESSING = 'processing'; // like offline - payment
    const CONFIRMED  = 'confirmed'; // after processing -> confirmed (for offline payment)
    const COMPLETED  = 'completed'; //
    const CANCELLED  = 'cancelled';
    const PAID       = 'paid'; //
    const PARTIAL_PAYMENT       = 'partial_payment'; //
}
