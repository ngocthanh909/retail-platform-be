<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendAutoNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-auto-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
    }
}
