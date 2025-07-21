<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sender_phone;
    public $sender_token;
    public $phone;
    public $message;
    public $buttons;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sender_phone, $sender_token, $phone, $message, $buttons)
    {
        $this->sender_phone = $sender_phone;
        $this->sender_token = $sender_token;
        $this->phone = $phone;
        $this->message = $message;
        $this->buttons = $buttons;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (Module::find('Wazone') && Module::find('Wazone')->isEnabled()) {
            $filename = Module::getModulePath('Wazone') . 'wazone_settings.json';
            if (file_exists($filename)) {
                $data = json_decode(file_get_contents($filename), true);
                $wazone_panel = $data['wazone_panel'];
                $wazone_server = $data['wazone_server'];
                $wazone_sender = $this->sender_phone != null ? $this->sender_phone : $data['wazone_sender'];
                $wazone_token = $this->sender_token != null ? $this->sender_token : $data['wazone_token'];
                $wazone_fallback = $data['wazone_fallback'];
                if ($wazone_fallback == 'none') {
                    $wazone_timeout = 1;
                } else {
                    $wazone_timeout = 5;
                }
            } else {
                Log::info('Unable to open file wazone_settings.json for read!');
                $this->delete();
            }

            $url = $wazone_server . '/api/send/whatsapp';
            if (!str_starts_with($this->phone, '+')) {
                $this->phone = "+" . $this->phone;
            }
            $data = [
                'recipient' => $this->phone,
                'message' => $this->message,
                'account' => $wazone_sender,
                'secret' => $wazone_token,
                'type' => "text",
                'priority' => 1
            ];
            $response = Curl::to($url)
                ->withContentType('application/x-www-form-urlencoded')
                ->withData($data)
                ->post();
        }
    }
}
