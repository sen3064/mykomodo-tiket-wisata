<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BravoHotelRoomDate extends Model
{
    use HasFactory;

    protected $table = 'bravo_hotel_room_dates';

    protected $fillable = [
        'target_id',
        'price_date',
        'active',
    ];

    public function tour()
    {
        return $this->belongsTo(HotelRoom::class, 'target_id');
    }
}
