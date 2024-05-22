<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerProfileEditRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Customer;
use App\Models\District;
use App\Models\Province;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerProfileController extends Controller
{
    use ApiResponseTrait;
    public function edit(CustomerProfileEditRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = Customer::findOrFail($request->user()->id);
            $district = District::where('district_code', $data['district_id'] ?? '')->first();
            $province = Province::where('province_code', $data['province_id'] ?? '')->first();
            $customerData = [
                'customer_name' => $data['customer_name'],
                'gender' => $data['gender'] ? 1 : 0,
                'address' => $data['address'] ?? '',
                'district' => $district->district_name ?? '',
                'province' => $province->province_name ?? '',
                'district_id' => $data['district_id'] ?? 0,
                'province_id' => $data['province_id'] ?? 0,
                'dob' => $data['dob'] ?? '1900-01-01'
            ];
            $customer->fill($customerData);
            $originalAvatar = $customer->getRawOriginal('avatar');
            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $fileName = '';
            if ($file) {
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($customer->phone) . time() . '.' . $file->extension()
                );
                $customer->avatar = $fileName;
            };
            if (!$customer->save()) {
                return $this->failure("Lỗi khi sửa hồ sơ");
            }
            if (!empty($originalAvatar)) {
                Storage::delete($originalAvatar);
            }
            return $this->success($customer, 'Sửa hồ sơ thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi cập nhật thông tin hồ sơ';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy hồ sơ này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    function changePassword(Request $request)
    {
        try {
            $data = $request->all();
            $user = Customer::findOrFail($request->user()->id);

            if (!Hash::check($data['current_password'] ?? '', $user->password)) {
                throw new \Exception("Mật khẩu cũ không khớp");
            }
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            if ($user->save()) {
                return $this->success($user, "Đổi mật khẩu thành công!");
            };
            return $this->failure("Đổi mật khẩu thất bại");
        } catch (\Throwable $e) {
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy tài khoản này', $e->getMessage());
            }
            return $this->failure('Lỗi khi sửa tài khoản', $e->getMessage());
        }
    }
}
