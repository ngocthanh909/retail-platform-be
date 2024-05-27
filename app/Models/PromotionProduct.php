<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionProduct extends Model
{
    use HasFactory;
    protected $table = 'promotion_apply_products';
    protected $fillable = ['promotion_id', 'product_id', 'category_id'];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function category()
    {
        return $this->hasOne(Product::class, 'id', 'category_id');
    }
}
