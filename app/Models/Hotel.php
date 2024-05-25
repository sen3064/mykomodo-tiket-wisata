<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Hotel extends Model
{
    use SoftDeletes;
    use Notifiable;
    protected $table                              = 'bravo_hotels';
    public    $type                               = 'hotel';

    public function hotelRoom(){
        return $this->hasMany(HotelRoom::class,'parent_id');
    }
}
