<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Order extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = "orders";
    protected $appends = ["displayId"];

    const ORDER_STATUS = [
        0 => 'Đã hủy',
        1 => 'Chờ xác nhận',
        2 => 'Đã xác nhận',
        3 => 'Hoàn thành'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'responsible_staff',
        'creator',
        'customer_name',
        'phone',
        'province',
        'district',
        'address',
        'subtotal',
        'total',
        'discount_code',
        'discount_note',
        'discount',
        'note',
        'status'
    ];

    public function getDisplayIdAttribute()
    {
        return "DH" . str_pad($this->id, 7, 0, STR_PAD_LEFT);
    }

    public function details(){
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }
    public function staff(){
        return $this->belongsTo(User::class, 'responsible_staff', 'id');
    }
    public function creator(){
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
