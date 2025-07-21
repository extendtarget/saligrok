<?php

namespace App;

use Exception;
use App\SmsOtp;
use Twilio\Rest\Client;
use Nwidart\Modules\Facades\Module;
use Modules\Wazone\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Log;

class Sms
{

    /**
     * @param $actionType
     * @param $phone
     * @param $otp
     * @param null $message
     * @return mixed
     */
    public function processSmsAction($actionType, $phone, $otp = null, $message = null, $smsForDelivery = null)
    {
        // START v4 WAZONE FOR FOODOMAA BY ARROCY Module 1 OF 2
        if ($actionType == 'OTP' && Module::find('Wazone')->isEnabled()) {
            $fallback = 'none';
            $filename = Module::getModulePath('Wazone') . 'wazone_settings.json';
            if (file_exists($filename)) {
                $data = json_decode(file_get_contents($filename), true);
                $fallback = $data['wazone_fallback'];
            } else {
                die('Unable to open file wazone_settings.json for read!');
            }
            $response = $this->wazone($phone);
            if (($response['success'] == true) || $fallback == 'none') {
                return $response;
                die();
            }
        }
        // END v4 WAZONE FOR FOODOMAA BY ARROCY Module 1 OF 2

        // Selects Default Gateway
        $gateway = config('setting.defaultSmsGateway');

        switch ($gateway) {

            case '1':
                try {
                    $response = $this->msg91($actionType, $phone, $otp, $message, $smsForDelivery);
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'type' => 'MSG91',
                    ];
                }

                break;

            case '2':
                try {
                    $response = $this->twilio($actionType, $phone, $otp, $message);
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'type' => 'TWILIO',
                    ];
                }

                break;
        }
       
        return $response;
    }

    // START v4 WAZONE FOR FOODOMAA BY ARROCY Module 2 OF 2
    private function wazone($phone)
    {
        $phoneWa = preg_replace('/\D/', '', $phone);

        $otp = rand(111111, 999999);
        if (str_contains($phone, '1234567890')) {
            $otp = '123456';
        }
        $param = array('otp' => $otp);
        $this->saveOtp($phone, $otp);

        // Send Api Request
        $msg = new MessageController();
        $response = $msg->send('OTP', $phoneWa, $param);
        
        return $response;
    }
    // END v4 WAZONE FOR FOODOMAA BY ARROCY Module 2 OF 2

    /**
     * @param $actionType
     * @param $phone
     * @param $otp
     * @param $message
     * @return mixed
     */
    private function msg91($actionType, $phone, $otp, $message, $smsForDelivery = null)
    {
        $authkey = config('setting.msg91AuthKey');
        $sender_id = config('setting.msg91SenderId');

        switch ($actionType) {

            case 'OTP':
                $otp = rand(111111, 999999);
                if (str_contains($phone, '1234567890')) {
                    $otp = '123456';
                }
                $message = config('setting.otpMessage') . ' ' . $otp;
                $this->saveOtp($phone, $otp);

                $msg_dlt_template_id = config('setting.msg91OtpDltTemplateId');
                if ($msg_dlt_template_id == null) {
                    $curlPost = "{ \"sender\": \"$sender_id\", \"route\": \"4\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$phone\" ] } ] }";
                } else {
                    $curlPost = "{ \"DLT_TE_ID\": \"$msg_dlt_template_id\", \"sender\": \"$sender_id\", \"route\": \"4\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$phone\" ] } ] }";
                }

                break;

            case 'VERIFY':
                $response = $this->verifyOtp($phone, $otp);
                return $response;
                break;

            case 'OD_NOTIFY':

                if ($smsForDelivery) {
                    $msg_dlt_template_id = config('setting.msg91NewOrderDeliveryDltTemplateId');
                } else {
                    $msg_dlt_template_id = config('setting.msg91NewOrderDltTemplateId');
                }

                if ($msg_dlt_template_id == null) {
                    $curlPost = "{ \"sender\": \"$sender_id\", \"route\": \"4\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$phone\" ] } ] }";
                } else {
                    $curlPost = "{ \"DLT_TE_ID\": \"$msg_dlt_template_id\", \"sender\": \"$sender_id\", \"route\": \"4\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$phone\" ] } ] }";
                }

                break;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.msg91.com/api/v2/sendsms',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $curlPost,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: $authkey",
                'content-type: application/json',
            ),
        ));

        $response = curl_exec($curl);
        
        $err = curl_error($curl);
        curl_close($curl);


        if ($err) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $actionType
     * @param $phone
     * @param $otp
     * @param $message
     * @return mixed
     */
    private function twilio($actionType, $phone, $otp, $message)
    {
        $sid = config('setting.twilioSid');
        $token = config('setting.twilioAccessToken');
        $from = config('setting.twilioFromPhone');

        switch ($actionType) {

            case 'OTP':
                $otp = rand(111111, 999999);
                if (str_contains($phone, '1234567890')) {
                    $otp = '123456';
                }
                $message = config('setting.otpMessage') . ' ' . $otp;
                $this->saveOtp($phone, $otp);

                break;

            case 'VERIFY':
                $response = $this->verifyOtp($phone, $otp);
                return $response;
                break;

            case 'OD_NOTIFY':
                // Do Nothing Just Send
                break;
        }
        // Send Api Request

        $twilio = new Client($sid, $token);

        try {
            $twilio->messages->create(
                // Where to send a text message (your cell phone?)
                $phone,
                array(
                    'From' => $from,
                    'body' => $message,
                )
            );
            return true;
        } catch (Exception $e) {
            \Log::error($e->getMessage());
            throw new Exception('Twilio Error');
        } catch (\Twilio\Rest\RestException $e) {
            \Log::error($e);
            throw new Exception('Twilio Error');
        } catch (\Twilio\Exceptions\RestException $e) {
            \Log::error($e);
            throw new Exception('Twilio Error');
        }
    }

    /**
     * @param $phone
     * @param $otp
     */
    private function saveOtp($phone, $otp)
    {

        $otpTable = SmsOtp::where('phone', $phone)->first();

        if ($otpTable) {
            //phone exists, just update the otp
            $otpTable->otp = $otp;
            $otpTable->save();
            # code...
        } else {
            //create new entry
            $otpTable = new SmsOtp();
            $otpTable->phone = $phone;
            $otpTable->otp = $otp;
            $otpTable->save();
        }
        // dd($phone);
    }

    /**
     * @param $phone
     * @param $otp
     */
    private function verifyOtp($phone, $otp)
    {
        // dd($otp);
        $otpTable = SmsOtp::where('phone', $phone)->first();

        if ($otpTable) {
            if ($otpTable->otp == $otp) {

                $response = [
                    'valid_otp' => true,
                ];
            } else {
                $response = [
                    'valid_otp' => false,
                ];
            }
        } else {
            $response = [
                'valid_otp' => false,
            ];
        }
        return $response;
    }
}
