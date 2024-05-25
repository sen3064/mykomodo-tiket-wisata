<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreModelHasRole extends Model
{
    use HasFactory;
    protected $connection="kabtour_db";
    protected $table="users";
    public $timestamps = false;
}
