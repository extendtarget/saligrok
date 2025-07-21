<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWazoneMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deliveryGuyIds;
    public $storeOwnerIds;
    public $msg;
    public $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($deliveryGuyIds, $storeOwnerIds, $msg, $param)
    {
        $this->deliveryGuyIds = $deliveryGuyIds;
        $this->storeOwnerIds = $storeOwnerIds;
        $this->msg = $msg;
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (isset($this->deliveryGuyIds)) {
            $deliveryGuys = User::whereIn('id', $this->deliveryGuyIds)->select('id', 'phone')->get();

            foreach ($deliveryGuys as $deliveryGuy) {
                $sendDriver = $this->msg->send('DRIVER', $deliveryGuy->phone, $this->param);
            }
        }

        if (isset($this->storeOwnerIds)) {
            $storeOwnerIds = User::whereIn('id', $this->storeOwnerIds)->select('id', 'phone')->get();

            foreach ($storeOwnerIds as $storeOwner) {
                $sendOwner = $this->msg->send('OWNER', $storeOwner->phone, $this->param);
            }
        }
    }
}
