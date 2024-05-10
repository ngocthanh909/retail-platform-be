<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ApiResponseTrait;
    public function list(Request $request){
        $categories = Category::where('status', 1)->paginate(config('paginate.category'));
        return $this->success($categories, 'Lấy danh sách thành công');
    }

    public function listForManager(Request $request){
        $categories = Category::paginte(config('paginate.category'));
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
}
