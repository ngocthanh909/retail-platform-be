<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Jobs\SendAutomaticNotification;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationManagerController extends Controller
{
    use ApiResponseTrait, NotificationTrait;

    function create(Request $request){
        try {
            $sendNotifi = $this->sendNotification(
                $request->receiver_id,
                1,
                $request->delivery_time,
                true,
                $request->title ?? '',
                $request->content ?? '',
                ''
            );
            if(!$sendNotifi){
                return $this->failure('Gửi thông báo thành không thành công!');
            }
            return $this->success([], 'Gửi thông báo thành công!');
        } catch(\Throwable $e){
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function list(Request $request){
        try {
            $notifications = NotificationTemplate::where('is_manual', 1)->paginate(config('store_list'));
            return $this->success($notifications, 'Gửi thông báo thành công!');
        } catch(\Throwable $e){
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function edit(Request $request){
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
            if(!$sendNotifi){
                return $this->failure('Gửi thông báo thành không thành công!');
            }
            return $this->success([], 'Gửi thông báo thành công!');
        } catch(\Throwable $e){
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function seen(Request $request){
        try {
            $seenNotifi = $this->seen($request->id);
            if(!$seenNotifi){
                return $this->failure('Không thể đánh dấu đã xem!');
            }
            return $this->success();
        } catch(\Throwable $e){
            Log::error($e);
            return $this->failure('Không thể đánh dấu đã xem!', $e->getMessage());
        }
    }

    function delete(Request $request){
        try {
            $deleteNotifi = $this->deleteNotificationStrategy($request->id);
            if(!$deleteNotifi){
                return $this->failure('Xóa thông báo không thành công!');
            }
            return $this->success();
        } catch(\Throwable $e){
            Log::error($e);
            return $this->failure('Xóa thông báo không thành công!', $e->getMessage());
        }
    }
}
