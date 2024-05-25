<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRekening extends Model
{
    protected $connection="kabtour_db";
    protected $table = 'user_rekening';
}
