<?php

namespace App\Jobs\Schedule;

use Carbon\Carbon;
use App\Restaurant;
use Illuminate\Bus\Queueable;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ScheduleRestaurants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    // public function handle()
    // {
       
    //     $day = strtolower(Carbon::now()->timezone(config('app.timezone'))->format('l'));
    //     Log::info("Running ScheduleRestaurants job for day: {$day}");


    //     $restaurants = Restaurant::with(['orders' => function ($q) {
    //         $q->whereIn('orders.orderstatus_id', ['10', '11']);
    //     }])->select('id', 'is_schedulable', 'schedule_data', 'is_active')->get();

    //     $minsSetByAdmin = (int) config('setting.minsBeforeScheduleOrderProcessed');
    //     $now = Carbon::now()->timezone(config('app.timezone'));

    //     $updates = [];

    //     foreach ($restaurants as $restaurant) {
    //         Log::info("Processing restaurant ID: {$restaurant->id}");
    //         $is_active = false;

    //         if ($restaurant->is_schedulable) {
    //             $schedule_data = $restaurant->schedule_data;

    //             if (empty($schedule_data)) {
    //                 Log::warning("No schedule data for restaurant ID: {$restaurant->id}");
    //                 $updates[$restaurant->id]['is_active'] = false;
    //             } else {
    //                 $schedule_data = json_decode($schedule_data, true);

    //                 if (!$schedule_data || !isset($schedule_data[$day]) || empty($schedule_data[$day])) {
    //                     Log::warning("No valid schedule data for restaurant ID: {$restaurant->id} on {$day}");
    //                     $updates[$restaurant->id]['is_active'] = false;
    //                 } else {
                      
    //                     foreach ($schedule_data[$day] as $time) {
    //                         try {
    //                             $open = Carbon::parse($time['open']);
    //                             $close = Carbon::parse($time['close']);

    //                             if ($open <= $now && $close >= $now) {
    //                                 $is_active = true;
    //                                 Log::info("Restaurant ID: {$restaurant->id} is open at {$now->toDateTimeString()}");
    //                                 break; 
    //                             }
    //                         } catch (\Exception $e) {
    //                             Log::error("Error parsing time for restaurant ID: {$restaurant->id}: {$e->getMessage()}");
    //                         }
    //                     }
    //                     $updates[$restaurant->id]['is_active'] = $is_active;
    //                 }
    //             }

               
    //             if ($restaurant->is_active != $is_active) {
    //                 Cache::forget('store-info-' . $restaurant->slug);
    //             }
    //         } else {
    //             Log::info("Restaurant ID: {$restaurant->id} is not schedulable");
    //             $updates[$restaurant->id]['is_active'] = false;
    //         }

            
    //         if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
    //             if (count($restaurant->orders) > 0) {
    //                 foreach ($restaurant->orders as $restaurantOrder) {
    //                     try {
    //                         $scheduleDate = json_decode($restaurantOrder->schedule_date);
    //                         $scheduleDate = $scheduleDate->date;
    //                         $scheduleSlot = json_decode($restaurantOrder->schedule_slot);
    //                         $scheduleSlotFrom = $scheduleSlot->open;

    //                         $scheduledDateTime = Carbon::createFromFormat('Y-m-d h:i A', $scheduleDate . ' ' . $scheduleSlotFrom);
    //                         $reduceTime = $scheduledDateTime->subMinutes($minsSetByAdmin);

    //                         if (Carbon::parse($reduceTime) <= $now) {
    //                             if ($restaurantOrder->orderstatus_id == 11) {
    //                                 $restaurantOrder->orderstatus_id = 2; // Preparing
    //                             } else {
    //                                 if ($restaurant->auto_acceptable) {
    //                                     $restaurantOrder->orderstatus_id = 2; // Preparing
    //                                 } else {
    //                                     $restaurantOrder->orderstatus_id = 1; // New order
    //                                 }
    //                             }

    //                             $restaurantOrder->save();
    //                             Log::info("Updated order ID: {$restaurantOrder->id} to status: {$restaurantOrder->orderstatus_id}");

    //                             sendNotificationAccordingToOrderRules($restaurantOrder);
    //                         }
    //                     } catch (\Exception $e) {
    //                         Log::error("Error processing order ID: {$restaurantOrder->id}: {$e->getMessage()}");
    //                     }
    //                 }
    //             }
    //         }
    //     }

        
    //     foreach ($updates as $restaurantId => $update) {
    //         Restaurant::where('id', $restaurantId)->update($update);
    //     }

    //     Cache::forget('stores-delivery-active');
    //     Cache::forget('stores-delivery-inactive');
    //     Cache::forget('stores-selfpickup-active');
    //     Cache::forget('stores-selfpickup-inactive');

    //     Log::info("Completed ScheduleRestaurants job");
    // }
    
      public function handle()
    {
       
        $day = strtolower(Carbon::now()->timezone(config('app.timezone'))->format('l'));
        Log::info("Running ScheduleRestaurants job for day: {$day}");


        $restaurants = Restaurant::with(['orders' => function ($q) {
            $q->whereIn('orders.orderstatus_id', ['10', '11']);
        }])->select('id', 'is_schedulable', 'schedule_data', 'is_active')->get();

        $minsSetByAdmin = (int) config('setting.minsBeforeScheduleOrderProcessed');
        $now = Carbon::now()->timezone(config('app.timezone'));

        $updates = [];

        foreach ($restaurants as $restaurant) {
            Log::info("Processing restaurant ID: {$restaurant->id}");
            $is_active = false;

            if ($restaurant->is_schedulable) {
                $schedule_data = $restaurant->schedule_data;

                if (empty($schedule_data)) {
                    Log::warning("No schedule data for restaurant ID: {$restaurant->id}");
                    $updates[$restaurant->id]['is_active'] = false;
                } else {
                    $schedule_data = json_decode($schedule_data, true);

                    if (!$schedule_data || !isset($schedule_data[$day]) || empty($schedule_data[$day])) {
                        Log::warning("No valid schedule data for restaurant ID: {$restaurant->id} on {$day}");
                        $updates[$restaurant->id]['is_active'] = false;
                    } else {
                        $is_active = false;
                        // تحويل schedule_data[$day] لمصفوفة إذا كانت كائن واحد
                        $times = is_array($schedule_data[$day]) && !isset($schedule_data[$day]['open']) ? $schedule_data[$day] : [$schedule_data[$day]];
                        
                        foreach ($times as $time) {
                            try {
                                $open = Carbon::parse($time['open']);
                                $close = Carbon::parse($time['close']);

                                if ($open <= $now && $close >= $now) {
                                    $is_active = true;
                                    Log::info("Restaurant ID: {$restaurant->id} is open at {$now->toDateTimeString()}");
                                    break; 
                                }
                            } catch (\Exception $e) {
                                Log::error("Error parsing time for restaurant ID: {$restaurant->id}: {$e->getMessage()}");
                            }
                        }
                        $updates[$restaurant->id]['is_active'] = $is_active;
                    }
                }

               
                if ($restaurant->is_active != $is_active) {
                    Cache::forget('store-info-' . $restaurant->slug);
                }
            } else {
                Log::info("Restaurant ID: {$restaurant->id} is not schedulable");
                $updates[$restaurant->id]['is_active'] = false;
            }

            
            if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
                if (count($restaurant->orders) > 0) {
                    foreach ($restaurant->orders as $restaurantOrder) {
                        try {
                            $scheduleDate = json_decode($restaurantOrder->schedule_date);
                            $scheduleDate = $scheduleDate->date;
                            $scheduleSlot = json_decode($restaurantOrder->schedule_slot);
                            $scheduleSlotFrom = $scheduleSlot->open;

                            $scheduledDateTime = Carbon::createFromFormat('Y-m-d h:i A', $scheduleDate . ' ' . $scheduleSlotFrom);
                            $reduceTime = $scheduledDateTime->subMinutes($minsSetByAdmin);

                            if (Carbon::parse($reduceTime) <= $now) {
                                if ($restaurantOrder->orderstatus_id == 11) {
                                    $restaurantOrder->orderstatus_id = 2; // Preparing
                                } else {
                                    if ($restaurant->auto_acceptable) {
                                        $restaurantOrder->orderstatus_id = 2; // Preparing
                                    } else {
                                        $restaurantOrder->orderstatus_id = 1; // New order
                                    }
                                }

                                $restaurantOrder->save();
                                Log::info("Updated order ID: {$restaurantOrder->id} to status: {$restaurantOrder->orderstatus_id}");

                                sendNotificationAccordingToOrderRules($restaurantOrder);
                            }
                        } catch (\Exception $e) {
                            Log::error("Error processing order ID: {$restaurantOrder->id}: {$e->getMessage()}");
                        }
                    }
                }
            }
        }

        
        foreach ($updates as $restaurantId => $update) {
            Restaurant::where('id', $restaurantId)->update($update);
        }

        Cache::forget('stores-delivery-active');
        Cache::forget('stores-delivery-inactive');
        Cache::forget('stores-selfpickup-active');
        Cache::forget('stores-selfpickup-inactive');

        Log::info("Completed ScheduleRestaurants job");
    }
}