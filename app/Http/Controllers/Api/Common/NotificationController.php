<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ApiResponseTrait, NotificationTrait;
    function getList(Request $request)
    {
        try {
            $notifications = Notification::where('receiver_type', $request->user()->tokenCan('customer') ? 1 :  0)
                ->where('receiver', $request->user()->id)
                ->orWhere('receiver', 0)
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
            $action = $this->seenNotification($request->id);
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }
    function deleteAction(Request $request)
    {
        try {
            $action = $this->deleteNotification($request->id);
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }
}
