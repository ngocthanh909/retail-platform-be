<?php

namespace App\Http\Controllers\Api\Manager;

use App\Models\Config;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\Helpers\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class CustomerManagementController extends Controller
{
    use ApiResponseTrait;
    public function list(Request $request){
        $customers = Customer::where('status', 1);

        $keyword = $request->keyword;
        if(!empty($keyword)){
            $customers = $customers->where('customer_name', 'like', "%$keyword%");
        }
        $customers = $customers->orderBy('created_at', 'DESC')->paginate(config('paginate.store_list'));
        return $this->success($customers);
    }

    public function detail(Request $request, $id){
        try {
            $customer = Customer::findOrFail($id);
            return $this->success($customer);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function create(Request $request){
        try {
            $data = $request->all();

            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? '',
                'district' => $data['district'] ?? '',
                'province' => $data['province'] ?? '',
                'responsible_staff' => $data['responsible_staff'] ?? 0,
                'password' => Hash::make($data["password"] ?? "12345678"),
                'address' => $data['address'],
                'status' => $data['status'] ?? true
            ];
            $customer = new Customer($customerData);
            if(!$customer->save()){
                return $this->failure("Lỗi khi tạo mới khách hàng");
            }
            return $this->success($customer, 'Tạo khách hàng thành công');
        } catch(\Throwable $e){
            return $this->failure("Lỗi khi tạo mới khách hàng", $e->getMessage());
        }
    }

    public function update(Request $request, $id){
        try {
            $data = $request->all();
            $customer = Customer::findOrFail($id);

            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? '',
                'district' => $data['district'] ?? '',
                'province' => $data['province'] ?? '',
                'responsible_staff' => $data['responsible_staff'] ?? 0,
                'address' => $data['address'],
                'status' => $data['status'] ?? true
            ];

            if(!empty($data['password'])){
                $customerData['password'] = Hash::make($data["password"]);
            }

            $customer->fill($customerData);

            if(!$customer->save()){
                return $this->failure("Lỗi khi sửa cửa hàng");
            }
            return $this->success($customer, 'Sửa cửa hàng thành công');
        } catch(\Throwable $e){
            $message = 'Lỗi khi cập nhật thông tin cửa hàng';
            if($e instanceof ModelNotFoundException){
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function delete(Request $request, $id){
        try {
            $customer = Customer::findOrFail($id);
            if(!$customer->delete()){
                return $this->failure("Lỗi khi xóa hàng");
            }
            return $this->success([], 'Xóa hàng thành công');
        } catch(\Throwable $e){
            $message = 'Lỗi khi xóa thông tin cửa hàng';
            if($e instanceof ModelNotFoundException){
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function getDiscountRate(Request $request){
        $discountRate = Config::getConfig('discount');
        return $this->success(['rate' => $discountRate]);
    }
    public function editDiscountRate(Request $request){
        $update = Config::editConfig('discount', $request->rate);
        return $update ? $this->success() : $this->failure();
    }
}
