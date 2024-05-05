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
use Illuminate\Http\Response;
trait NotificationTrait
{
    function sendCustomerNotification(string $id, string $title = '', string $content = ''){
        $notification = new Notification(['receiver' => $id, 'receiver_type' => 1, 'title' => $title, 'content' => $content, 'seen' => 0]);
        return $notification->save();
    }
    function seenNotification($id){
        $seen = Notification::where('id', $id)->update(['seen' => 1]);
        return $seen;
    }
    function deleteNotification($id){
        $delete = Notification::where('id', $id)->delete();
        return $delete;
    }
}
