<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use DB;

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

    public function images(){
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public static function getOne($id){
        return self::with(['category', 'images'])->find($id);
    }

    public static function getMany($filter = null){
        $query = self::with(['category']);

        if(!empty($filter['keyword'])){
            $query = $query->where('product_name', 'like', '%' . $filter['keyword'] . '%');
        }
        if(!empty($filter['category_id'])){
            $query = $query->where('category_id', $filter['category_id']);
        }
        return $query->paginate(config('paginate.product'));
    }
}
