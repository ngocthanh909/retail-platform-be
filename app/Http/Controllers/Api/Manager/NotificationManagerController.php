<?php

namespace App\Http\Controllers\Api\Manager;

use App\Console\Commands\SendAutoNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationCampaignRequest;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Http\Traits\Helpers\NotificationTrait;
use App\Jobs\SendAutomaticNotification;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\NotificationCampaign;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationManagerController extends Controller
{
    use ApiResponseTrait, NotificationTrait;

    function create(NotificationCampaignRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $campaign = new NotificationCampaign([
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'image' => '',
                'receiver_id' => $data['receiver_id'] ?? 0,
                'delivery_date' => $data['delivery_date'] ?? '2024-01-01',
                'delivery_time' => $data['delivery_time'] ?? '00:00:00',
                'repeat' => $data['repeat'] ?? '',
                'next_repeat' => null
            ]);
            if ($data['repeat'] ?? '') {
                $weekMap = [
                    0 => 'SU',
                    1 => 'MO',
                    2 => 'TU',
                    3 => 'WE',
                    4 => 'TH',
                    5 => 'FR',
                    6 => 'SA',
                ];
                switch ($data['repeat']) {
                    case 'now':
                        $this->sendCustomerNotification($data['receiver_id'], $data['title'], $data['content']);
                        break;
                    case str_starts_with($data['repeat'], 'weekly'):
                        $dayOfWeek = explode(':', $data['repeat']);
                        $dayOfWeek = count($dayOfWeek) > 1 ? (int)$dayOfWeek[1] : null;
                        $sampleTime = Carbon::parse(now()->format('Y-m-d') . ' ' . $campaign->delivery_time)->addDays($dayOfWeek);
                        $campaign->next_repeat = $sampleTime->isFuture() ? $sampleTime : $sampleTime->addWeek();
                        break;
                    case 'everyday':
                        $time = Carbon::make($campaign->delivery_time);
                        $campaign->next_repeat = $time->isPast() ? $time->addDay() : $time;
                        break;
                    default:
                        $campaign->next_repeat = Carbon::make($data['delivery_date'] . " " . $data['delivery_time']);
                        break;
                }
            }
            $action = $campaign->save();
            if (!$action) {
                throw new Exception('Lưu thông báo thất bại');
            }
            DB::commit();
            return $this->success($campaign, 'Gửi thông báo thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->failure('Gửi thông báo thất bại', $e->getMessage());
        }
    }

    function edit(NotificationCampaignRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $campaign = NotificationCampaign::findOrFail($id);
            $campaign->fill([
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'image' => '',
                'receiver_id' => $data['receiver_id'] ?? 0,
                'delivery_date' => $data['delivery_date'] ?? '2024-01-01',
                'delivery_time' => $data['delivery_time'] ?? '00:00:00',
                'repeat' => $data['repeat'] ?? '',
                'next_repeat' => null,
                'status' => $data['status'] ? 1 : 0
            ]);
            if ($data['repeat'] ?? '') {
                $weekMap = [
                    0 => 'SU',
                    1 => 'MO',
                    2 => 'TU',
                    3 => 'WE',
                    4 => 'TH',
                    5 => 'FR',
                    6 => 'SA',
                ];
                switch ($data['repeat']) {
                    case 'now':
                        $this->sendCustomerNotification($data['receiver_id'], $data['title'], $data['content']);
                        break;
                    case str_starts_with($data['repeat'], 'weekly'):
                        $dayOfWeek = explode(':', $data['repeat']);
                        $dayOfWeek = count($dayOfWeek) > 1 ? (int)$dayOfWeek[1] : null;
                        $sampleTime = Carbon::parse(now()->format('Y-m-d') . ' ' . $campaign->delivery_time)->addDays($dayOfWeek);
                        $campaign->next_repeat = $sampleTime->isFuture() ? $sampleTime : $sampleTime->addWeek();
                        break;
                    case 'everyday':
                        $time = Carbon::make($campaign->delivery_time);
                        $campaign->next_repeat = $time->isPast() ? $time->addDay() : $time;
                        break;
                    default:
                        $campaign->next_repeat = Carbon::make($data['delivery_date'] . " " . $data['delivery_time']);
                        break;
                }
            }
            $action = $campaign->save();
            if (!$action) {
                throw new Exception('Sửa thông báo thất bại');
            }
            DB::commit();
            return $this->success($campaign, 'Sửa thông báo thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Sửa thông báo thất bại', 'Không tìm thấy thông báo');
            }
            return $this->failure('Sửa thông báo thất bại', $e->getMessage());
        }
    }



    function list(Request $request)
    {
        try {
            $notifications = NotificationCampaign::where('status', 1)->with('receiver')->orderBy('created_at', 'DESC')->paginate(config('store_list'));
            return $this->success($notifications, 'Gửi thông báo thành công!');
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

    function delete(Request $request, $id)
    {
        try {
            $deleteNotifi = NotificationCampaign::where('id', $id)->delete();
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
