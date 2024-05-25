<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    protected $connection="kabtour_db";
    protected $table = 'user_meta';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
