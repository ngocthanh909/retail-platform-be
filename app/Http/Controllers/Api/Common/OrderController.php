<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateOrderRequest;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\OrderEditRequest;
use App\Http\Requests\OrderRequest;
use Illuminate\Http\Request;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Models\Category;
use App\Models\Config;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    use ApiResponseTrait, NotificationTrait;

    public function detail(Request $request, $id)
    {
        try {
            $order = Order::with(['details', 'staff', 'creator'])->findOrFail($id);
            return $this->success($order);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy đơn hàng này', $e->getMessage());
            }
            return $this->failure('Lỗi truy vấn đơn hàng', $e->getMessage());
        }
    }

    public function calculateOrder(CalculateOrderRequest $request)
    {
        try {
            $data = $request->all();
            $user = auth('sanctum')->user();
            $responseData = $this->calculate($data, $user);
            return $this->success($responseData);
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Lỗi khi tính giá trị đơn hàng', $e->getMessage());
        }
    }

    public function checkout(CheckoutRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $user = auth('sanctum')->user();

            $customer = Customer::find($request->customer_id);
            $responseData = $this->calculate($data, $user);


            $order = new Order([
                'customer_id' => $customer->id ?? 0,
                'responsible_staff' => $customer->responsible_staff ?? 0,
                'creator_id' => ($user->tokenCan('admin') || $user->tokenCan('employee')) ? $user->id : '',
                'customer_name' => $customer->customer_name ?? ($request->customer_name ?? ''),
                'phone' => $customer->phone ?? ($request->customer_phone ?? ''),
                'province' => $customer->province ?? '',
                'district' => $customer->district ?? '',
                'address' => $customer->address ?? ($request->customer_address ?? ''),
                'subtotal' => $responseData['subtotal'],
                'total' => $responseData['total'],
                'discount_code' => $request->discount_code,
                'discount' => $responseData['discount'],
                'discount_note' => $responseData['discount_description'],
                'note' => $data['note'] ?? '',
                'status' => 1
            ]);
            if (!$order->save()) throw new Exception('Lỗi trong quá trình tạo đơn hàng');

            $orderDetails = [];

            foreach ($responseData['products'] ?? [] as $product) {
                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'sku' => $product['sku'] ?? '',
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'qty' => $product['qty'],
                    'discount' => 0,
                    'total' => $product['price'] * $product['qty'],
                    'product_image' => $product['product_image'],
                ];
            }
            if (!OrderDetail::insert($orderDetails)) {
                throw new Exception('Lỗi khi tạo chi tiết đơn hàng');
            };
            DB::commit();
            $order = $order->load('details');
            $this->sendNotification(
                $user->id,
                1,
                null,
                false,
                'Tạo đơn hàng thành công',
                'Bạn đã tạo đơn hàng thành công. Mã đơn hàng của bạn là ' . $order->displayId,
                ''
            );
            $adminReceiver = [1];

            if($customer->responsible_staff){
                $adminReceiver[] = $customer->responsible_staff;
            }
            $this->sendNotification(
                $adminReceiver,
                0,
                null,
                false,
                'Bạn có đơn hàng mới',
                'Khách hàng ' . $user->customer_name . ' vừa đặt thành công đơn hàng ' . $order->displayId,
                ''
            );
            return $this->success($order);
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();
            return $this->failure('Lỗi khi tạo đơn hàng', $e->getMessage());
        }
    }

    public function edit(OrderEditRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $user = $request->user();

            $customer = Customer::find($request->customer_id);
            $responseData = $this->calculate($data, $user);

            $order = Order::findOrFail($id);

            $order->fill([
                'customer_id' => $customer->id,
                'responsible_staff' => $customer->responsible_staff,
                'creator_id' => ($user->tokenCan('admin') || $user->tokenCan('employee')) ? $user->id : '',
                'customer_name' => $customer->customer_name,
                'phone' => $customer->phone,
                'province' => $customer->province,
                'district' => $customer->district,
                'address' => $customer->address,
                'subtotal' => $responseData['subtotal'],
                'total' => $responseData['total'],
                'discount_code' => $request->discount_code,
                'discount' => $responseData['discount'],
                'discount_note' => $responseData['discount_description'],
                'note' => $data['note'] ?? '',
                'status' => $data['status'] ?? $order->status
            ]);
            if (!$order->save()) throw new Exception('Lỗi trong quá trình cập nhật đơn hàng');

            $orderDetails = [];
            OrderDetail::where('order_id', $order->id)->delete();

            foreach ($responseData['products'] ?? [] as $product) {
                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'sku' => $product['sku'] ?? '',
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'qty' => $product['qty'],
                    'total' => $product['price'] * $product['qty'],
                    'product_image' => $product['product_image'],
                    'discount' => 0
                ];
            }
            if (!OrderDetail::insert($orderDetails)) {
                throw new Exception('Lỗi khi cập nhật chi tiết đơn hàng');
            };
            DB::commit();
            $order = $order->load('details');
            $this->sendNotification(
                $customer->id,
                1,
                null,
                false,
                'Sửa đơn hàng',
                'Bạn đã sửa đơn hàng ' . $order->displayId . ' thành công',
                ''
            );
            if($customer->responsible_staff){
                $adminReceiver[] = $customer->responsible_staff;
            }
            $this->sendNotification(
                $adminReceiver,
                0,
                null,
                false,
                'Sửa',
                'Đơn của ' . $order->displayId . ' khách hàng ' . $user->customer_name . ' vừa được chỉnh sửa ',
                ''
            );

            return $this->success($order);
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();
            return $this->failure('Lỗi khi cập nhật đơn hàng', $e->getMessage());
        }
    }


    public function changeStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);

            if (((int)$request->status < 0) || ((int)$request->status > 3)) {
                return $this->failure('Trạng thái không hợp lệ');
            }
            $orderUpdate = Order::with('details')->where('id', $id)->update(['status' => $request['status']]);
            $customer = Customer::find($order->customer_id);
            $user = $request->user();
            if (!is_numeric($orderUpdate)) {
                return $this->failure('Lỗi cập nhật đơn hàng');
            }
            DB::commit();
            $messageUser = '';
            $messageAdmin = '';
            $title = '';
            if($request->status == 2){
                $title = 'Xác nhận đơn hàng';
                $messageUser = 'Đơn hàng ' . $order->displayId .  ' của bạn đã được xác nhận';
                $messageAdmin = 'Đơn hàng ' . $order->displayId . ' đã được xác nhận bởi ' . $user->name;
            }
            if($request->status == 3){
                $title = 'Hoàn thành đơn hàng';
                $messageUser = 'Đơn hàng ' . $order->displayId . ' của bạn đã hoàn thành';
                $messageAdmin = 'Đơn hàng ' . $order->displayId . 'đã hoàn thành bởi ' . $user->name;
            }
            if($request->status == 0){
                $title = 'Hủy nhận đơn hàng';
                $messageUser = 'Đơn hàng ' . $order->displayId . ' của bạn đã bị hủy bởi nhân viên phụ trách.';
                $messageAdmin = 'Đơn hàng ' . $order->displayId . 'đã bị hủy bởi ' . $user->name;
            }

            $this->sendNotification(
                $customer->id,
                1,
                now(),
                false,
                $title,
                $messageUser,
                ''
            );
            $adminReceiver = [1];
            if($customer->responsible_staff){
                $adminReceiver[] = $customer->responsible_staff;
            }
            $this->sendNotification(
                $adminReceiver,
                0,
                now(),
                false,
                $title,
                $messageAdmin,
                ''
            );

            return $this->success([], 'Đơn hàng đã sửa trạng thái thành công');
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy đơn hàng này', $e->getMessage());
            }
            return $this->failure('Lỗi cập nhật đơn hàng', $e->getMessage() . $e->getLine());
        }
    }

    public function changeMultipleStatus(Request $request)
    {
        $ids = $request->ids ?? [];
        DB::beginTransaction();
        $user = $request->user();
        try {
            $successCount = 0;
            foreach($ids as $id){
                if (((int)$request->status < 0) || ((int)$request->status > 3)) {
                    return $this->failure('Trạng thái không hợp lệ');
                }
                $orderUpdate = Order::with('details')->where('id', $id)->update(['status' => $request['status']]);
                $order = Order::find($id);
                $customer = Customer::find($order->customer_id);
                if (!is_numeric($orderUpdate)) {
                    return $this->failure('Lỗi cập nhật đơn hàng');
                }
                $successCount++;
                $messageUser = '';
                $messageAdmin = '';
                $title = '';
                if($request->status == 2){
                    $title = 'Xác nhận đơn hàng';
                    $messageUser = 'Đơn hàng ' . $order->displayId .  ' của bạn đã được xác nhận';
                    $messageAdmin = 'Đơn hàng ' . $order->displayId . ' đã được xác nhận bởi ' . $user->name;
                }
                if($request->status == 3){
                    $title = 'Hoàn thành đơn hàng';
                    $messageUser = 'Đơn hàng ' . $order->displayId . ' của bạn đã hoàn thành';
                    $messageAdmin = 'Đơn hàng ' . $order->displayId . 'đã hoàn thành bởi ' . ($user->name ?? $customer->customer_name);
                }
                if($request->status == 0){
                    $title = 'Hủy đơn hàng';
                    $messageUser = 'Đơn hàng ' . $order->displayId . ' của bạn đã bị hủy bởi nhân viên.';
                    $messageAdmin = 'Đơn hàng ' . $order->displayId . 'đã bị hủy bởi ' . ($user->name ?? $customer->customer_name);
                }

                $this->sendNotification(
                    $user->id,
                    1,
                    now(),
                    false,
                    $title,
                    $messageUser,
                    ''
                );
                if($customer->responsible_staff){
                    $adminReceiver[] = $customer->responsible_staff;
                }
                $this->sendNotification(
                    $adminReceiver,
                    0,
                    now(),
                    false,
                    $title,
                    $messageAdmin,
                    ''
                );
            }
            DB::commit();
            return $this->success([], $successCount .' đơn hàng đã sửa trạng thái thành công');
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy đơn hàng này', $e->getMessage());
            }
            return $this->failure('Lỗi cập nhật đơn hàng', $e->getMessage());
        }
    }
    public function calculate($data, $user)
    {
        try {
            $rawProducts = $data['products'] ?? [];
            $products = [];
            $isGuest = false;

            //Order variable
            $subTotal = 0;
            $total = 0;

            if ($user->tokenCan('customer')) {
                if (empty($user?->responsible_staff)) {
                    $isGuest = false;
                }
            } else {
                //Check logic customer is a member or not
                $customer = Customer::find($data['customer_id']);
                if (($data['customer_id'] != 0) && !$customer) {
                    throw new Exception("Không tìm thấy cửa hàng này");
                }
                if (empty($customer?->responsible_staff)) {
                    $isGuest = true;
                }
            }

            if (count($rawProducts) > 0) {
                foreach ($rawProducts as $rawProduct) {
                    $product = Product::find($rawProduct['id']);
                    if (!$product) {
                        throw new Exception("Không tìm thấy sản phẩm trong danh sách");
                    }
                    $tempPrice = $product->price + ($isGuest ? ($product->price * ((int)(Config::getConfig('discount') ?? 0)) / 100) : 0);
                    $tempTotal = $tempPrice * $rawProduct['qty'];
                    $products[] = [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku ?? '',
                        'product_image' => $product->product_image,
                        'price' => $tempPrice,
                        'total' => $tempTotal,
                        'qty' => $rawProduct['qty'],
                        'total' => $product->price * $rawProduct['qty'],
                        'product_image' => $product->getRawOriginal('product_image'),
                    ];
                    $subTotal += $tempTotal;
                }
            }

            $total = $subTotal;
            $responseData = [
                'products' => $products,
                'discount' => 0,
                'discount_description' => 'Nếu bạn được tặng quà thì ghi chú ở đây. Nếu được giảm giá thì discount trả về giá trị theo số tiền dc giảm',
                'subtotal' => $subTotal,
                'total' => $total,
            ];
            return $responseData;
        } catch (\Throwable $e) {
            Log::error($e);
            throw new Exception($e->getMessage());
        }
    }

    public function list(Request $request)
    {
        $data = $request->all();
        $query = new Order();
        $user = auth('sanctum')->user();
        if ($user->tokenCan('customer')) {
            $query = $query->where('customer_id', $user->id);
        } else {
            if(!$user->tokenCan('admin')){
                $query = $query->where('creator_id', $user->id);
            }
            if ($request->customer_id) {
                $query = $query->where('customer_id', $request->customer_id);
            }
        }
        if ($request->id) {
            $query = $query->where('id', 'like', '%' . $request->id . '%');
        }
        if ($request->responsible_staff) {
            $query = $query->where('responsible_staff', $request->responsible_staff);
        }
        if ($request->creator) {
            $query = $query->where('creator', $request->creator);
        }

        if ($request->min_price) {
            $query = $query->where('total', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query = $query->where('total', '<=', $request->max_price);
        }

        if ($request->min_date) {
            $query = $query->where('created_at', '>=', $request->min_date);
        }
        if ($request->max_date) {
            $query = $query->where('created_at', '<=', $request->max_date);
        }

        if (($request->status !== null) && is_numeric($request->status)) {

            $query = $query->where('status', $request->status);
        }

        $orders = $query->with(['staff', 'creator'])->orderBy('created_at', 'DESC')->paginate(config('paginate.order'));
        return $this->success($orders);
    }
}
