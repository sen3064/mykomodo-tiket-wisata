<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TiketWisataDate extends Model
{
    use HasFactory;

    protected $table = 'bravo_tiket_wisata_dates';

    protected $fillable = [
        'target_id',
        'start_date',
        'status',
    ];

    public function tiketWisata()
    {
        return $this->belongsTo(TiketWisata::class, 'target_id');
    }

    public function getPriceAttribute()
    {
        if ($this->status == 1 && $this->product) {
            return $this->tiketWisata->price_holiday;
        }

        return null;
    }
}
