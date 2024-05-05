<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionCustomer extends Model
{
    use HasFactory;
    protected $table = 'promotion_customers';
    protected $fillable = ['promotion_id', 'customer_id'];
}
