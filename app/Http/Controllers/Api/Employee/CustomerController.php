<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function listManagedByMe(Request $request)
    {
        try {
            $customers = Customer::where('responsible_staff', $request->user()?->id);
            $keyword = $request->keyword;
            if (!empty($keyword)) {
                $customers = $customers->where('customer_name', 'like', "%$keyword%");
            }
            $customers = $customers->orderBy('created_at', 'DESC')->paginate(config('paginate.store_list'));
            return $this->success($customers);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failure('Lỗi khi lấy danh sách cửa hàng');
        }
    }
}
