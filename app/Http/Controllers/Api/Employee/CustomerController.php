<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function create(CustomerRequest $request)
    {
        try {
            $data = $request->validated();
            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? '',
                'district' => $data['district'] ?? '',
                'province' => $data['province'] ?? '',
                'responsible_staff' => $request->user()->id ?? '',
                'password' => Hash::make($data["password"] ?? "12345678"),
                'address' => $data['address'],
                'status' => $data['status'] ?? true
            ];
            $customer = new Customer($customerData);
            if (!$customer->save()) {
                return $this->failure("Lỗi khi tạo mới khách hàng");
            }
            return $this->success($customer, 'Tạo khách hàng thành công');
        } catch (\Throwable $e) {
            return $this->failure("Lỗi khi tạo mới khách hàng", $e->getMessage());
        }
    }

    public function edit(CustomerRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $customer = Customer::findOrFail($id);

            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? '',
                'district' => $data['district'] ?? '',
                'province' => $data['province'] ?? '',
                'address' => $data['address'],
                'status' => $data['status'] ?? true
            ];

            if (!empty($data['password'])) {
                $customerData['password'] = Hash::make($data["password"]);
            }

            $customer->fill($customerData);

            if (!$customer->save()) {
                return $this->failure("Lỗi khi sửa cửa hàng");
            }
            return $this->success($customer, 'Sửa cửa hàng thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi cập nhật thông tin cửa hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            if (!$customer->delete()) {
                return $this->failure("Lỗi khi xóa hàng");
            }
            return $this->success([], 'Xóa cửa hàng thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi xóa thông tin cửa hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function detail(Request $request, $id)
    {
        try {
            $customer = Customer::with('staff')->findOrFail($id);
            return $this->success($customer);
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi truy vấn cửa hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }
}
