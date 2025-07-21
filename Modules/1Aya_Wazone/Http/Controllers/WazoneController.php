<?php

namespace Modules\Wazone\Http\Controllers;

use Cache;
use App\User;
use App\Setting;
use App\Restaurant;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;
use Modules\Wazone\Helper\JsonIndent;

class WazoneController extends Controller
{
    public function saveRestaurantNotifiable($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant->is_notifiable) {
            $restaurant->is_notifiable = false;
        } else {
            $restaurant->is_notifiable = true;
        }
        $restaurant->save();
        return redirect()->back()->with(['success' => 'Store Notifiable Saved!']);
    }

    public function saveUserNotifiable($id)
    {
        $user = User::where('id', $id)->first();
        if ($user->is_notifiable) {
            $user->is_notifiable = false;
        } else {
            $user->is_notifiable = true;
        }
        $user->save();
        return redirect()->back()->with(['success' => 'User Notifiable Saved!']);
    }

    public function saveWazoneSettings(Request $request)
    {
        $setting = Setting::where('key', 'enOLnR')->first();
        $setting->value = $request->enOLnR;
        $setting->save();
        $settings = Cache::forget('settings');

        $filename = Module::getModulePath('Wazone') . 'wazone_settings.json';
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            $data['wazone_panel'] = rtrim($request->wazone_panel, "/");
            $data['wazone_server'] = rtrim($request->wazone_server, "/");
            $data['wazone_sender'] = $request->wazone_sender;
            $data['wazone_token'] = $request->wazone_token;
            $data['wazone_fallback'] = $request->wazone_fallback;
            $data['wazone_timeout'] = $request->wazone_timeout;
            $json = json_encode($data);
            $json = JsonIndent::beautify($json);
            file_put_contents($filename, $json);
        } else {
            return redirect()->back()->with(['message' => 'Please copy /Helper/wazone_settings.json and put it on root of wazone module!']);
        }

        $filename = Module::getModulePath('Wazone') . 'message_templates.json';
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            $data['msg_owner'] = $request->msg_owner;
            $data['msg_store'] = $request->msg_store;
            $data['msg_driver'] = $request->msg_driver;
            $data['msg_customer'] = $request->msg_customer;
            $data['msg_admin'] = $request->msg_admin;
            $data['msg_otp'] = $request->msg_otp;
            $data['msg_status2'] = $request->msg_status2;
            $data['msg_status3'] = $request->msg_status3;
            $data['msg_status4'] = $request->msg_status4;
            $data['msg_guest_status4'] = $request->msg_guest_status4; // Add the new field for guest
            $data['msg_admin_status4'] = $request->msg_admin_status4; // Add the new field for admin
            $data['msg_status5'] = $request->msg_status5;
            $data['msg_guest_status5'] = $request->msg_guest_status5;
            $data['msg_status6'] = $request->msg_status6;
            $data['msg_status7'] = $request->msg_status7;
            $data['msg_status8'] = $request->msg_status8;
            $data['msg_status9'] = $request->msg_status9;
            $data['msg_status10'] = $request->msg_status10;
            $data['msg_status11'] = $request->msg_status11;
            $data['msg_cancel'] = $request->msg_cancel;
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $json = JsonIndent::beautify($json);
            file_put_contents($filename, $json);
            return redirect()->back()->with(['success' => 'Server Settings & Message Templates Saved!']);
        } else {
            return redirect()->back()->with(['message' => 'Please copy /Helper/message_templates.json and put it on root of wazone module!']);
        }
    }

    public function wazone($phone, $message)
    {
        if (is_null($phone)) {
            return;
        }
        $filename = Module::getModulePath('Wazone') . 'wazone_settings.json';
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            $wazone_panel = $data['wazone_panel'];
            $wazone_server = $data['wazone_server'];
            $wazone_sender = $data['wazone_sender'];
            $wazone_token = $data['wazone_token'];
            $wazone_fallback = $data['wazone_fallback'];
            if ($wazone_fallback == 'none') {
                $wazone_timeout = 1;
            } else {
                $wazone_timeout = $data['wazone_timeout'];
            }
        } else {
            die('Unable to open file wazone_settings.json for read!');
        }
        if (!str_starts_with($phone, '+')) {
            $phone = "+" . $phone;
        }
        $url = $wazone_server . '/api/send/whatsapp';
        $data = ['recipient' => $phone, 'message' => $message, 'account' => $wazone_sender, 'secret' => $wazone_token, 'type' => "text", "priority" => 1];
        $resp = Curl::to($url)
            ->withContentType('application/x-www-form-urlencoded')
            ->withData($data)
            ->post();
        $resp = json_decode($resp, true);
        $status = $resp['status'];
        if (empty($resp)) {
            $resp['success'] = true;
            return ($resp);
        }
        $status = $status ?? $resp;
        if (empty($status)) {
            $response = ['success' => false, 'type' => 'WHATSAPP'];
        } elseif ($status == 200) {
            $response = ['success' => true, 'type' => 'WHATSAPP'];
        } elseif ($status != 200) {
            $response = ['success' => false, 'type' => 'WHATSAPP'];
        } else {
            $response = ['success' => true, 'type' => 'WHATSAPP'];
        }
        return $response;
    }

    public function url_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return curl_exec($ch);
    }

    public function validate()
    {
        return true;
    }

    public function activate($license_key)
    {
        return ($res['success'] = 'true');
    }

    private function read_file()
    {
        $filename = Module::getModulePath('Wazone') . 'Helper/helper.json';
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            return $data;
        } else {
            return false;
        }
    }

    private function write_file($license_key, $rest_api)
    {
        $filename = Module::getModulePath('Wazone') . 'Helper/helper.json';
        $data = ['order_id' => base64_encode($license_key), 'rest_api' => base64_encode($rest_api)];
        file_put_contents($filename, json_encode($data));
    }

    public function settings()
    {
        $users = User::orderBy('id', 'DESC')->with('roles')->take(9)->get();
        $userCount = User::count();
        $restaurants = Restaurant::where('is_accepted', '1')->with('users.roles')->ordered()->paginate(20);
        $restaurantCount = Restaurant::count();
        $file_templates = Module::getModulePath('Wazone') . 'message_templates.json';
        if (!file_exists($file_templates)) copy(Module::getModulePath('Wazone') . 'Helper/message_templates.json', Module::getModulePath('Wazone') . 'message_templates.json');
        $template = json_decode(file_get_contents(Module::getModulePath('Wazone') . 'message_templates.json'), true);
        $file_settings = Module::getModulePath('Wazone') . 'wazone_settings.json';
        if (!file_exists($file_settings)) copy(Module::getModulePath('Wazone') . 'Helper/wazone_settings.json', Module::getModulePath('Wazone') . 'wazone_settings.json');
        $setting = json_decode(file_get_contents(Module::getModulePath('Wazone') . 'wazone_settings.json'), true);
        return view('wazone::settings', compact('users', 'userCount', 'restaurants', 'restaurantCount', 'template', 'setting'));
    }

    public function enableAllUsers()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->is_notifiable = 1;
            $user->update();
        }
        return redirect()->back()->with(['success' => 'All users enabled successfully!']);
    }

    public function disableAllUsers()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->is_notifiable = 0;
            $user->update();
        }
        return redirect()->back()->with(['success' => 'All users disabled successfully!']);
    }

    public function enableAllStores()
    {
        $restaurants = Restaurant::all();
        foreach ($restaurants as $restaurant) {
            $restaurant->is_notifiable = 1;
            $restaurant->update();
        }
        return redirect()->back()->with(['success' => 'All stores enabled successfully!']);
    }

    public function disableAllStores()
    {
        $restaurants = Restaurant::all();
        foreach ($restaurants as $restaurant) {
            $restaurant->is_notifiable = 0;
            $restaurant->update();
        }
        return redirect()->back()->with(['success' => 'All stores disabled successfully!']);
    }
}