<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Config;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    function __construct()
    {
        config(['app.discount_rate' => Config::getConfig('discount')]);
    }
    use ApiResponseTrait;
    public function list(Request $request)
    {
        $products = Product::getMany([
            'category_id' => $request->category_id ?? '',
            'keyword' => $request->keyword ?? '',
        ], true);
        return $this->success($products, 'Lấy danh sách thành công');
    }
    public function listForManager(Request $request)
    {
        $products = Product::getMany([
            'category_id' => $request->category_id ?? '',
            'keyword' => $request->keyword ?? '',
        ]);
        return $this->success($products, 'Lấy danh sách thành công');
    }
    public function detail(Request $request, $id)
    {
        try {
            $product = Product::getOne($id);
            return $this->success($product);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }
}
