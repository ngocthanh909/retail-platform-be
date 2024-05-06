<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Promotion;
use App\Models\PromotionCustomer;
use App\Models\PromotionProduct;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionManagementController extends Controller
{
    use ApiResponseTrait;

    function list(Request $request){
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $status = $request->status;
        $promote_type = $request->promote_type;

        $promotions = Promotion::orderBy('start_date', 'DESC');
        if($start_date){
            $promotions = $promotions->where('start_date', '>=', $start_date);
        }
        if($end_date){
            $promotions = $promotions->where('end_date', '>=', $end_date);
        }
        if($status){
            $promotions = $promotions->where('status', $status);
        }
        if($promote_type){
            $promotions = $promotions->where('promote_type', $promote_type);
        }
        $promotions = $promotions->paginate(config('paginate.promotion'));
        //with(['applyProduct', 'applyCustomer'])->
        return $this->success($promotions);
    }
    public function detail(Request $request, $id)
    {
        try {
            $promotion = Promotion::with(['applyProduct', 'applyCustomer'])->findOrFail($id);
            return $this->success($promotion);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm khuyến mãi này!', $e->getMessage());
            }
            return $this->failure('Lỗi khi lấy thông tin CTKM', $e->getMessage());
        }
    }

    function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $promotion = new Promotion([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? '',
                'qty' => $data['qty'] ?? 0,
                // 'used' => $data['used'],
                'apply' => $data['apply'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'promote_type' => $data['promote_type'],
                'promote_by' => $data['promote_by'],
                'promote_min_order_price' => $data['discount_min_order_price'] ?? 0,


                'gift_product_id' => $data['gift_product_id'] ?? null,
                'gift_product_qty' => $data['gift_product_qty'] ?? null,

                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'status' => $data['status'] ? 1 : 0
            ]);

            $promotionSaved = $promotion->save();

            if (($data['apply'] == 1) && isset($data['customers']) && is_array($data['customers'])) {
                $customers = [];
                foreach ($data['customers'] as $customer) {
                    $customers[] = [
                        'promotion_id' => $promotion->id,
                        'customer_id' => $customer
                    ];
                }
                PromotionCustomer::insert($customers);
            }
            if ($promotion->promote_type == 1) {
                if ($data['products'] && is_array($data['products'])) {
                    $products = [];
                    foreach ($data['products'] as $product) {
                        $products[] = [
                            'promotion_id' => $promotion->id,
                            'product_id' => $product
                        ];
                    }
                    PromotionProduct::insert($products);
                }
            }

            if(!$promotionSaved){
                throw new \Exception('Không thể lưu chương trình khuyến mãi');
            }
            // dd($promotionSaved);
            DB::commit();
            return $this->success($promotion, 'Lưu CTKM thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            // dd($e);
            Log::error($e);
            return $this->failure('Tạo chương trình KM thất bại', $e->getMessage());
        }
    }

    function edit(Request $request, $id){
        DB::beginTransaction();
        try {
            $data = $request->all();
            $promotion = Promotion::findOrFail($id);
            $promotion->fill([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? '',
                'qty' => $data['qty'] ?? 0,
                // 'used' => $data['used'],
                'apply' => $data['apply'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'promote_type' => $data['promote_type'],
                'promote_by' => $data['promote_by'],
                'promote_min_order_price' => $data['discount_min_order_price'] ?? 0,


                'gift_product_id' => $data['gift_product_id'] ?? null,
                'gift_product_qty' => $data['gift_product_qty'] ?? null,

                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'status' => $data['status'] ? 1 : 0
            ]);

            $promotionSaved = $promotion->save();

            PromotionProduct::where('promotion_id', $id)->delete();
            PromotionCustomer::where('promotion_id', $id)->delete();

            if (($data['apply'] == 1) && isset($data['customers']) && is_array($data['customers'])) {
                $customers = [];
                foreach ($data['customers'] as $customer) {
                    $customers[] = [
                        'promotion_id' => $promotion->id,
                        'customer_id' => $customer
                    ];
                }
                PromotionCustomer::insert($customers);
            }
            if ($promotion->promote_type == 1) {
                if ($data['products'] && is_array($data['products'])) {
                    $products = [];
                    foreach ($data['products'] as $product) {
                        $products[] = [
                            'promotion_id' => $promotion->id,
                            'product_id' => $product
                        ];
                    }
                    PromotionProduct::insert($products);
                }
            }

            if(!$promotionSaved){
                throw new \Exception('Không thể sửa chương trình khuyến mãi');
            }
            // dd($promotionSaved);
            DB::commit();
            return $this->success($promotion, 'Sửa CTKM thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm khuyến mãi này!', $e->getMessage());
            }
            return $this->failure('Lỗi khi sửa CTKM', $e->getMessage());
        }
    }

    function delete(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $deleteMaster = Promotion::where('id', $id)->delete();
            if(!$deleteMaster){
                throw new \Exception('Không thể xóa chương trình khuyến mãi');
            }
            PromotionProduct::where('promotion_id', $id)->delete();
            PromotionCustomer::where('promotion_id', $id)->delete();

            DB::commit();
            return $this->success([], 'Xóa CTKM thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e);
            Log::error($e);
            return $this->failure('Xóa chương trình KM thất bại', $e->getMessage());
        }
    }


}
