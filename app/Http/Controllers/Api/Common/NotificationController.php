<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    use ApiResponseTrait, NotificationTrait;
    function getList(Request $request)
    {
        try {
            $user = $request->user();
            $now = now()->format('Y-m-d H:i:s');
            $notifications = DB::table('notification_delivery AS nd')
                ->join('notifications AS n', function ($join) use ($user) {
                    $join
                        ->on('nd.notification_id', '=', 'n.id');
                })
                ->where('nd.receiver_id', $user->id)
                ->where('nd.user_type', '=', $user->tokenCan('customer') ? 1 : 0)
                ->where('nd.delivery_time', '<=', now())
                ->orderBy('nd.delivery_time', 'DESC')
                ->select('nd.id', 'n.title', 'n.content', 'n.image', 'nd.seen', 'nd.receiver_id', 'nd.user_type', 'nd.delivery_time')
                ->paginate(config('paginate.notification'));
            return $this->success($notifications);
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Lấy thông báo thất bại', $e->getMessage());
        }
    }
    function seenAction(Request $request)
    {
        try {
            $user = $request->user();
            $type = $user->tokenCan('customer') ? 1 : 0;
            $action = NotificationDelivery::where('user_type', $type)->where('receiver_id', $user->id)->where('id', $request->id)->where('seen', 0)->update(['seen' => 1]);
            return $action ? $this->success() : $this->failure();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }
    function seenAllAction(Request $request)
    {
        try {
            $user = $request->user();
            $type = $user->tokenCan('customer') ? 1 : 0;
            $action = NotificationDelivery::where('user_type', $type)->where('receiver_id', $user->id)->where('seen', 0)->update(['seen' => 1]);
            return $action ? $this->success() : $this->failure();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Chuyển trạng thái thông báo thất bại', $e->getMessage());
        }
    }
    function deleteAction(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $request->user()->tokenCan('customer') ? 1 : 0;
            $action = NotificationDelivery::where('receiver_id')->where('user_type', $userType)->where('id', $request->id)->delete();
            return $action ? $this->success() : $this->failure();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }

    function deleteAllAction(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user->tokenCan('customer') ? 1 : 0;
            $action = NotificationDelivery::where('receiver_id', $user->id)->where('user_type', $userType)->delete();
            return $action ? $this->success() : $this->failure();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }
}
