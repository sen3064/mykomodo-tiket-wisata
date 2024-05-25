<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;
    protected $connection="kabtour_db";
    protected $table = "bravo_market_shippings";

    public function order()
    {
    	return $this->belongsTo(Order::class);
    }
}
