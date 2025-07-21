<?php

namespace App\Jobs;

use App\Alert;
use App\PushToken;
use Illuminate\Bus\Queueable;
use Ixudra\Curl\Facades\Curl;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendRatingPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $titles = [
            "👀 How Was It? Rate Your Order! 🌟",
            "🌟 Rate Your Order! Help Us Improve 😊",
            "👀 We saw you ordered with us! How was it? 😃"
        ];
        $secretKey = 'key=' . config('setting.firebaseSecret');
        $title = $titles[array_rand($titles)];
        $message = null;
        if ($this->order->delivery_type == 1) {
            $message = "We'd love to hear your thoughts on your delivery experience! Rate us now!";
        } elseif ($this->order->delivery_type == 2) {
            $message = "Thank you for choosing us! We'd love to hear your thoughts on your recent pickup order. Rate us now!";
        }

        $url = request()->getSchemeAndHttpHost() . "/rate-order" . "/" . $this->order->id;
        $whatsappMsg = $message;
        $whatsappMsg .= "\n\nClick on the link below to rate us! 👇🏻👇🏻👇🏻👇🏻\n\n$url";
        SendWhatsappMessage::dispatch(null, null, $this->order->user->phone, $message, null);

        $badge = base_path('/assets/img/favicons/favicon-96x96.png');
        $alertData = array(
            'title' => $title,
            'icon' => base_path('/assets/img/favicons/favicon-512x512.png'),
            'click_action' => $url,
            'unique_order_id' => null,
            'custom_notification' => true,
            'custom_image' => null,
            'url_open_type' => "INTERNAL",
            'notification_url' => $url,
        );

        /* Save to Alerts table */
        $alert = new Alert();
        $data = $alertData;
        $data['body'] = $message;
        $alert->data = json_encode($data);
        $alert->user_id = $this->order->user->id;
        $alert->is_read = 0;
        $alert->save();
        /*  END Save to Alerts Table */

        $pushTokens = PushToken::where('is_active', '1')
            ->where('user_id', $this->order->user->id)
            ->get(['token', 'device_type']);

        $androidTokens = [];
        $iosTokens = [];

        foreach ($pushTokens as $pushToken) {
            if ($pushToken->device_type == 'ios') {
                $iosTokens[] = $pushToken->token;
            } else {
                $androidTokens[] = $pushToken->token;
            }
        }

        // Send notifications to iOS devices
        if (count($iosTokens)) {
            $iosChunks = array_chunk($iosTokens, 1000);
            foreach ($iosChunks as $chunk) {
                $iosNotificationData = $alertData;
                $iosNotificationData['body'] = $message;
                $iosData = [
                    'registration_ids' => $chunk,
                    'notification' => $iosNotificationData, // Use $alertData instead of $data
                ];

                SendFirebaseNotification::dispatch($iosData);
            }
        }

        // Send notifications to Android devices
        if (count($androidTokens)) {
            $androidChunks = array_chunk($androidTokens, 1000);
            foreach ($androidChunks as $chunk) {
                $androidMessageData = $alertData;
                $androidMessageData['badge'] = $badge;
                $androidMessageData['message'] = $message;
                $androidData = [
                    'registration_ids' => $chunk,
                    'data' => $androidMessageData, // Use $alertData instead of $data
                ];

                SendFirebaseNotification::dispatch($androidData);
            }
        }
    }
}
