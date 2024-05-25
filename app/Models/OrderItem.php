<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $connection = 'kabtour_db';
    protected $table = "bravo_market_order_items";

    public function order()
    {
    	return $this->belongsTo(Order::class);
    }

    public function product()
    {
    	return $this->belongsTo(Product::class);
    }
}
