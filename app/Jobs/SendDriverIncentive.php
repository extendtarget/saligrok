<?php

namespace App\Jobs;

use App\User;
use App\Order;
use Carbon\Carbon;
use App\AcceptDelivery;
use Illuminate\Bus\Queueable;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendDriverIncentive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startDate = Carbon::yesterday()->startOfDay()->toDateTimeString();
        $endDate = Carbon::yesterday()->endOfDay()->toDateTimeString();

        $acceptedOrders = AcceptDelivery::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereIn('orderstatus_id', ['5'])->whereBetween('created_at', [$startDate, $endDate]);
        })->where('is_complete', 1)
            ->select('id', 'user_id')
            ->get();;

        $deliveryGuyIds = [];

        foreach ($acceptedOrders as $order) {
            $deliveryGuyId = $order->user_id;
            if (isset($deliveryGuyIds[$deliveryGuyId])) {
                $deliveryGuyIds[$deliveryGuyId]++;
            } else {
                $deliveryGuyIds[$deliveryGuyId] = 1;
            }
        }
        $selectedDb = [];
        $deliveryUsers = User::whereIn('id', array_keys($deliveryGuyIds))->with('wallet')->select('id')->get();
        if (isset($deliveryUsers) && count($deliveryUsers) > 0) {
            $incentiveData = json_decode(config('setting.deliveryGuyIncentiveData'), true);
            $incentiveData = collect($incentiveData)->sortBy('incentive_amount')->values()->toArray();

            foreach ($deliveryUsers as $deliveryGuy) {
                $orderCount = $deliveryGuyIds[$deliveryGuy->id];
                $incentiveAmount = 0;
                $lastIncentiveAmount = 0;
                foreach ($incentiveData as $incentive) {
                    if ($orderCount >= $incentive['order_count']) {
                        $lastIncentiveAmount = $incentiveAmount;
                        $incentiveAmount = $incentive['incentive_amount'];
                    }
                }
                if ($incentiveAmount > $lastIncentiveAmount) {
                    $selectedDb[$deliveryGuy->id] = [
                        'amount' => $incentiveAmount,
                        'count' => $orderCount,
                    ];
                }
            }
            if (count($selectedDb) > 0) {
                $message = "Delivery Guy Incentive Posted in " . config('setting.storeName') . " " . config('setting.walletName') . " for \n\n";
                foreach ($selectedDb as $dbId => $data) {
                    if ($data['amount'] > 0) {
                        $amount = $data['amount'];
                        $deliveryBoy = User::where('id', $dbId)->with('wallet')->first();
                        if ($deliveryBoy) {
                            $message .= $deliveryBoy->name . " - " . $data['count'] . " - " . config('setting.currencyFormat') . $amount . "\n";
                            $deliveryBoy->deposit($amount * 100, ["description" => "Incentive Deposited in Wallet for delivering " . $data['count'] . " orders on " . Carbon::yesterday()->format('d-m-Y')]);
                        }
                    }
                }
                // SendWhatsappMessage::dispatch(null, null, "917400234173", $message, null);
            }
        }
    }
}
