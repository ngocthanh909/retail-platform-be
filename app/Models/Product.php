<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['product_name', 'product_image', 'sku', 'category_id', 'price', 'status'];

    protected function productImage(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => asset(Storage::url($value))
        );
    }

    public function category(){
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
