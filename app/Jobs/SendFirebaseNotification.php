<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $secretKey = 'key=' . config('setting.firebaseSecret');

        $response = Curl::to('https://fcm.googleapis.com/fcm/send')
            ->withHeader('Content-Type: application/json')
            ->withHeader("Authorization: $secretKey")
            ->withData(json_encode($this->data))
            ->post();
    }
}
