<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;
    protected $table = 'configs';
    protected $fillable = ['key', 'value'];
    public static function getConfig($key = ''){
        $config = self::where('key', $key)->first();
        return $config->value ?? '';
    }
    public static function editConfig($key, $value){
        $config = self::where('key', $key)->first();
        if($config){
            $config->value = $value;
            return $config->save();
        }
        $config = new Config(['key' => $key, 'value' => $value]);
        return $config->save();
    }

}
