<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table="bravo_market_products";
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'regular_price',
        'admin_fee',
        'sku'
        // 'social_id',
        // 'device_token',
        // 'token',
        // 'is_open',
        // 'always_open'
    ];

    public function category() 
    {
    	return $this->belongsTo(Category::class,'category_id');
    }
    public function image()
    {
        return $this->belongsTo(Media::class,'image_id');
    }
    public function location()
    {
        return $this->belongsTo(Location::class,'location_id');
    }
    // public function gallery()
    // {
    //     return $this->belongsTo(Media::class, $rowimage);
    // }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class,'product_id');
    }
}
