<?php

namespace App\Http\Controllers;

use App\User;
use Artisan;
use Hash;
use Illuminate\Http\Request;

class SchedulerController extends Controller
{
    /**
     * @param $password
     */
    public function run($password)
    {
        $admin = User::where('id', '1')->first();
        $hashedPassword = $admin->password;

        if (Hash::check($password, $hashedPassword)) {
            Artisan::call('schedule:run');
        } else {
            echo 'Access Denied.';
        }
    }
    
    
    // public function run($password = null)
    // {
    //     try {
    //         $admin = User::where('id', 1)->first();

    //         if (!$admin || !Hash::check($password, $admin->password)) {
    //             Log::warning("Scheduler access denied for password: {$password}");
    //             return response()->json(['status' => 'error', 'message' => 'Access Denied'], 403);
    //         }

    //         // تشغيل الـ ScheduleRestaurants job
    //         ScheduleRestaurants::dispatch();
    //         Log::info("ScheduleRestaurants job dispatched successfully");

    //         return response()->json(['status' => 'success', 'message' => 'Scheduler ran successfully'], 200);
    //     } catch (\Exception $e) {
    //         Log::error("Error running scheduler: {$e->getMessage()}");
    //         return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
    //     }
    // }
}
