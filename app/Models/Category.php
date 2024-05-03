<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = ['category_name', 'category_code', 'category_image'];

    protected function categoryImage(): Attribute
    {
        if($this->getRawOriginal('category_image')){
            return Attribute::make(
                get: fn (string $value) => asset(Storage::url($value))
            );
        }
        return Attribute::make(get: fn () => '');

    }
}
