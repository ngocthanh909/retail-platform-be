<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class OrderDetail extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = "order_details";
    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'product_name',
        'price',
        'qty',
        'discount',
        'total',
        'product_image',
        'category_id',
        'category_name',
        'category_commission_rate',
        'category_commission_amount'
    ];

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
