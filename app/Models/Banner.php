<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use DB;

class Banner extends Model
{
    use HasFactory;
    protected $table = 'banners';
    protected $fillable = ['image', 'title', 'description'];
    protected $appends = ['storage_path_image'];

    protected function image(): Attribute
    {
        if($this->getRawOriginal('image')){
            return Attribute::make(
                get: fn (string $value) => asset(Storage::url($value))
            );
        }
        return Attribute::make(get: fn () => '');

    }
    public function getStoragePathImageAttribute()
    {
        return $this->getRawOriginal('image');
    }
}
