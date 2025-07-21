<?php

namespace App\Console;

use Cache;
use App\Order;
use Carbon\Carbon;
use App\Restaurant;
use App\Jobs\SendDriverIncentive;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Schema;
use App\Jobs\Schedule\ScheduleRestaurants;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\Schedule\ScheduleDriversSalary;
use App\User;
use Illuminate\Support\Facades\Log;
use Artisan;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
       \App\Console\Commands\SendStoreOrderReminders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        if (Schema::hasTable('orders')) {
            $orders = Order::where('orderstatus_id', 8)->get();
            $now = Carbon::now()->timezone(config('app.timezone'));
            $awaitingPaymentThreshold = config('setting.awaitingPaymentThreshold');
            foreach ($orders as $order) {
                $now = Carbon::now()->timezone(config('app.timezone'));
                $orderPlacedTime = Carbon::parse($order->created_at);
                $diff = $now->diffInMinutes($orderPlacedTime);
                if ($diff > $awaitingPaymentThreshold) {
                    $order->orderstatus_id = 9;
                    $order->save();
                }
            }
        }

        // Schedule System Start
        $schedule->call(function () {
            ScheduleRestaurants::dispatch();
        })->everyMinute();

        $schedule->call(function () {
            ScheduleDriversSalary::dispatch();
        })->everyMinute();
        
        /* created by qusay */
        $schedule->call(function () {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Log::info('cache was cleared!');
        })->everyFiveMinutes();
        /* created by qusay */

        //Check Delivery Guys Performance for the day and add incentive in their wallet
        if (config('setting.enableDeliveryGuyIncentive') == "true") {
            $schedule->call(function () {
                SendDriverIncentive::dispatch();
            })->dailyAt('6:00');
        }

        if (!$this->osProcessIsRunning('queue:work')) {
            $schedule->command('queue:work')->everyMinute();
        }

        if (!$this->osProcessIsRunning('queue:listen')) {
            $schedule->command('queue:listen')->everyMinute();
        }

        // $schedule->command('schedule:restaurants')->everyMinute();
    }

    protected function osProcessIsRunning($needle)
    {
        // get process status. the "-ww"-option is important to get the full output!
        exec('ps aux -ww', $process_status);

        // search $needle in process status
        $result = array_filter($process_status, function ($var) use ($needle) {
            return strpos($var, $needle);
        });

        // if the result is not empty, the needle exists in running processes
        if (!empty($result)) {
            return true;
        }
        return false;
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    public function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
};
