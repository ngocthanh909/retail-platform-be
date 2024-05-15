<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use DB;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['product_name', 'product_image', 'description', 'sku', 'category_id', 'price', 'status'];
    protected $appends = ['product_image_storage_path', 'display_price'];

    protected function productImage(): Attribute
    {
        if ($this->getRawOriginal('product_image')) {
            return Attribute::make(
                get: fn (string $value) => asset(Storage::url($value))
            );
        }
        return Attribute::make(get: fn () => '');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public static function getOne($id)
    {
        return self::with(['category', 'images'])->find($id);
    }

    public static function getMany($filter = null, $activeProductOnly = false)
    {
        $query = self::with(['category']);

        if (!empty($filter['keyword'])) {
            $query = $query->where('product_name', 'like', '%' . $filter['keyword'] . '%');
        }
        if (!empty($filter['category_id'])) {
            $query = $query->where('category_id', $filter['category_id']);
        }
        if ($activeProductOnly) {
            $query = $query->where('status', 1);
        }
        return $query->paginate(config('paginate.product'));
    }

    public function getProductImageStoragePathAttribute()
    {
        return $this->getRawOriginal('product_image');
    }

    public function getDisplayPriceAttribute()
    {
        try {
            $rate = config('app.discount_rate');
            $user = auth('sanctum')->user();
            $priceForGuest = $this->price + ($this->price * $rate / 100);
            if(!$user){
                return $priceForGuest;
            }
            if($user && $user->tokenCan('customer')){
                return $user->responsible_staff ? $this->price : $priceForGuest;
            }
            if($user && !$user->tokenCan('admin') && $user->tokenCan('employee')){
                return $this->price;
            }
            if ($user && $user->tokenCan('admin')) {
                if (request()->destination_customer) {
                    $user = Customer::find(request()->destination_customer);
                    if ($user && $user->responsible_staff) return $this->price;
                    return $priceForGuest;
                }
            }

            return $this->price;
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->price;
        }
    }
}
