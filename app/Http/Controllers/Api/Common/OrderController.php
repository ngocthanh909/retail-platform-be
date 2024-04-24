<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Category;
use App\Models\Config;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductImage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    use ApiResponseTrait;
    public function calculateOrder(Request $request)
    {
        try {
            $data = $request->all();
            $rawProducts = $data['products'] ?? [];
            $products = [];
            $isGuest = false;

            //Order variable
            $subTotal = 0;
            $total = 0;

            if ($request->user()->tokenCan('customer')) {
                if (empty($request->user()?->responsible_staff)) {
                    $isGuest = false;
                }
            } else {
                //Check logic customer is a member or not
                $customer = Customer::find($data['customer_id']);
                if (!$customer) {
                    throw new Exception("Không tìm thấy cửa hàng này");
                }
                if (empty($customer?->responsible_staff)) {
                    $isGuest = true;
                }
            }

            if (count($rawProducts) > 0) {
                foreach ($rawProducts as $rawProduct) {
                    $product = Product::find($rawProduct['id']);
                    if(!$product){
                        throw new Exception("Không tìm thấy sản phẩm trong danh sách");
                    }
                    $tempPrice = $product->price + ($isGuest ? ($product->price * ((int)(Config::getConfig('discount_rate') ?? 0)) / 100): 0);
                    $tempTotal = $tempPrice * $rawProduct['qty'];
                    $products[] = [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'product_image' => $product->product_image,
                        'price' => $tempPrice,
                        'total' => $tempTotal,
                        'qty' => $rawProduct['qty']
                    ];

                    $subTotal += $tempTotal;
                }
            }
            $total = $subTotal;
            $responseData = [
                'products' => $products,
                'discount' => 0,
                'discount_description' => 'Nếu bạn được tặng quà thì ghi chú ở đây. Nếu được giảm giá thì discount trả về giá trị theo số tiền dc giảm',
                'subtotal' => $subTotal,
                'total' => $total,
            ];
            return $this->success($responseData);
        } catch (\Throwable $e) {
            dd($e);
        }
    }
}
