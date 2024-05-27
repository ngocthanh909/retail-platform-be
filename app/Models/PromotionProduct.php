<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionProduct extends Model
{
    use HasFactory;
    protected $table = 'promotion_apply_products';
    protected $fillable = ['promotion_id', 'product_id', 'category_id'];

    public function products()
    {
        return $this->hasMany(Product::class, 'id', 'product_id');
    }
    public function categories()
    {
        return $this->hasMany(Product::class, 'id', 'category_id');
    }
}
