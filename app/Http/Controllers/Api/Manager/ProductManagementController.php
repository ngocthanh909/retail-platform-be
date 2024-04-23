<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ProductManagementController extends Controller
{
    use ApiResponseTrait;

    public function list(Request $request){
        $products = Product::paginate(config('paginate.product'));
        return $this->success($products, 'Lấy danh sách thành công');
    }

    public function detail(Request $request, $id){
        try {
            $product = Product::findOrFail($id);
            return $this->success($product);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        try {
            $data = $request->all();
            $product = new Product([
                'product_name' => $data['product_name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'product_image' => '',
                'category_id' => $data['category_id'],
                'status' => $data['status'] ?? 1,
            ]);
            $file = $request->hasFile('product_image') ? $request->File('product_image') : null;
            if ($file) {
                $extension = $file->extension();
                $fileName = $file->storePubliclyAs(
                    'images/categories',
                    Str::slug($data['product_name']) . '_' . time() . '.' . $extension,
                    'public'
                );
                if ($fileName) {
                    $product->product_image = $fileName;
                }
            }
            if (!$product->save()) {
                return $this->failure('Lỗi khi tạo sản phẩm mới');
            };
            return $this->success($product, 'Tạo sản phẩm thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Lỗi khi tạo sản phẩm mới', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            $product = Product::findOrFail($id);
            $product->fill([
                'product_name' => $data['product_name'],
                'product_code' => $data['product_code'],
                'status' => $data['status'] ?? 1
            ]);
            $file = $request->hasFile('product_image') ? $request->File('product_image') : null;
            if ($file) {
                $oldFile = $product->getRawOriginal('product_image');
                $extension = $file->extension();
                $fileName = $file->storePubliclyAs(
                    'images/categories',
                    Str::slug($data['product_name']) . '_' . time() . '.' . $extension,
                    'public'
                );
                if ($fileName) {
                    $product->product_image = $fileName;
                    if (!empty($oldFile) && Storage::disk('public')->exists($oldFile)) {
                        Storage::disk('public')->delete($oldFile);
                    }
                }
            }
            if (!$product->save()) {
                return $this->failure('Lỗi khi sửa sản phẩm');
            };
            return $this->success($product, 'Sửa sản phẩm thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi sửa sản phẩm';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
            return $this->failure('Lỗi khi tạo sản phẩm', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $oldFile = $product->getRawOriginal('product_image');

            if (!$product->delete()) {
                return $this->failure("Lỗi khi xóa sản phẩm");
            }

            if (!empty($oldFile) && Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
            return $this->success([], 'Xóa sản phẩm thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi xóa thông tin sản phẩm';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }
}
