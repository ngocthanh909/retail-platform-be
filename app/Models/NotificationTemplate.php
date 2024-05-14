<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class NotificationTemplate extends Model
{
    use HasFactory;
    protected $table = 'notification_template';
    protected $guarded = [];
}
