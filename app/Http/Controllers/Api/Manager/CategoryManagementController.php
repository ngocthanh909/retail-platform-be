<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryManagementController extends Controller
{
    use ApiResponseTrait;

    public function list(Request $request){
        $categories = Category::paginate(config('paginate.category'));
        return $this->success($categories, 'Lấy danh sách thành công');
    }

    public function listAll(Request $request){
        $categories = Category::get();
        return $this->success($categories, 'Lấy danh sách thành công');
    }

    public function listAllWithProduct(Request $request){
        $categories = Category::with('product')->get();
        return $this->success($categories, 'Lấy danh sách thành công');
    }

    public function detail(Request $request, $id){
        try {
            $category = Category::findOrFail($id);
            return $this->success($category);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy ngành hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function create(CategoryRequest $request)
    {
        try {
            $data = $request->validated();
            $category = new Category([
                'category_name' => $data['category_name'],
                'category_code' => $data['category_code'],
                'status' => $data['status'] ?? 1,
                'category_image' => ''
            ]);
            $file = $request->hasFile('category_image') ? $request->File('category_image') : null;
            if ($file) {
                $extension = $file->extension();
                $fileName = $file->storePubliclyAs(
                    'images/categories',
                    Str::slug($data['category_name']) . '_' . time() . '.' . $extension,
                    'public'
                );
                if ($fileName) {
                    $category->category_image = $fileName;
                }
            }
            if (!$category->save()) {
                return $this->failure('Lỗi khi tạo ngành hàng mới');
            };
            return $this->success($category, 'Tạo ngành hàng thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Lỗi khi tạo ngành hàng mới', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }

    public function update(CategoryRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $category = Category::findOrFail($id);
            $category->fill([
                'category_name' => $data['category_name'],
                'category_code' => $data['category_code'],
                'status' => $data['status'] ?? 1
            ]);
            $file = $request->hasFile('category_image') ? $request->File('category_image') : null;
            if ($file) {
                $oldFile = $category->getRawOriginal('category_image');
                $extension = $file->extension();
                $fileName = $file->storePubliclyAs(
                    'images/categories',
                    Str::slug($data['category_name']) . '_' . time() . '.' . $extension,
                    'public'
                );
                if ($fileName) {
                    $category->category_image = $fileName;
                    if (!empty($oldFile) && Storage::exists($oldFile)) {
                        Storage::delete($oldFile);
                    }
                }
            }
            if (!$category->save()) {
                return $this->failure('Lỗi khi sửa ngành hàng');
            };
            return $this->success($category, 'Sửa ngành hàng thành công');
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi sửa ngành hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy ngành hàng này!';
            }
            return $this->failure($message, $e->getMessage());
            return $this->failure('Lỗi khi tạo ngành hàng', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $oldFile = $category->getRawOriginal('category_image');

            if (!$category->delete()) {
                return $this->failure("Lỗi khi xóa ngành hàng");
            }

            if (!empty($oldFile) && Storage::exists($oldFile)) {
                Storage::delete($oldFile);
            }
            return $this->success([], 'Xóa ngành hàng thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi xóa thông tin ngành hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy ngành hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function disable(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            if($category->status == 1){
                Product::where('category_id', $id)->update(['status' => 0]);
                $category->status = 0;
                $category->save();
                return $this->success([], 'Ngừng kinh doanh ngành hàng thành công');
            } else {
                $category->status = 1;
                Product::where('category_id', $id)->update(['status' => 1]);
                $category->save();
                return $this->success([], 'Tiếp tục kinh doanh ngành hàng thành công');
            }
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi ngừng kinh doanh ngành hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy ngành này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }
}
