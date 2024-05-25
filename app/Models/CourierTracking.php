<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierTracking extends Model
{
    use HasFactory;
    protected $connection="kabtour_db";
    protected $table="users";
}
