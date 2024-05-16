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
use App\Models\NotificationTemplate;
use App\Models\User;
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
        }

        $save = $notification->save();
        $user = null;

        if($sendNow){
            if($userType == 0){
                $user = User::find($id);
            } else {
                $user = Customer::find($id);
            }
            if($user && $user->device_token){
                SendAutomaticNotification::dispatch('', $title, $content);
            }
        }

        return $save;
    }
    function seenNotification($id)
    {
        $seen = Notification::where('id', $id)->update(['seen' => 1]);
        return $seen;
    }

    function deleteNotification($id)
    {
        $notification = Notification::where('id', $id)->where('receiver_id', request()->user()?->id)->delete();
        return $notification;
    }
    function deleteNotificationStrategy($id)
    {
        $notification = NotificationTemplate::find($id);
        if (!$notification) {
            return false;
        }
        DB::beginTransaction();
        if (Notification::where('template_id', $id)->delete() &&  $notification->delete()) {
            DB::commit();
            return true;
        }
        DB::rollback();
        return false;
    }
    function generateTemplate($title = '', $content = '', $image = '', $isManual)
    {
        if (!empty($content)) {
            $notification = new NotificationTemplate([
                'is_manual' => $isManual,
                'title' => $title,
                'content' => $content,
                'image' => ''
            ]);
            $notification->save();
            return $notification->id;
        }
        return false;
    }
    function sendFirebaseNotification($token, $title = 'Đăng Khoa', $content = '')
    {
        $authKeyContent = json_decode(File::get(storage_path('firebase-adminsdk.json')), true);
        $projectID = config('app.fcm_app_name');
        $body = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $content,
                ],
                'data' => [
                    'story_id' => 'notification',
                ],
            ],
        ];

        $bearerToken = FCM::getBearerToken($authKeyContent);

        FCM::send($bearerToken, $projectID, $body);
    }
}
