<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = "media_files";
    public function image()
    {
        return $this->belongsTo(Product::class, 'gallery');
    }
}
