<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    use ApiResponseTrait;

    public function list(Request $request)
    {
        try {
            $users = new User();
            $keyword = $request->keyword;
            if (!empty($keyword)) {
                $users = $users->where('name', 'like', "%$keyword%");
            }
            $users = $users->orderBy('name', 'DESC')->paginate(config('paginate.store_list'));
            return $this->success($users);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failure('Lỗi khi lấy danh sách nhân viên');
        }
    }

    public function detail(Request $request, $id)
    {
        try {
            $user = User::with('customers')->findOrFail($id);
            return $this->success($user);
        } catch (\Throwable $e) {
            Log::error($e);
            $message = 'Lỗi khi truy vấn nhân viên';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy nhân viên này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    function create(UserRequest $request)
    {
        $fileName = '';
        try {
            $data = $request->validated();
            $user = new User([
                "name" => $request->name,
                "phone" => $request->phone,
                "email" => $request->email,
                "is_admin" => 0,
                "password" => Hash::make($request->password),
                "gender" => $data['gender'],
                "dob" => $data['dob'],
                "avatar" => '',
                "address" => $data['address'] ?? ''
            ]);

            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;


            if($file){
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($data['phone']) . '.' . $file->extension(),
                    'public'
                );
                $user->avatar = $fileName;
            };
            if ($user->save()) {
                return $this->success("Tạo tài khoản thành công!");
            };
            return $this->failure("Tạo tài khoản thất bại");
        } catch(\Throwable $e){
            return $this->failure('', $e->getMessage());
        }
    }

    function edit(UserRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $user = User::findOrFail($id);
            $user->fill([
                "name" => $request->name,
                "phone" => $request->phone,
                "email" => $request->email,
                "is_admin" => 0,
                "gender" => $data['gender'] ?? true,
                "dob" => $data['dob'] ?? null,
                "address" => $data['address'] ?? ''
            ]);

            if(!empty($data['password'])){
                $user->password = Hash::make($data['password']);
            }

            $file = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $fileName = '';

            if($file){
                $fileName = $file->storePubliclyAs(
                    'images/users',
                    Str::slug($data['phone']) . '.' . $file->extension(),
                    'public'
                );
                $user->avatar = $fileName;
            };
            if ($user->save()) {
                return $this->success($user, "Sửa tài khoản thành công!");
            };
            return $this->failure("Sửa tài khoản thất bại");
        } catch(\Throwable $e){
            if($e instanceof ModelNotFoundException){
                return $this->failure('Không tìm thấy nhân viên này', $e->getMessage());
            }
            return $this->failure('Lỗi khi sửa nhân viên', $e->getMessage());
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            if($id === 1){
                throw new Exception("Không thể xóa tài khoản admin");
            }

            if (!$user->delete()) {
                return $this->failure("Lỗi khi xóa tài khoản");
            }
            return $this->success([], 'Xóa tài khoản thành công');
        } catch (\Throwable $e) {
            $message = 'Lỗi khi xóa thông tin tài khoản';
            if ($e instanceof ModelNotFoundException) {
                $message = 'Không tìm thấy tài khoản này!';
            }
            return $this->failure($message, $e->getMessage());
        }
    }

    function listAllEmployee(){

        try {
            return $this->success(User::where('is_admin', 0)->get());
        } catch (\Exception $e) {
            Log::error($e);
            return $this->failure('Lỗi khi lấy danh sách nhân viên');
        }

    }

}
