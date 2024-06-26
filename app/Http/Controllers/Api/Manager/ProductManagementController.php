<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ProductManagementController extends Controller
{
    use ApiResponseTrait;

    public function list(Request $request)
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

    public function create(ProductRequest $request)
    {
        DB::beginTransaction();
        $fileNames = [];
        $fileNameRaw = [];
        try {
            $data = $request->validated();
            $product = new Product([
                'product_name' => $data['product_name'] ?? '',
                'sku' => $data['sku'] ?? '',
                'price' => $data['price'] ?? 0,
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'] ?? 0,
                'status' => $data['status'] ?? 1,
            ]);

            if (is_object($request->product_image)) {
                $product_image = $request->hasFile('product_image') ? $request->file('product_image') : null;
                $extension = $product_image->extension();
                $fileName = $product_image->storePubliclyAs(
                    'images/products',
                    Str::slug($data['product_name']) . '_' . str_pad(rand(0, 999), 3, STR_PAD_LEFT) . '.' . $extension,
                    'public'
                );
                $product->product_image = $fileName;
            }

            $files = $request->hasFile('images') ? $request->file('images') : null;

            $fileNames = [];
            $fileNameRaw = [];
            if ($files && (count($files) > 0)) {
                foreach ($files as $key => $file) {
                    $extension = $file->extension();
                    $fileName = $file->storePubliclyAs(
                        'images/products',
                        Str::slug($data['product_name']) . '_' . str_pad(rand(0, 999), 3, STR_PAD_LEFT) . '.' . $extension,
                        'public'
                    );
                    if ($fileName) {
                        $fileNames[] = [
                            'product_id' => '',
                            'product_image' => $fileName
                        ];
                    }
                    $fileNameRaw[] = $fileName;
                }
            }

            if (!$product->save()) {
                throw new \Exception('Lỗi khi tạo sản phẩm mới');
            };

            $id = $product->id;
            $fileNames = array_map(function ($item) use ($id) {
                $item['product_id'] = $id;
                return $item;
            }, $fileNames);

            ProductImage::insert($fileNames);
            DB::commit();
            return $this->success(Product::getOne($id), 'Tạo sản phẩm thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();
            if (count($fileNameRaw) > 0) {
                $fileNameRaw = array_map(function ($item) {
                    Storage::delete($item);
                }, $fileNameRaw);
            }
            return $this->failure('Lỗi khi tạo sản phẩm mới', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }

    public function update(ProductRequest $request, $id)
    {
        DB::beginTransaction();
        $fileNames = [];
        $fileNameRaw = [];
        $shouldDeleteFile = [];
        try {
            $data = $request->validated();
            $product = Product::findOrFail($id);

            $product->fill([
                'product_name' => $data['product_name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'status' => $data['status'] ?? $product->status,
            ]);
            if (is_object($request->product_image)) {
                $shouldDeleteFile[] = $product->getRawOriginal('product_image');
                $product_image = $request->hasFile('product_image') ? $request->file('product_image') : null;
                $extension = $product_image->extension();
                $fileName = $product_image->storePubliclyAs(
                    'images/products',
                    Str::slug($data['product_name']) . '_' . str_pad(rand(0, 999), 3, STR_PAD_LEFT) . '.' . $extension,
                    'public'
                );
                $product->product_image = $fileName;
            }

            foreach (($data['images'] ?? []) as $image) {
                if (is_string($image)) {
                    $fileNameRaw[] = $image;
                } elseif (is_object($image)) {
                    $product_image = $image;
                    $extension = $product_image->extension();
                    $fileName = $product_image->storePubliclyAs(
                        'images/products',
                        Str::slug($data['product_name']) . '_' . str_pad(rand(0, 999), 3, STR_PAD_LEFT) . '.' . $extension,
                        'public'
                    );
                    $fileNameRaw[] = $fileName;
                }
            }

            $shouldDeleteQueryRs = ProductImage::where('product_id', $id)->whereNotIn('product_image', $fileNameRaw)->get();
            foreach ($shouldDeleteQueryRs as $possibleDeleteFile) {
                $shouldDeleteFile[] = $possibleDeleteFile->product_image_storage_path;
            }
            $deleteOldProductRecord = ProductImage::where('product_id', $id)->delete();

            $fileNames = array_map(function ($item) use ($id) {
                return ['product_id' => $id, 'product_image' => $item];
            }, $fileNameRaw);
            ProductImage::insert($fileNames);
            if (!$product->save()) {
                throw new \Exception('Lỗi khi tạo sản phẩm mới');
            };
            DB::commit();
            //Delete old
            foreach ($shouldDeleteFile as $imageToDelete) {
                $pathToDelete = $imageToDelete;
                Storage::exists($pathToDelete) && Storage::delete($pathToDelete);
            }
            return $this->success(Product::getOne($id), 'Sửa sản phẩm thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();
            if (count($fileNameRaw) > 0) {
                $fileNameRaw = array_map(function ($item) {
                    Storage::delete($item);
                }, $fileNameRaw);
            }
            return $this->failure('Lỗi khi sửa sản phẩm', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $productImages = ProductImage::where('product_id', $id)->get();
            $oldFile = $product->getRawOriginal('product_image');

            if (!$product->delete()) {
                return $this->failure("Lỗi khi xóa sản phẩm");
            }

            if (!empty($oldFile) && Storage::exists($oldFile)) {
                Storage::delete($oldFile);
            }
            if (count($productImages) > 0) {
                foreach ($productImages as $oldImage) {
                    Storage::delete($oldImage->getRawOriginal('product_image'));
                }
            }
            ProductImage::where('product_id', $id)->delete();
            return $this->success([], 'Xóa sản phẩm thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi xóa thông tin sản phẩm';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function disable(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            if ($product->status == 1) {
                $product->status = 0;
                $product->save();
                return $this->success([], 'Ngừng kinh doanh sản phẩm thành công');
            } else {
                $product->status = 1;
                $product->save();
                return $this->success([], 'Tiếp tục kinh doanh sản phẩm thành công');
            }
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi ngừng kinh doanh sản phẩm';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy sản phẩm này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }
}
