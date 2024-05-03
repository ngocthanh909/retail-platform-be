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
        'product_image'
    ];

    protected function productImage(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => asset(Storage::url($value))
        );
    }
}
