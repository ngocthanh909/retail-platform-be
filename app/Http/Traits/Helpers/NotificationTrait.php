<?php
/**
 * Created by PhpStorm.
 * User: Bawa, Lakhveer
 * Email: iamdeep.dhaliwal@gmail.com
 * Date: 2020-06-14
 * Time: 12:18 p.m.
 */

namespace App\Http\Traits\Helpers;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait NotificationTrait
{
    function sendNotification(mixed $id, $userType = 0, $sendTime = null, $isManual = false, string $title = '', string $content = '', $image = '', $sendNow = true){
        $templateId = $this->generateTemplate($title, $content, $image, $isManual);
        if(is_array($id)){
            foreach($id as $receiverId){
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

        return $notification->save();
    }
    function seenNotification($id){
        $seen = Notification::where('id', $id)->update(['seen' => 1]);
        return $seen;
    }

    function deleteNotification($id){
        $notification = Notification::where('id', $id)->where('receiver_id', request()->user()?->id)->delete();
        return $notification;
    }
    function deleteNotificationStrategy($id){
        $notification = NotificationTemplate::find($id);
        if(!$notification){
            return false;
        }
        DB::beginTransaction();
        if(Notification::where('template_id', $id)->delete() &&  $notification->delete()){
            DB::commit();
            return true;
        }
        DB::rollback();
        return false;
    }
    function generateTemplate($title = '', $content = '', $image = '', $isManual){
        if(!empty($content)){
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
    function sendFirebaseNotification(){

    }
}
