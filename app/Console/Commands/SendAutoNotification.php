<?php

namespace App\Console\Commands;

use App\Http\Traits\Helpers\NotificationTrait;
use App\Models\NotificationCampaign;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAutoNotification extends Command
{
    use NotificationTrait;
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
    protected $description = 'Send campaign message each minute';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaigns = NotificationCampaign::where('status', 1)->whereRaw("DATE_FORMAT(next_repeat, '%Y-%m-%d %H:%i') = '" . now()->format('Y-m-d H:i') . "'")->get();
        $this->info(count($campaigns) . " campaigns start now");
        foreach ($campaigns as $campaign) {
            try {
                $this->sendCustomerNotification($campaign->receiver_id, $campaign->title, $campaign->content);
                switch ($campaign->repeat) {
                    case str_starts_with($campaign->repeat, 'weekly'):
                        $dayOfWeek = explode(':', $campaign->repeat);
                        $dayOfWeek = count($dayOfWeek) > 1 ? (int)$dayOfWeek[1] : null;
                        $sampleTime = Carbon::parse(now()->format('Y-m-d') . ' ' . $campaign->delivery_time)->addDays($dayOfWeek);
                        $campaign->next_repeat = $sampleTime->isFuture() ? $sampleTime : $sampleTime->addWeek();
                        $campaign->save();
                        break;
                    case 'everyday':
                        $time = Carbon::make($campaign->delivery_time);
                        $campaign->next_repeat = $time->isPast() ? $time->addDay() : $time;
                        $campaign->save();
                        break;
                    default:
                        $campaign->next_repeat = null;
                        $campaign->save();
                        break;
                }
            } catch (\Throwable $e) {
                Log::error($e);
                $this->error($e->getMessage());
            }
        }
    }
}
