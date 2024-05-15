<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmployeeProfileEditRequest;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeProfileController extends Controller
{
    use ApiResponseTrait;
    function edit(EmployeeProfileEditRequest $request)
    {
        try {
            $data = $request->validated();
            $user = User::findOrFail($request->user()->id);
            $user->fill([
                "name" => $data['name'] ?? '',
                "email" => $data['email'] ?? '',
                "gender" => $data['gender'] ?? true,
                "dob" => $data['dob'] ?? '1900-01-01',
                "address" => $data['address'] ?? ''
            ]);

            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $fileName = '';

            if ($file) {
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($user->phone) . time() . '.' . $file->extension()
                );
                $user->avatar = $fileName;
            };
            if ($user->save()) {
                return $this->success($user, "Sửa tài khoản thành công!");
            };
            return $this->failure("Sửa tài khoản thất bại");
        } catch (\Throwable $e) {
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy tài khoản này', $e->getMessage());
            }
            return $this->failure('Lỗi khi sửa tài khoản', $e->getMessage());
        }
    }

    function changePassword(ChangePasswordRequest $request)
    {
        try {
            $data = $request->all();
            $user = User::findOrFail($request->user()->id);
            if(!Hash::check($data['current_password'], $user->password)){
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
