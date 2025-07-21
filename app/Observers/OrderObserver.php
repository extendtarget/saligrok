<?php

namespace App\Observers;

use App\User;
use App\Order;
use App\Restaurant;
use App\Jobs\SendOrderCallToSO;
use App\Jobs\SendRatingPushNotification;
use App\Jobs\SendWazoneMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;

class OrderObserver
{
    /**
     * Handle the order "created" event.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function created(Order $order)
    {
        if ($order->payment_mode == 'COD') {
            try {
                if (Module::find('Wazone')->isEnabled()) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                    if ($restaurant) {
                        $admin = User::where('id', 1)->first();
                        $customer = User::where('id', $order->user_id)->first();
                        $msg = new \Modules\Wazone\Http\Controllers\MessageController();
            
                        $param = [
                            'unique_order_id'   => $order->unique_order_id,
                            'payment_mode'      => $order->payment_mode,
                            'total'             => $order->total,
                            'sub_total'         => $order->sub_total,
                            'restaurant_charge' => $order->restaurant_charge,
                            'delivery_charge'   => $order->delivery_charge,
                            'tax_amount'        => $order->tax_amount,
                            'tip_amount'        => $order->tip_amount,
                            'coupon_amount'     => $order->coupon_amount,
                            'payable'           => $order->payable,
                            'wallet_amount'     => $order->wallet_amount,
                            'order_comment'     => $order->order_comment,
                            'actual_delivery_charge' => $order->actual_delivery_charge,
                            'restaurant_id'     => $restaurant->id,
                            'restaurant_name'   => $restaurant->name,
                            'restaurant_phone'  => $restaurant->phone,
                            'customer_id'       => $customer->id,
                            'customer_name'     => $customer->name,
                            'customer_phone'    => $customer->phone,
                            'customer_email'    => $customer->email
                        ];
            
                        if ($order->orderstatus_id == '1' || ($order->orderstatus_id == '2' && $restaurant->auto_acceptable == '1')) {
                            $storeOwnerIds = [];
                            $pivotUsers = $restaurant->users()->wherePivot('restaurant_id', $order->restaurant_id)->get();
                            foreach ($pivotUsers as $target) {
                                if ($target->hasRole('Store Owner') && $target->is_notifiable == true) {
                                    array_push($storeOwnerIds, $target->id);
                                }
                            }
                            SendWazoneMessage::dispatch(null, $storeOwnerIds, $msg, $param);
                            if ($restaurant->is_notifiable == true) {
                                $sendStore = $msg->send('STORE', $restaurant->phone, $param);
                            }
                            if ($customer->is_notifiable == true) {
                                $sendCustomer = $msg->send('CUSTOMER', $customer->phone, $param);
                            }
                            if ($admin->is_notifiable == true) {
                                $sendAdmin = $msg->send('ADMIN', $admin->phone, $param);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::info("Error in Wazone Observer - " . $e->getMessage());
            } catch (\Throwable $th) {
                Log::info("Error in Wazone Observer - " . $th->getMessage());
            }
        }
    }

    /**
     * Handle the order "updated" event.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function updated(Order $order)
    {
        try {
            if (Module::find('Wazone')->isEnabled()) {
                $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                if ($restaurant) {
                    $admin = User::where('id', 1)->first();
                    $customer = User::where('id', $order->user_id)->first();
                    $msg = new \Modules\Wazone\Http\Controllers\MessageController();
                    $param = array(
                        'unique_order_id'   => $order->unique_order_id,
                        'payment_mode'      => $order->payment_mode,
                        'total'             => $order->total,
                        'sub_total'         => $order->sub_total,
                        'restaurant_charge' => $order->restaurant_charge,
                        'delivery_charge'   => $order->delivery_charge,
                        'tax_amount'        => $order->tax_amount,
                        'tip_amount'        => $order->tip_amount,
                        'coupon_amount'     => $order->coupon_amount,
                        'payable'           => $order->payable,
                        'wallet_amount'     => $order->wallet_amount,
                        'order_comment'     => $order->order_comment,
                        'actual_delivery_charge' => $order->actual_delivery_charge,
                        'restaurant_id'     => $restaurant->id,
                        'restaurant_name'   => $restaurant->name,
                        'restaurant_phone'  => $restaurant->phone,
                        'customer_id'       => $customer->id,
                        'customer_name'     => $customer->name,
                        'customer_phone'    => $customer->phone,
                        'cancel_reason'     => $order->cancel_reason,
                        'customer_email'    => $customer->email
                    );

                    if ($order->delivery_type == '1' && $order->orderstatus_id == '2') {
                        $pivotUsers = $restaurant->users()->wherePivot('restaurant_id', $order->restaurant_id)->get();
                        $deliveryGuyIds = [];
                        foreach ($pivotUsers as $target) {
                            if ($target->hasRole('Delivery Guy') && $target->is_notifiable == true && $target->is_active) {
                                array_push($deliveryGuyIds, $target->id);
                            }
                        }
                        SendWazoneMessage::dispatch($deliveryGuyIds, null, $msg, $param);
                    }

                    if ($order->orderstatus_id == '4' && $order->getOriginal('orderstatus_id') != '4') {
                        if ($admin->is_notifiable == true) {
                            $sendAdmin = $msg->send('ADMIN_STATUS4', $admin->phone, $param);
                        }
                    }

                    if ($order->orderstatus_id == '6') {
                        $pivotUsers = $restaurant->users()->wherePivot('restaurant_id', $order->restaurant_id)->get();
                        $storeOwnerIds = [];
                        foreach ($pivotUsers as $target) {
                            if ($target->hasRole('Store Owner') && $target->is_notifiable == true) {
                                array_push($storeOwnerIds, $target->id);
                                $sendOwner = $msg->send('CANCEL', $target->phone, $param);
                            }
                        }
                        if ($restaurant->is_notifiable == true) {
                            $sendStore = $msg->send('CANCEL', $restaurant->phone, $param);
                        }
                        if ($admin->is_notifiable == true) {
                            $sendAdmin = $msg->send('CANCEL', $admin->phone, $param);
                        }
                    }

                    if ($customer->is_notifiable) {
                        $statusid = $order->orderstatus_id;
                        $originalStatus = $order->getOriginal('orderstatus_id');
                        if ($statusid != '1' && $originalStatus != $statusid) {
                            $sendCustomer = $msg->send('STATUS' . $statusid, $customer->phone, $param);
                        }
                    }

                    if ($order->orderstatus_id == 5 && $order->getOriginal('orderstatus_id') != 5) {
                        SendRatingPushNotification::dispatch()->delay(Carbon::now()->addMinutes(config('setting.ratingNotificationDelay')));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info("Error in Wazone Observer - " . $e->getMessage());
        } catch (\Throwable $th) {
            Log::info("Error in Wazone Observer - " . $th->getMessage());
        }
    }

    /**
     * Handle the order "deleted" event.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function deleted(Order $order)
    {
        //
    }

    /**
     * Handle the order "restored" event.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function restored(Order $order)
    {
        //
    }

    /**
     * Handle the order "force deleted" event.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function forceDeleted(Order $order)
    {
        //
    }
}