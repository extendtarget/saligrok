<?php

namespace App\Console\Commands;

use App\Order;
use App\Restaurant;
use App\User;
use Illuminate\Console\Command;
use Modules\Wazone\Http\Controllers\MessageController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendStoreOrderReminders extends Command
{
    protected $signature = 'stores:send-order-reminders';
    protected $description = 'Send reminder messages to restaurant owners for unprocessed orders';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Fetch pending notifications that are due for a reminder
        $notifications = DB::table('store_notifications')
            ->where('status', 0)
            ->where(function ($query) {
                $query->whereNull('updated_at')
                      ->orWhereRaw('TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= 1');
            })
            ->get();

        Log::info("Found " . count($notifications) . " pending notifications for reminders");

        $messageController = new MessageController();

        foreach ($notifications as $notification) {
            $orderId = explode(': ', $notification->title)[1] ?? null;
            $order = Order::where('unique_order_id', $orderId)->first();
            $restaurant = Restaurant::find($notification->restaurant_id);

            if ($restaurant && $order && in_array($order->orderstatus_id, ['1', '10'])) {
                // Get all store owners from restaurant_user table
                $restaurantUsers = DB::table('restaurant_user')
                    ->where('restaurant_id', $restaurant->id)
                    ->get();

                $sentToOwner = false;

                if ($restaurantUsers->isNotEmpty()) {
                    foreach ($restaurantUsers as $restaurantUser) {
                        $user = User::find($restaurantUser->user_id);

                        if ($user && $user->hasRole('Store Owner') && ($user->is_notifiable || is_null($user->is_notifiable))) {
                            $phone = $user->phone;

                            if (preg_match('/^\+\d{10,15}$/', $phone)) {
                                $response = $messageController->send('STORE', $phone, [
                                    'unique_order_id' => $order->unique_order_id,
                                    'restaurant_name' => $restaurant->name,
                                    'order_comment' => $order->order_comment ?? '',
                                ]);

                                if (empty($response['success'])) {
                                    Log::error("Failed to send reminder for order {$order->unique_order_id} to user {$user->name}: " . json_encode($response, JSON_UNESCAPED_UNICODE));
                                } else {
                                    Log::info("Reminder sent for order {$order->unique_order_id} to user {$user->name}");
                                    $sentToOwner = true;
                                }
                            } else {
                                Log::warning("Invalid phone number format for user {$user->name}: {$phone}");
                            }
                        } else {
                            Log::warning("User ID {$restaurantUser->user_id} is not a store owner or not notifiable for restaurant {$restaurant->name}");
                        }
                    }
                } else {
                    Log::warning("No store owners found in restaurant_user for restaurant {$restaurant->name}");
                }

                // Fallback to restaurant phone if no valid owners were found
                if (!$sentToOwner && $restaurant->is_notifiable && preg_match('/^\+\d{10,15}$/', $restaurant->phone)) {
                    $response = $messageController->send('STORE', $restaurant->phone, [
                        'unique_order_id' => $order->unique_order_id,
                        'restaurant_name' => $restaurant->name,
                        'order_comment' => $order->order_comment ?? '',
                    ]);
                    if (empty($response['success'])) {
                        Log::error("Failed to send fallback reminder for order {$order->unique_order_id} to restaurant {$restaurant->name}: " . json_encode($response, JSON_UNESCAPED_UNICODE));
                    } else {
                        Log::info("Fallback reminder sent for order {$order->unique_order_id} to restaurant {$restaurant->name}");
                    }
                } elseif (!$sentToOwner) {
                    Log::warning("No valid store owners or restaurant phone found for {$restaurant->name}");
                }

                DB::table('store_notifications')
                    ->where('id', $notification->id)
                    ->update(['updated_at' => now()]);

                $this->info("Reminder processed for order {$order->unique_order_id} for restaurant {$restaurant->name}");
            } else {
                Log::warning("Notification skipped for order {$orderId}: restaurant or order invalid");
            }
        }
    }
}