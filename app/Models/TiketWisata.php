<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiketWisata extends Model
{
    use HasFactory, SoftDeletes;

    protected $table="bravo_tiket_wisata";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'location_id',
        'category_id',
        'address',
        'latitude',
        'longitude',
        'banner',
        'gallery',
        'stock',
        'base_price',
        'base_price_child',
        'base_price_wisman',
        'vat',
        'retribution',
        'retribution_child',
        'retribution_wisman',
        'price',
        'price_weekend',
        'price_holiday',
        'status',
        'create_user'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relasi ke SpecialDate
    public function specialDates()
    {
        return $this->hasMany(TiketWisataDate::class, 'target_id');
    }
    
    public function category(){
        return $this->belongsTo(BravoTiketWisataCategory::class);
    }

}
