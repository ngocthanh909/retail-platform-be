<?php

namespace App\Http\Controllers\Api\Authenticate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Http\Traits\Helpers\ApiResponseTrait;
use Illuminate\Http\Response;
use Laravel\Passport\Token;

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
            if (!$this->guardAdmin()->attempt($credentials)) {
                return $this->failure('Sai tên đăng nhập hoặc mật khẩu', [], 500);
            }
            $user = $this->guardAdmin()->user();
            $role = 'employee';
            $abilities = ['employee'];
            if($user->is_admin){
                $abilities[] = 'admin';
                $role = "admin";
            }
            $tokenResult = $user->createToken("$role:".$user->id .';'.now(), $abilities, now()->addYear());
            $token = $tokenResult->plainTextToken;
            DB::commit();

            $responseData = [
                "token" => $token,
                "type" => "Bearer",
                "abilities" => $abilities
            ];
            return $this->success($responseData, "Đăng nhập thành công");
        } catch (\Throwable $e){
            DB::rollback();
            return $this->failure();
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

    protected function guardAdmin() {
        return Auth::guard('admin');
    }
}
