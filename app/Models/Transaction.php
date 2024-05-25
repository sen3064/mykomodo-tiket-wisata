<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $connection="kabtour_db";
    protected $table = "bravo_market_transactions";

    public function order()
    {
    	return $this->belongsTo(Order::class);
    }

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

}
