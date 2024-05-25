<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $connection = 'kabtour_db';
    protected $table = 'bravo_locations';
}
