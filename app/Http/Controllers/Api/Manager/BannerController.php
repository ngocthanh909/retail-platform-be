<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    use ApiResponseTrait;
    public function list()
    {
        $banners = Banner::get();
        return $this->success($banners);
    }
    public function create(Request $request)
    {
        try {
            $banner = new Banner();
            $file = $request->hasFile('image') ? $request->file('image') : null;

            if ($file) {
                $fileName = $file->storePubliclyAs(
                    'images/banners/' . time() . '.' . $file->extension()
                );
                $banner->image = $fileName;
            };
            if(!$banner->save()){
                return $this->failure();
            }
            return $this->success($banner);
        } catch (\Throwable $e) {
            return $this->failure('Tạo banner thất bại', $e->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            if($banner->delete()){
                Storage::delete($banner->getRawOriginal('image'));
            };
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Xóa banner thất bại', $e->getMessage());
        }
    }
}
