<?php

namespace App\Jobs\Schedule;

use Carbon\Carbon;
use App\DeliveryGuyDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ScheduleDriversSalary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // Driver Schedule
        $drivers = DeliveryGuyDetail::where('fixed_salary_schedulable', true)->whereNotNull('fixed_salary_schedule_data')
            ->select('id', 'status', 'fixed_salary', 'fixed_salary_schedulable', 'fixed_salary_schedule_data')
            ->get();
        $updates = [];
        if ($drivers->count() > 0) {

            $day = "salary_" . strtolower(Carbon::now()->timezone(config('app.timezone'))->format('l'));

            foreach ($drivers as $driver) {
                $schedule_data = $driver->fixed_salary_schedule_data;
                $schedule_data = json_decode($schedule_data);

                if ($schedule_data) {
                    if (isset($schedule_data->$day)) {
                        if (count($schedule_data->$day) > 0) {
                            foreach ($schedule_data->$day as $time) {
                                if (Carbon::parse($time->open) < Carbon::now()->timezone(config('app.timezone')) && Carbon::parse($time->close) > Carbon::now()->timezone(config('app.timezone'))) {
                                    $updates[$driver->id]['fixed_salary'] = number_format($time->salary, 2);
                                }
                            }
                        }
                    }
                }
            }
        }

        $flattenedUpdates = [];
        foreach ($updates as $update) {
            $flattenedUpdates = array_merge($flattenedUpdates, $update);
        }

        if (!empty($flattenedUpdates)) {
            DeliveryGuyDetail::whereIn('id', array_keys($updates))->update($flattenedUpdates);
        }
    }
}
