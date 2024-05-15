<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Models\Notification;
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
            $notifications = DB::table('notifications AS n')
            ->select('n.id', 'nt.title', 'nt.content', 'nt.image', 'n.seen', 'n.receiver_id', 'n.user_type', 'n.delivery_time')
            ->join('notification_template AS nt',function($join) use ($user) {
                $join
                ->on('n.template_id','=','nt.id')
                ->where('n.receiver_id','=', $user->id)
                ->where('n.user_type','=', $user->tokenCan('customer') ? 1 : 0);
            })
            ->orderBy('n.delivery_time', 'DESC')
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
    function seenAllAction(Request $request)
    {
        try {
            $delete = Notification::where('receiver_id', $request->user()->id)->where('user_type', $request->tokenCan('employee') ? 0 : 1)->update(['seen' => 1]);
            if(!$delete){
                throw new \Exception('Thao tác thất bại');
            }
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Chuyển trạng thái thông báo thất bại', $e->getMessage());
        }
    }
    function deleteAction(Request $request)
    {
        try {
            $this->deleteNotification($request->id);
            return $this->success();
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure('Thao tác thất bại', $e->getMessage());
        }
    }
}
