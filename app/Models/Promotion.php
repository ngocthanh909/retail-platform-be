<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    protected $table = 'promotions';
    // protected $fillable = [
    //     'name',
    //     'code',
    //     'description',
    //     'start_date',
    //     'end_date',
    //     'apply',
    //     'qty',
    //     'used',
    //     'status',

    //     'promote_by',
    //     'promote_type',
    //     'promote_min_order_price',

    //     'gift_product_id',
    //     'gift_product_qty',
    //     'discount_value',
    //     'discount_typ'
    // ];
    protected $guarded = [];

    function applyCustomer()
    {
        return $this->hasMany(PromotionCustomer::class, 'promotion_id', 'id');
    }
    function applyProduct()
    {
        return $this->hasMany(PromotionProduct::class, 'promotion_id', 'id');
    }
    function giftProduct()
    {
        return $this->hasOne(Product::class, 'id', 'gift_product_id');
    }
}
