<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;


class ProductImage extends Model
{
    use HasFactory;
    protected $table = 'product_images';
    protected $fillable = ['product_id', 'product_image'];

    protected function productImage(): Attribute
    {
        if($this->getRawOriginal('product_image')){
            return Attribute::make(
                get: fn (string $value) => asset(Storage::url($value))
            );
        }
        return Attribute::make(get: fn () => '');

    }
}
