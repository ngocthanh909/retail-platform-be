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

    private $operator = '';

    public function __construct()
    {
        $user = auth('sanctum')->user();
        if ($user->tokenCan('customer')) {
            $this->operator = 'customer';
        } else {
            $this->operator = $user->tokenCan('admin') ? 'admin' : 'employee';
        }
    }

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
                'responsible_staff' => $customer?->responsible_staff ?? 0,
                'creator_id' => ($user->tokenCan('admin') || $user->tokenCan('employee')) ? $user->id : 0,
                'customer_name' => $customer->customer_name ?? ($request->customer_name ?? ''),
                'phone' => $customer->phone ?? ($request->customer_phone ?? ''),
                'province' => $customer->province ?? '',
                'district' => $customer->district ?? '',
                'province_id' => $customer->province_id ?? 0,
                'district_id' => $customer->district_id ?? 0,
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
            $orderCommission = 0;
            foreach ($responseData['products'] ?? [] as $product) {
                $originalProduct = Product::join('categories', 'products.category_id', 'categories.id')->where('products.id', $product['id'])
                    ->select('categories.id', 'categories.category_name', 'categories.commission_rate')->first();

                $totalCommission = $originalProduct->commission_rate * ($product['price'] * $product['qty']) / 100;
                $orderCommission += $totalCommission;
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
                    'category_id' => $originalProduct->id ?? 0,
                    'category_name' => $originalProduct->category_name ?? '',
                    'category_commission_rate' => $originalProduct->commission_rate ?? 0,
                    'category_commission_amount' => $totalCommission
                ];
            }

            if (!OrderDetail::insert($orderDetails)) {
                throw new Exception('Lỗi khi tạo chi tiết đơn hàng');
            };
            DB::commit();
            $order->total_commission = $orderCommission;
            $order->save();
            $order = $order->load(['details', 'staff']);
            $staffName = $order->staff?->name;
            $orderId = $order->displayId;
            $customerName = $order->customer_name;

            switch ($this->operator) {
                case 'admin':
                    $this->sendUserNotification('admin', 'Tạo đơn hàng thành công', "Bạn đã tạo thành công đơn hàng $orderId cho cửa hàng $customerName");
                    if ($customer) {
                        $this->sendUserNotification($customer?->responsible_staff ?? '', 'Đơn hàng mới từ Admin', "Admin đã tạo đơn hàng mới cho cửa hàng $customerName do bạn phụ trách. Mã đơn hàng là $orderId");
                        $this->sendCustomerNotification($customer?->id ?? '', 'Đơn hàng mới từ khách hàng', "Khách hàng $customerName đã tạo đơn hàng mới, mã đơn hàng $orderId");
                    }
                    break;
                case 'employee':
                    $this->sendUserNotification('admin', "Đơn hàng mới từ $staffName",  "$staffName đã tạo đơn hàng cho cửa hàng $customerName. Mã đơn hàng là $orderId");
                    if ($customer) {
                        $this->sendUserNotification($customer?->responsible_staff ?? '', "Tạo đơn hàng thành công", "Bạn đã tạo thành công đơn hàng $orderId cho cửa hàng $customerName");
                        $this->sendCustomerNotification($customer?->id ?? '', "Đơn hàng mới từ khách hàng", "Khách hàng $customerName đã tạo đơn hàng mới, mã đơn hàng $orderId");
                    }
                    break;
                case 'customer':
                    $this->sendUserNotification('admin', "Đơn hàng mới từ khách hàng",  "$customerName đã tạo thành công đơn hàng $orderId");
                    if ($customer) {
                        $this->sendCustomerNotification($customer?->id ?? '', 'Tạo đơn hàng mới thành công', "Bạn đã tạo thành công đơn hàng mới, mã đơn hàng $orderId");
                        $this->sendUserNotification($customer?->responsible_staff ?? '', "Đơn hàng mới từ khách hàng",  "$customerName đã tạo thành công đơn hàng $orderId");
                    }
                    break;
            }
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
                'responsible_staff' => $customer?->responsible_staff,
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

            $orderCommission = 0;
            foreach ($responseData['products'] ?? [] as $product) {
                $originalProduct = Product::join('categories', 'products.category_id', 'categories.id')->where('products.id', $product['id'])
                    ->select('categories.id', 'categories.category_name', 'categories.commission_rate')->first();
                $totalCommission = $originalProduct->commission_rate * ($product['price'] * $product['qty']) / 100;
                $orderCommission += $totalCommission;

                $orderDetails[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'sku' => $product['sku'] ?? '',
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'qty' => $product['qty'],
                    'total' => $product['price'] * $product['qty'],
                    'product_image' => $product['product_image'],
                    'discount' => 0,
                    'category_id' => $originalProduct->id ?? 0,
                    'category_name' => $originalProduct->category_name ?? '',
                    'category_commission_rate' => $originalProduct->commission_rate ?? 0,
                    'category_commission_amount' => $totalCommission
                ];
            }
            if (!OrderDetail::insert($orderDetails)) {
                throw new Exception('Lỗi khi cập nhật chi tiết đơn hàng');
            };
            $order->total_commission = $orderCommission;
            $order->save();
            DB::commit();
            $order = $order->load('details');
            $order = $order->load(['details', 'staff']);

            $staffName = $order->staff?->name ?? '';
            $orderId = $order->displayId;
            $customerName = $order->customer_name;

            switch ($this->operator) {
                case 'admin':
                    $this->sendUserNotification('admin', 'Sửa đơn hàng thành công', "Bạn đã sửa thành công đơn hàng $orderId cho cửa hàng $customerName");
                    if ($customer) {
                        $this->sendUserNotification($customer?->responsible_staff ?? '', 'Đơn hàng được cập bởi Admin', "Admin đã sửa đơn hàng mới cho cửa hàng $customerName do bạn phụ trách. Mã đơn hàng là $orderId");
                        $this->sendCustomerNotification($customer->id, 'Cập nhật đơn hàng', "Admin đã sửa đơn hàng $orderId của bạn");
                    }
                    break;
                case 'employee':
                    $this->sendUserNotification('admin', "Đơn hàng được cập bởi $staffName",  "$staffName đã sửa đơn hàng cho cửa hàng $customerName. Mã đơn hàng là $orderId");
                    if ($customer) {
                        $this->sendUserNotification($customer?->responsible_staff ?? '', "Sửa đơn hàng thành công", "Bạn đã sửa thành công đơn hàng $orderId cho cửa hàng $customerName");
                        $this->sendCustomerNotification($customer->id, "Cập nhật đơn hàng", "Admin đã sửa đơn hàng $orderId của bạn");
                    }
                    break;
            }


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


            if (!is_numeric($orderUpdate)) {
                return $this->failure('Lỗi cập nhật đơn hàng');
            }
            DB::commit();

            $title = '';
            $message = '';

            if ($request->status == 2) {
                $title = 'Thông báo xác nhận đơn hàng ' . $order->displayId;
                $message = 'Đơn hàng ' . $order->displayId .  ' đã được xác nhận';
            }
            if ($request->status == 3) {
                $title = 'Thông báo hoàn thành đơn hàng ' . $order->displayId;
                $message = 'Đơn hàng ' . $order->displayId .  ' đã được xác nhận';
            }
            if ($request->status == 0) {
                $title = 'Thông báo hủy đơn hàng';
                $message = 'Đơn hàng ' . $order->displayId .  ' đã bị hủy';
            }

            $this->sendUserNotification('admin', $title, $message);
            if ($customer) {
                $this->sendUserNotification($order->responsible_staff ?? '', $title, $message);
                $this->sendCustomerNotification($customer->id, $title, $message);
            }


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

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $ids = $request->ids;
            $delete = Order::whereIn('id', $ids)->delete();
            $deleteDetail = OrderDetail::where('order_id', $ids)->delete();
            if (!$delete || !$deleteDetail) {
                throw new \Exception('Lỗi xóa đơn hàng');
            }
            DB::commit();
            return $this->success([], 'Đơn hàng đã xóa thành công');
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Không tìm thấy đơn hàng này', $e->getMessage());
            }
            return $this->failure('Lỗi xóa đơn hàng', $e->getMessage() . $e->getLine());
        }
    }

    public function changeMultipleStatus(Request $request)
    {
        $ids = $request->ids ?? [];
        DB::beginTransaction();
        $user = $request->user();
        try {
            $successCount = 0;
            foreach ($ids as $id) {
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

                $title = '';
                $message = '';

                if ($request->status == 2) {
                    $title = 'Thông báo xác nhận đơn hàng ' . $order->displayId;
                    $message = 'Đơn hàng ' . $order->displayId .  ' đã được xác nhận';
                }
                if ($request->status == 3) {
                    $title = 'Thông báo hoàn thành đơn hàng ' . $order->displayId;
                    $message = 'Đơn hàng ' . $order->displayId .  ' đã được xác nhận';
                }
                if ($request->status == 0) {
                    $title = 'Thông báo hủy đơn hàng';
                    $message = 'Đơn hàng ' . $order->displayId .  ' đã bị hủy';
                }

                $this->sendUserNotification('admin', $title, $message);
                if ($customer) {
                    $this->sendUserNotification($customer?->responsible_staff ?? '', $title, $message);
                    $this->sendCustomerNotification($customer->id, $title, $message);
                }
            }
            DB::commit();
            return $this->success([], $successCount . ' đơn hàng đã sửa trạng thái thành công');
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
                    $isGuest = true;
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
            if ($user->tokenCan('employee') && !$user->tokenCan('admin')) {
                $query = $query->where('responsible_staff', $user->id);
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
            $query = $query->where('creator_id', $request->creator);
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
