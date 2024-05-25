<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boat extends Model
{
    use HasFactory;
    protected $table='bravo_boat';

    public function portFrom()
    {
        return $this->hasOne(Port::class, 'id', 'port_from')->withDefault();
    }

    public function portTo()
    {
        return $this->hasOne(Port::class, 'id', 'port_to')->withDefault();
    }
}
