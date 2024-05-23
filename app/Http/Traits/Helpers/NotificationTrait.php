<?php

/**
 * Created by PhpStorm.
 * User: Bawa, Lakhveer
 * Email: iamdeep.dhaliwal@gmail.com
 * Date: 2020-06-14
 * Time: 12:18 p.m.
 */

namespace App\Http\Traits\Helpers;

use App\Jobs\SendAutomaticNotification;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use App\Models\User;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Kedniko\FCM\FCM;

trait NotificationTrait
{
    function sendNotification(mixed $id, $userType = 0, $sendTime = null, $isManual = false, string $title = '', string $content = '', $image = '', $sendNow = true)
    {
        $templateId = $this->generateTemplate($title, $content, $image, $isManual);
        if (is_array($id)) {
            foreach ($id as $receiverId) {
                $notification = new Notification([
                    'template_id' => $templateId,
                    'receiver_id' => $receiverId ?? 0,
                    'delivery_time' => $sendTime,
                    'user_type' => $userType,
                    'seen' => 0,
                    'sent' => $sendNow
                ]);
                $notification->save();

                if ($sendNow) {
                    if ($userType == 0) {
                        $user = User::find($receiverId);
                    } else {
                        $user = Customer::find($receiverId);
                    }
                    if ($user && $user->device_token) {
                        SendAutomaticNotification::dispatch($user->device_token, $title, $content);
                    }
                }
            }
        } else {
            $notification = new Notification([
                'template_id' => $templateId,
                'receiver_id' => $id,
                'delivery_time' => $sendTime,
                'user_type' => $userType,
                'seen' => 0,
                'sent' => $sendNow
            ]);
            $notification->save();
            if ($sendNow) {
                if ($userType == 0) {
                    $user = User::find($id);
                } else {
                    $user = Customer::find($id);
                }
                if ($user && $user->device_token) {
                    SendAutomaticNotification::dispatch($user->device_token, $title, $content);
                }
            }
        }

        $save = $notification->save();
        $user = null;

        // try {

        // } catch(\Throwable $e){
        //     dd($e);
        // }

        return $save;
    }

    function sendUserNotification($receiver = 'admin', $title, $content)
    {
        DB::beginTransaction();
        try {
            $receiverIds = [];
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

            if (!is_array($receiver) && is_string($receiver) && ($receiver == 'admin')) {
                $admins = User::where('is_admin', 1)->get();
                $receiverIds = data_get($admins, '*.id');
            }
            if (is_array($receiver)) {
                $receiverIds = $receiver;
            }
            foreach ($receiverIds as $userId) {
                $user = User::where('id', $userId)->first();
                $shouldSendNotificationCustomer[] = $user->device_token ?? '';
                $notificationDeliveryList[] = [
                    'receiver_id' => $userId,
                    'user_type' => 0,
                    'notification_id' => $notification->id ?? 0,
                    'delivery_time' => $data['delivery_time'] ?? now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            $deliveryNotification = NotificationDelivery::insert($notificationDeliveryList);
            if (!$deliveryNotification) {
                throw new Exception('Lỗi khi gửi cho từng khách hàng');
            }
            DB::commit();

            foreach ($shouldSendNotificationCustomer as $receiverToken) {
                SendAutomaticNotification::dispatch($receiverToken, $notification->title ?? '', $notification->content ?? '');
                // $this->sendFirebaseNotification($receiverToken, $notification->title ?? '', $notification->content ?? '');
            }

            return $this->success([], 'Gửi thông báo thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function seenNotification($id)
    {
        $seen = NotificationDelivery::where('id', $id)->update(['seen' => 1]);
        return $seen;
    }

    function seenAllNotification($user)
    {
        $type = $user->tokenCan('customer') ? 1 : 0;
        $seen = NotificationDelivery::where('user_type', $type)->where('receiver_id', $user->id)->where('seen', 0)->update(['seen' => 1]);
        return $seen;
    }

    function deleteNotification($id)
    {
        $notification = Notification::where('id', $id)->where('receiver_id', request()->user()?->id)->delete();
        return $notification;
    }

    function deleteAllNotification($id)
    {
        $notification = Notification::where('id', $id)->where('receiver_id', request()->user()?->id)->delete();
        return $notification;
    }


    function sendFirebaseNotification($token, $title = 'Đăng Khoa', $content = '')
    {
        try {
            $authKeyContent = json_decode(File::get(storage_path('firebase-adminsdk.json')), true);
            $projectID = config('app.fcm_app_name');
            Log::info("Send to $token");
            $body = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $content,
                    ],
                    'data' => [
                        'story_id' => 'notification',
                    ]
                ],
            ];

            $bearerToken = FCM::getBearerToken($authKeyContent);

            FCM::send($bearerToken, $projectID, $body);
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }
}
