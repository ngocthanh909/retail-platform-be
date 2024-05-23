<?php

namespace App\Http\Controllers\Api\Manager;

use App\Console\Commands\SendAutoNotification;
use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Jobs\SendAutomaticNotification;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationManagerController extends Controller
{
    use ApiResponseTrait, NotificationTrait;

    function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $notification = new Notification([
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'image' => '',
                'is_manual' => true
            ]);
            if (!$notification->save()) {
                throw new Exception('Có lỗi khi tạo thông báo');
            }
            $notificationDeliveryList = [];
            $shouldSendNotificationCustomer = [];
            if ($data['receiver_id'] == 0) {
                $customers = Customer::where('status', 1)->get();
                foreach ($customers as $customer) {
                    $shouldSendNotificationCustomer[] = $customer->device_token ?? '';
                    $notificationDeliveryList[] = [
                        'receiver_id' => $customer->id,
                        'user_type' => 1,
                        'notification_id' => $notification->id ?? 0,
                        'delivery_time' => $data['delivery_time'] ?? now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            } else {
                $customer = Customer::find($data['receiver_id']);
                $shouldSendNotificationCustomer[] = $customer->device_token ?? '';
                $notificationDeliveryList[] = [
                    'receiver_id' => $data['receiver_id'],
                    'user_type' => 1,
                    'notification_id' => $notification->id ?? 0,
                    'delivery_time' => $data['delivery_time'] ?? now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            };
            $deliveryNotification = NotificationDelivery::insert($notificationDeliveryList);
            if (!$deliveryNotification) {
                throw new Exception('Lỗi khi gửi cho từng khách hàng');
            }
            DB::commit();

            if (!$data['delivery_time']) {
                foreach ($shouldSendNotificationCustomer as $receiverToken) {
                    SendAutomaticNotification::dispatch($receiverToken, $notification->title ?? '', $notification->content ?? '');
                    $this->sendFirebaseNotification($receiverToken, $notification->title ?? '', $notification->content ?? '');
                }
            }
            return $this->success([], 'Gửi thông báo thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function list(Request $request)
    {
        try {
            $notifications = Notification::where('is_manual', true)->paginate(config('store_list'));
            return $this->success($notifications, 'Gửi thông báo thành công!');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function edit(Request $request)
    {
        try {
            $sendNotifi = $this->sendNotification(
                $request->ids,
                1,
                $request->delivery_time,
                false,
                $request->title ?? '',
                $request->content ?? '',
                '',
                false
            );
            if (!$sendNotifi) {
                return $this->failure('Gửi thông báo thành không thành công!');
            }
            return $this->success([], 'Gửi thông báo thành công!');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function seen(Request $request)
    {
        try {
            $seenNotifi = $this->seen($request->id);
            if (!$seenNotifi) {
                return $this->failure('Không thể đánh dấu đã xem!');
            }
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Không thể đánh dấu đã xem!', $e->getMessage());
        }
    }

    function delete(Request $request)
    {
        try {
            $deleteNotifi = $this->deleteNotificationStrategy($request->id);
            if (!$deleteNotifi) {
                return $this->failure('Xóa thông báo không thành công!');
            }
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Xóa thông báo không thành công!', $e->getMessage());
        }
    }
}
