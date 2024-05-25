<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AboutKabupaten extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $connection="kabtour_db";
    protected $table = "tentang_kabupaten";

    protected $fillable = [
        "title",
        "logo_id",
        "banner_id",
        "gallery",
        "content",
        "category",
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
