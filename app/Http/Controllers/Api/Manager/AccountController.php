<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    use ApiResponseTrait;
    function createUser(Request $request)
    {
        try {
            $data = [
                "name" => $request->name,
                "phone" => $request->phone,
                "email" => $request->email,
                "is_admin" => 0,
                "password" => Hash::make($request->password)
            ];
            $user = new User($data);
            if ($user->save()) {
                return $this->success("Tạo tài khoản thành công!");
            };
            return $this->failure("Tạo tài khoản thất bại");
        } catch(\Throwable $e){
            return $this->failure('', $e->getMessage());
        }


    }
    function createCustomer()
    {

    }
}
