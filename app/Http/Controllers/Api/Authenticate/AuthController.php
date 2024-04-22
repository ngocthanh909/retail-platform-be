<?php

namespace App\Http\Controllers\Api\Authenticate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Http\Traits\Helpers\ApiResponseTrait;
use Illuminate\Http\Response;
use Laravel\Passport\Token;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;
    public function login(Request $request){
        DB::beginTransaction();
        DB::enableQueryLog();
        try {
            $credentials = [
                "phone" => $request->phone,
                "password" => $request->password
            ];
            $checkUser = User::where('phone', $credentials['phone'])->first();
            $checkCustomer = Customer::where('phone', $credentials['phone'])->first();

            if(!$checkUser && !$checkCustomer){
                throw new \Exception("Tên đăng nhập không tồn tại!");
            }
            $user = null;
            $role = '';
            $abilities = [];
            if ($checkUser && $this->guardManager()->attempt($credentials)) {
                $user = $this->guardManager()->user();
                $abilities[] = 'employee';
                $role = 'employee';
                if($user->is_admin){
                    $abilities[] = 'admin';
                    $role = "admin";
                }
            }
            if($checkCustomer && $this->guardCustomer()->attempt($credentials)){
                $user = $this->guardCustomer()->user();
                $abilities[] = 'customer';
                $role = 'customer';
            }

            if($user){
                $tokenResult = $user->createToken("$role:".$user->id .';'.now(), $abilities, now()->addYear());
                $token = $tokenResult->plainTextToken;
                DB::commit();
                $responseData = [
                    "token" => $token,
                    "type" => "Bearer",
                    "abilities" => $abilities,
                    "user" => $user
                ];
                return $this->success($responseData, "Đăng nhập thành công");
            }
            throw new \Exception("Mật khẩu không đúng!");
        } catch (\Throwable $e){
            DB::rollback();
            return $this->failure($e->getMessage(), $e->getLine());
        }
    }

    public function logout(Request $request){
        try {
            $user = $request->user();
            if(!$request->user()->currentAccessToken()->delete()){
                return $this->failure('Đăng xuất không thành công');
            };
            return $this->success([], 'Đăng xuất thành công');
        } catch(\Throwable $e) {
            return $this->failure();
        }
    }

    public function signup(Request $request){
        try {
            $data = $request->all();

            $customerData = [
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? '',
                'district' => $data['district'] ?? '',
                'province' => $data['province'] ?? '',
                'responsible_staff' => 0,
                'password' => Hash::make($data["password"]),
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

    protected function guardManager() {
        return Auth::guard('manager');
    }

    protected function guardCustomer() {
        return Auth::guard('customer');
    }
}
