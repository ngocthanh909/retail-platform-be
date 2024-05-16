<?php

namespace App\Jobs;

use App\Http\Traits\Helpers\NotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomaticNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotificationTrait;

    /**
     * Create a new job instance.
     */
    private $token = '';
    private $title = '';
    private $content = '';

    public function __construct($token, $title, $content)
    {
        $this->token = $token;
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendFirebaseNotification($this->token, $this->title, $this->content);
    }
}
