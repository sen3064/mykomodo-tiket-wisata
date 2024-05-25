<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomBooking extends Model
{
    use HasFactory;
    protected $connection = 'kabtour_db';
    protected $table = 'bravo_hotel_room_bookings';
}
