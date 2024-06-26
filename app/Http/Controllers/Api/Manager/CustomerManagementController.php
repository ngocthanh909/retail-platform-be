<?php

namespace App\Http\Controllers\Api\Manager;

use App\Models\Config;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\District;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CustomerManagementController extends Controller
{
    use ApiResponseTrait;
    public function list(Request $request)
    {
        try {
            $customers = Customer::with('staff');
            $keyword = $request->keyword;
            $address = $request->address;
            $province_id = $request->province_id;
            $district_id = $request->district_id;

            if (!empty($keyword)) {
                $customers = $customers->where('customer_name', 'like', "%$keyword%");
            }

            if (!empty($address)) {
                $customers = $customers->where('address', 'like', "%$address%");
            }
            if (!empty($province_id)) {
                $customers = $customers->where('province_id', $province_id);
            }
            if (!empty($district_id)) {
                $customers = $customers->where('district_id', "%$district_id%");
            }
            $customers = $customers->orderBy('created_at', 'DESC')->paginate(config('paginate.store_list'));
            return $this->success($customers);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failure('Lỗi khi lấy danh sách cửa hàng');
        }
    }

    public function listAll(Request $request)
    {
        try {
            $customers = new Customer();
            $user = $request->user();
            if ($user->tokenCan('employee') && !$user->tokenCan('admin')) {
                $customers = $customers->where('responsible_staff', $user->id);
            }
            $customers = $customers->get();
            return $this->success($customers);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failure('Lỗi khi lấy danh sách cửa hàng');
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

    public function create(CustomerRequest $request)
    {
        try {
            $data = $request->validated();
            $district = District::where('district_code', $data['district_id'] ?? '')->first();
            $province = Province::where('province_code', $data['province_id'] ?? '')->first();
            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? '',
                'district' => $district->district_name ?? '',
                'province' => $province->province_name ?? '',
                'district_id' => $data['district_id'] ?? 0,
                'province_id' => $data['province_id'] ?? 0,
                'responsible_staff' => $data['responsible_staff'] ?? 0,
                'password' => Hash::make($data["password"] ?? "12345678"),
                'address' => $data['address'],
                'status' => $data['status'] ?? true
            ];
            $customer = new Customer($customerData);
            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $fileName = '';
            $customer = new Customer($customerData);
            if ($file) {
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($customer->phone) . time() . '.' . $file->extension()
                );
                $customer->avatar = $fileName;
            };

            if (!$customer->save()) {
                return $this->failure("Lỗi khi tạo mới khách hàng");
            }
            return $this->success($customer, 'Tạo khách hàng thành công');
        } catch (\Throwable $e) {
            return $this->failure("Lỗi khi tạo mới khách hàng", $e->getMessage());
        }
    }

    public function update(CustomerRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $customer = Customer::findOrFail($id);
            $district = District::where('district_code', $data['district_id'])->first();
            $province = Province::where('province_code', $data['province_id'])->first();
            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? '',
                'district' => $district->district_name ?? '',
                'province' => $province->province_name ?? '',
                'district_id' => $data['district_id'] ?? 0,
                'province_id' => $data['province_id'] ?? 0,
                'responsible_staff' => $data['responsible_staff'] ?? 1,
                'address' => $data['address'],
                'status' => $data['status'] ?? true,
                'gender' => $data['gender'] ?? 1,
            ];

            if (!empty($data['password'])) {
                $customerData['password'] = Hash::make($data["password"] ?? '');
            }

            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $fileName = '';

            if ($file) {
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($customer->phone) . time() . '.' . $file->extension()
                );
                $customer->avatar = $fileName;
            };

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
            return $this->success([], 'Xóa hàng thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi xóa thông tin cửa hàng';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy cửa hàng này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    public function getDiscountRate(Request $request)
    {
        $discountRate = Config::getConfig('discount');
        return $this->success(['rate' => $discountRate]);
    }
    public function editDiscountRate(Request $request)
    {
        $update = Config::editConfig('discount', $request->rate);
        return $update ? $this->success() : $this->failure();
    }
}
