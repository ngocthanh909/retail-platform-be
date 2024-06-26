<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = "users";
    protected $appends = ["displayId"];


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'dob',
        'avatar',
        'address',
        'gender'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function customers(){
       return $this->hasMany(Customer::class, 'responsible_staff', 'id');
    }

    public function getDisplayIdAttribute()
    {
        return ($this->is_admin ? "ADMIN" : "NV") . str_pad($this->id, 7, 0, STR_PAD_LEFT);
    }

    protected function avatar(): Attribute
    {
        if($this->getRawOriginal('avatar')){
            return Attribute::make(
                get: fn (string $value) => asset(Storage::url($value))
            );
        }
        return Attribute::make(get: fn () => '');
    }

}
