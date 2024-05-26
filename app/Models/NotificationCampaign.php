<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    use HasFactory;
    protected $table = 'notification_campaigns';
    protected $fillable = ['title', 'content', 'image', 'delivery_time', 'delivery_date', 'repeat', 'receiver_id', 'next_repeat'];
    public function receiver()
    {
        return $this->hasOne(Customer::class, 'id', 'receiver_id');
    }
}
