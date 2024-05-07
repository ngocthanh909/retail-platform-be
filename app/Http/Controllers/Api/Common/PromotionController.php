<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\PromotionTrait;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    use ApiResponseTrait, PromotionTrait;
    function getUserPromotion(Request $request){
        try {
            $promotions = Promotion::where('status', 1)
            ->where('apply', 0)
            ->orWhereHas('applyCustomer', function($query) use ($request) {
                if($request->user()->tokenCan('customer')){
                    $query->where('customer_id', $request->user()->id);
                } elseif($request->customer_id) {
                    $query->where('customer_id', $request->customer_id);
                }
            })
            ->get();
            return $this->success($promotions);
        } catch (\Throwable $e){
            Log::error($e);
            return $this->failure('Lỗi khi lấy thông tin khuyến mãi', $e->getMessage());
        }
    }
}
