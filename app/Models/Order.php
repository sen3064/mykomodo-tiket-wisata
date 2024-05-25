<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    protected $connection = 'kabtour_db';
    protected $table = "bravo_market_orders";

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function orderItem()
    {
    	return $this->hasMany(OrderItem::class);
    }

    public function shipping()
    {
    	return $this->hasOne(Shipping::class);
    }

    public function transaction()
    {
    	return $this->hasMany(Transaction::class);
    }
}
