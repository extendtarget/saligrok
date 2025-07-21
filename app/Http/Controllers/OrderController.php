<?php

namespace App\Http\Controllers;

use Hashids;
use App\Item;
use App\User;
use App\Addon;
use App\Order;
use App\Coupon;
use App\Orderitem;
use App\PushNotify;
use App\Restaurant;
use App\OrderItemAddon;
use Illuminate\Http\Request;
use App\Helpers\TranslationHelper;
use Nwidart\Modules\Facades\Module;
use App\Jobs\AssignNearestDeliveryGuy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wazone\Http\Controllers\MessageController;

class OrderController extends Controller
{
    /**
     * Get cancellation reasons for restaurant.
     *
     * @return array
     */
    public function getCancelReasonsForRestaurant()
    {
        return [
            ['cancel_reason' => 'other'],
        ];
    }

    /**
     * Calculate delivery cost based on restaurant settings.
     *
     * @param Request $request
     * @return array
     */
    public function getDeliveryCost(Request $request)
    {
        $user = auth()->user();
        $restaurant_id = $request->store_id;
        
        $restaurant = Restaurant::where('id', $restaurant_id)->first();
        
        if ($request->delivery_type == 1) {
            $distance = (float) $request->dis;

            if ($restaurant->delivery_charge_type == 'DYNAMIC') {
                if ($distance > $restaurant->base_delivery_distance) {
                    $extraDistance = $distance - $restaurant->base_delivery_distance;
                    $extraCharge = ($extraDistance / $restaurant->extra_delivery_distance) * $restaurant->extra_delivery_charge;
                    $dynamicDeliveryCharge = $restaurant->base_delivery_charge + $extraCharge;

                    if (config('setting.enDelChrRnd') == 'true') {
                        $dynamicDeliveryCharge = ceil($dynamicDeliveryCharge);
                    }

                    $delivery_cost = round($dynamicDeliveryCharge);
                } else {
                    $delivery_cost = round($restaurant->base_delivery_charge);
                }
            } else {
                $delivery_cost = round($restaurant->delivery_charges);
            }
        } else {
            $delivery_cost = 0;
        }
        
        return ['delivery_cost' => $delivery_cost];
    }


    /**
     * Place a new order and register a notification in store_notifications.
     *
     * @param Request $request
     * @param TranslationHelper $translationHelper
     * @return \Illuminate\Http\JsonResponse
     */
    
    public function placeOrder(Request $request, TranslationHelper $translationHelper)
    {
        $user = auth()->user();
        
    if ($user->delivery_guy_detail_id !== null) {
        Log::error('Order placement attempt by delivery guy', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'delivery_guy_detail_id' => $user->delivery_guy_detail_id
        ]);
        return response()->json(['success' => false, 'message' => 'Delivery personnel cannot place orders'], 403);
    }
    
        // Initialize request parameters
        $request->partial_wallet = isset($request->partial_wallet) ? $request->partial_wallet : false;
        $request->payment_token = isset($request->payment_token) ? $request->payment_token : null;
        $request->pending_payment = isset($request->pending_payment) ? $request->pending_payment : false;
    
        if ($user) {
            $keys = ['orderPaymentWalletComment', 'orderPartialPaymentWalletComment'];
            $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);
    
            $newOrder = new Order();
    
            // Handle manual order flag
            $is_manual_order = isset($request->is_manual_order) && $request->is_manual_order;
            $newOrder->is_manual_order = $is_manual_order;
            Log::info('Order creation started', [
                'user_id' => $user->id,
                'is_manual_order' => $is_manual_order,
                'request_data' => $request->all()
            ]);
    
            // Generate unique order ID
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $newId = $lastOrder ? $lastOrder->id + 1 : 1;
            $uniqueId = Hashids::encode($newId);
            $unique_order_id = 'OD' . '-' . date('m-d') . '-' . strtoupper(str_random(4)) . '-' . strtoupper($uniqueId);
            $newOrder->unique_order_id = $unique_order_id;
    
            // Set restaurant and user details
            $restaurant_id = $request['order'][0]['restaurant_id'];
            $restaurant = Restaurant::where('id', $restaurant_id)->first();
            if (!$restaurant) {
                Log::error('Restaurant not found', ['restaurant_id' => $restaurant_id]);
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }
            $newOrder->user_id = $user->id;
            $newOrder->zone_id = $restaurant->zone_id ? $restaurant->zone_id : null;
    
            // Set order status
            if ($request['pending_payment'] || in_array($request['method'], ['MERCADOPAGO', 'PAYTM', 'RAZORPAY'])) {
                $newOrder->orderstatus_id = '8';
    
                if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
                    if (isset($request->schedule_date) && $request->schedule_date != null && isset($request->schedule_slot) && $request->schedule_slot != null) {
                        $newOrder->is_scheduled = true;
                        $newOrder->schedule_date = json_encode($request->schedule_date);
                        $newOrder->schedule_slot = json_encode($request->schedule_slot);
                    }
                }
            } elseif ($restaurant->auto_acceptable) {
                $newOrder->orderstatus_id = '2';
    
                if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
                    if (isset($request->schedule_date) && $request->schedule_date != null && isset($request->schedule_slot) && $request->schedule_slot != null) {
                        $newOrder->orderstatus_id = '10';
                        $newOrder->is_scheduled = true;
                        $newOrder->schedule_date = json_encode($request->schedule_date);
                        $newOrder->schedule_slot = json_encode($request->schedule_slot);
                    }
                }
    
                if ($request->delivery_type == 1) {
                    sendSmsToDelivery($restaurant_id);
                }
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('2', $newOrder->user_id, $newOrder->unique_order_id);
                }
            } else {
                $newOrder->orderstatus_id = '1';
    
                if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
                    if (isset($request->schedule_date) && $request->schedule_date != null && isset($request->schedule_slot) && $request->schedule_slot != null) {
                        $newOrder->orderstatus_id = '10';
                        $newOrder->is_scheduled = true;
                        $newOrder->schedule_date = json_encode($request->schedule_date);
                        $newOrder->schedule_slot = json_encode($request->schedule_slot);
                    }
                }
            }
    
            // Set location and address
            $newOrder->location = json_encode($request['location']);
            $full_address = ($request->delivery_type == 2) ? "NA" : $request['user']['data']['default_address']['house'] . ', ' . $request['user']['data']['default_address']['address'];
            $newOrder->address = $full_address;
    
            // Set restaurant charges and transaction ID
            $newOrder->restaurant_charge = $restaurant->restaurant_charges;
            $newOrder->transaction_id = $request->payment_token;
    
            // Calculate order total
            $orderTotal = 0;
            foreach ($request['order'] as $oI) {
                $originalItem = Item::where('id', $oI['id'])->first();
                if (!$originalItem) {
                    Log::warning("Item not found for ID: {$oI['id']}");
                    continue;
                }
                $orderTotal += ($originalItem->price * ($oI['quantity'] ?? 1));
    
                if (isset($oI['selectedaddons'])) {
                    foreach ($oI['selectedaddons'] as $selectedaddon) {
                        $addon = Addon::where('id', $selectedaddon['addon_id'])->first();
                        if ($addon && isset($oI['quantity'])) {
                            $orderTotal += $addon->price * $oI['quantity'];
                        } else {
                            Log::warning("Addon not found or quantity missing for addon ID: {$selectedaddon['addon_id']}");
                        }
                    }
                }
            }
            $newOrder->sub_total = $orderTotal;
            Log::info('Order subtotal calculated', [
                'order_id' => $newId,
                'sub_total' => $orderTotal,
                'restaurant_id' => $restaurant_id
            ]);
    
            // Set delivery type and calculate charges
            // Modified to ensure manual orders are treated as Delivery (1) by default
            $newOrder->delivery_type = $is_manual_order ? 1 : ($request->delivery_type ?? 1);
            Log::info('Delivery type set', [
                'order_id' => $newId,
                'delivery_type' => $newOrder->delivery_type,
                'is_manual_order' => $is_manual_order
            ]);
    $distance = 0;
    $calculatedDeliveryCharge = 0;
            if ($newOrder->delivery_type == 1) {
                if (config('setting.enGDMA') == 'true') {
                    $distance = (float) ($request->dis ?? 0);
                } else {
                    $distance = isset($request['user']['data']['default_address']['latitude']) && isset($request['user']['data']['default_address']['longitude']) 
                        ? getDistance(
                            $request['user']['data']['default_address']['latitude'],
                            $request['user']['data']['default_address']['longitude'],
                            $restaurant->latitude,
                            $restaurant->longitude
                        ) : 0;
                }
    
                if ($restaurant->delivery_charge_type == 'DYNAMIC') {
                    if ($distance > $restaurant->base_delivery_distance) {
                        $extraDistance = $distance - $restaurant->base_delivery_distance;
                        $extraCharge = ($extraDistance / $restaurant->extra_delivery_distance) * $restaurant->extra_delivery_charge;
                        $dynamicDeliveryCharge = $restaurant->base_delivery_charge + $extraCharge;
                        $calculatedDeliveryCharge = config('setting.enDelChrRnd') == 'true' ? ceil($dynamicDeliveryCharge) : $dynamicDeliveryCharge;
                    } else {
                        $calculatedDeliveryCharge = $restaurant->base_delivery_charge;
                    }
                } else {
                    $calculatedDeliveryCharge = $restaurant->delivery_charges;
                }
    
                $newOrder->delivery_charge = $calculatedDeliveryCharge;
                $newOrder->actual_delivery_charge = $calculatedDeliveryCharge;
                $newOrder->is_free_delivery = false;
                $newOrder->distance = $distance;
    
                Log::info('Delivery charges calculated', [
                    'order_id' => $newId,
                    'delivery_type' => $newOrder->delivery_type,
                    'distance' => $distance,
                    'delivery_charge' => $newOrder->delivery_charge,
                    'actual_delivery_charge' => $newOrder->actual_delivery_charge,
                    'is_manual_order' => $is_manual_order
                ]);
            } else {
                $newOrder->delivery_charge = 0;
                $newOrder->actual_delivery_charge = 0;
                $newOrder->is_free_delivery = false;
                Log::info('Self-pickup set, delivery charge zeroed', ['order_id' => $newId, 'delivery_type' => $newOrder->delivery_type]);
            }
    
            // Handle manual orders
            if ($is_manual_order) {
                // Ensure manual orders stay in "Pending" or "Accepted" state based on restaurant settings
                if ($restaurant->auto_acceptable) {
                    $newOrder->orderstatus_id = '2'; // Accepted
                    Log::info('Manual order set to Accepted due to auto_acceptable', [
                        'order_id' => $newId,
                        'unique_order_id' => $newOrder->unique_order_id
                    ]);
                    // Send notifications if needed
                   if ($newOrder->delivery_type == 1 && $restaurant->free_delivery_subtotal > 0 && $distance <= $restaurant->free_delivery_distance && $restaurant->free_delivery_cost > 0 && $restaurant->free_delivery_comm > 0) {
                        sendSmsToDelivery($restaurant_id);
                    }
                    if (config('setting.enablePushNotificationOrders') == 'true') {
                        $notify = new PushNotify();
                        $notify->sendPushNotification('2', $newOrder->user_id, $newOrder->unique_order_id);
                    }
                } else {
                    $newOrder->orderstatus_id = '1'; 
                    Log::info('Manual order set to Pending', [
                        'order_id' => $newId,
                        'unique_order_id' => $newOrder->unique_order_id
                    ]);
                    // Register notification for store
                    DB::table('store_notifications')->insert([
                        'restaurant_id' => $restaurant->id,
                        'user_id' => null,
                        'title' => 'طلب جديد: ' . $newOrder->unique_order_id,
                        'message' => "⚠️ تذكير: لديك طلب جديد على تطبيق موتوبوكس لم يتم معالجته بعد!\r\nرمز الطلب: {$newOrder->unique_order_id}\r\nيرجى قبول أو رفض الطلب فوراً من تطبيق المطاعم.\r\nملاحظات الطلب: " . ($request->order_comment ?? ''),
                        'image' => null,
                        'status' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $newOrder->save();
            }
    
            // Apply coupon if provided
            if ($request->coupon) {
                $coupon = Coupon::where('code', strtoupper($request['coupon']['code']))->first();
                if ($coupon) {
                    $newOrder->coupon_name = $request['coupon']['code'];
    
                    if ($coupon->is_used_for_delivery) {
                        $original_delivery_charge = $newOrder->delivery_charge;
                        $discount_percentage = $coupon->delivery_discount_percentage ?? 100;
                        $discount_amount = $original_delivery_charge * ($discount_percentage / 100);
                        $newOrder->coupon_amount = $discount_amount;
                        $newOrder->delivery_charge = $original_delivery_charge - $discount_amount;
                        $newOrder->delivery_charge = $newOrder->delivery_charge > 0 ? $newOrder->delivery_charge : 0;
                        $newOrder->actual_delivery_charge = $original_delivery_charge;
                        Log::info('Delivery coupon applied', [
                            'order_id' => $newId,
                            'coupon_code' => $coupon->code,
                            'coupon_amount' => $discount_amount,
                            'delivery_charge' => $newOrder->delivery_charge,
                            'actual_delivery_charge' => $newOrder->actual_delivery_charge
                        ]);
                    } else {
                        if ($coupon->discount_type == 'PERCENTAGE') {
                            $percentage_discount = (($coupon->discount / 100) * $orderTotal);
                            if ($coupon->max_discount && $percentage_discount >= $coupon->max_discount) {
                                $percentage_discount = $coupon->max_discount;
                            }
                            $newOrder->coupon_amount = $percentage_discount;
                            $orderTotal -= $percentage_discount;
                        } elseif ($coupon->discount_type == 'AMOUNT') {
                            $newOrder->coupon_amount = $coupon->discount;
                            $orderTotal -= $coupon->discount;
                        } elseif ($coupon->discount_type == 'FREE') {
                            $percentage_discount = (($coupon->discount / 100) * $newOrder->delivery_charge);
                            if ($coupon->max_discount && $percentage_discount >= $coupon->max_discount) {
                                $percentage_discount = $coupon->max_discount;
                            }
                            $newOrder->coupon_amount = $percentage_discount;
                            $newOrder->delivery_charge = $newOrder->delivery_charge - $percentage_discount;
                            $newOrder->delivery_charge = $newOrder->delivery_charge > 0 ? $newOrder->delivery_charge : 0;
                            $newOrder->actual_delivery_charge = $newOrder->delivery_charge;
                            Log::info('Free delivery coupon applied', [
                                'order_id' => $newId,
                                'coupon_code' => $coupon->code,
                                'coupon_amount' => $percentage_discount,
                                'delivery_charge' => $newOrder->delivery_charge
                            ]);
                        }
                    }
    
                    $coupon->count += 1;
                    $coupon->save();
                    Log::info('Coupon applied', [
                        'order_id' => $newId,
                        'coupon_code' => $coupon->code,
                        'coupon_amount' => $newOrder->coupon_amount,
                        'delivery_charge' => $newOrder->delivery_charge
                    ]);
                } else {
                    $newOrder->coupon_name = null;
                    $newOrder->coupon_amount = 0;
                }
            } else {
                $newOrder->coupon_name = null;
                $newOrder->coupon_amount = 0;
            }
    
            // Apply delivery surcharge if applicable
            if ($newOrder->actual_delivery_charge > 0 && $restaurant->zone && $restaurant->zone->delivery_surcharge_active && !$is_manual_order) {
                if ($restaurant->zone->delivery_surcharge_rate > 0) {
                    $deliverySurcharge = $restaurant->zone->delivery_surcharge_rate;
                    if ($restaurant->zone->delivery_surcharge_type === "fixed") {
                        $newOrder->delivery_charge += $deliverySurcharge;
                    } elseif ($restaurant->zone->delivery_surcharge_type === "percentage") {
                        $surchargeAmount = $newOrder->delivery_charge * ($deliverySurcharge / 100);
                        $newOrder->delivery_charge += $surchargeAmount;
                    }
                    $newOrder->delivery_charge = ceil($newOrder->delivery_charge);
                    $newOrder->actual_delivery_charge = $newOrder->delivery_charge;
                    Log::info('Delivery surcharge applied', [
                        'order_id' => $newId,
                        'surcharge_amount' => $deliverySurcharge,
                        'delivery_charge' => $newOrder->delivery_charge
                    ]);
                }
            }
    
            // Apply free delivery for non-manual orders if conditions met
            if ( $restaurant->free_delivery_subtotal > 0 && $distance <= $restaurant->free_delivery_distance && $restaurant->free_delivery_cost > 0 && $restaurant->free_delivery_comm > 0) {
                if ($newOrder->sub_total >= $restaurant->free_delivery_subtotal) {
                    $newOrder->delivery_charge = 0;
                    $newOrder->is_free_delivery = true;
                    $newOrder->actual_delivery_charge = $calculatedDeliveryCharge; // Preserve calculated charge for accounting
                    Log::info('Free delivery applied due to restaurant policy', [
                        'order_id' => $newId,
                        'sub_total' => $newOrder->sub_total,
                        'free_delivery_subtotal' => $restaurant->free_delivery_subtotal,
                        'free_delivery_cost' => $restaurant->free_delivery_cost,
                        'free_delivery_comm' => $restaurant->free_delivery_comm,
                        'distance' => $distance,
                        'free_delivery_distance' => $restaurant->free_delivery_distance,
                        'actual_delivery_charge' => $newOrder->actual_delivery_charge,
                        'coupon_amount' => $newOrder->coupon_amount,
                        'coupon_name' => $newOrder->coupon_name
                    ]);
                }
            }
    
            // Calculate final order total
            $orderTotal += $newOrder->delivery_charge + $restaurant->restaurant_charges;
    
            if (config('setting.taxApplicable') == 'true') {
                $newOrder->tax = config('setting.taxPercentage');
                $taxAmount = (float) ((config('setting.taxPercentage') / 100) * $orderTotal);
            } else {
                $taxAmount = 0;
            }
    
            $newOrder->tax_amount = $taxAmount;
            $orderTotal += $taxAmount;
    
            if (isset($request['tipAmount']) && !empty($request['tipAmount'])) {
                $orderTotal += $request['tipAmount'];
            }
    
            // Set payable amount for COD
            if ($request['method'] == 'COD') {
                $newOrder->payable = $request->partial_wallet ? $orderTotal - $user->balanceFloat : $orderTotal;
            }
    
            // Set final order details
            $newOrder->actual_total = $orderTotal;
            $newOrder->actual_payment_mode = $request->method;
            /*addActualTotalCode*/ 
            $newOrder->actual_total = $orderTotal;
            $newOrder->actual_payment_mode = $request['method'];
            /*endaddActualTotalCode*/
    $newOrder->total = $orderTotal;
            $newOrder->order_comment = $request['order_comment'];
            $newOrder->payment_mode = $request['method'];
            $newOrder->restaurant_id = $restaurant_id;
            $newOrder->tip_amount = $request['tipAmount'];
            $newOrder->delivery_type = $newOrder->delivery_type; // Ensure the modified value is retained
            $newOrder->cash_change_amount = $request['cash_change_amount'] ?? null;
            $newOrder->delivery_pin = substr(str_shuffle('123456789'), 0, 5);
    
            // Handle admin approval if required
            if ($restaurant->is_order_need_approval_by_admin) {
                $newOrder->is_accepted_by_admin = false;
            }
    
            // Save the order initially
            $newOrder->save();
            Log::info('Order saved', [
                'order_id' => $newOrder->id,
                'unique_order_id' => $newOrder->unique_order_id,
                'sub_total' => $newOrder->sub_total,
                'delivery_charge' => $newOrder->delivery_charge,
                'is_free_delivery' => $newOrder->is_free_delivery,
                'is_manual_order' => $newOrder->is_manual_order,
                'coupon_amount' => $newOrder->coupon_amount
            ]);
    
            // Register notification in store_notifications if order is not auto-accepted
            if (!$restaurant->auto_acceptable && in_array($newOrder->orderstatus_id, ['1', '10'])) {
                DB::table('store_notifications')->insert([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => null,
                    'title' => 'طلب جديد: ' . $newOrder->unique_order_id,
                    'message' => "⚠️ تذكير: لديك طلب جديد على تطبيق موتوبوكس لم يتم معالجته بعد!\r\nرمز الطلب: {$newOrder->unique_order_id}\r\nيرجى قبول أو رفض الطلب فوراً من تطبيق المطاعم.\r\nملاحظات الطلب: " . ($request->order_comment ?? ''),
                    'image' => null,
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info("Notification created for order {$newOrder->unique_order_id} without immediate WhatsApp message");
            }
    
            // Handle payment methods
            if (in_array($request['method'], ['PAYPAL', 'PAYSTACK', 'RAZORPAY', 'STRIPE', 'PAYMONGO', 'MERCADOPAGO', 'PAYTM', 'FLUTTERWAVE', 'KHALTI'])) {
                if ($request->partial_wallet) {
                    $userWalletBalance = $user->balanceFloat;
                    $newOrder->wallet_amount = $userWalletBalance;
                    $newOrder->payment_mode = 'PARTIAL';
                    $newOrder->save();
                    $user->withdraw($userWalletBalance * 100, ['description' => $translationData->orderPartialPaymentWalletComment . $newOrder->unique_order_id]);
                    Log::info('Partial wallet payment processed', [
                        'order_id' => $newOrder->id,
                        'user_id' => $user->id,
                        'wallet_amount' => $userWalletBalance,
                        'payment_mode' => $newOrder->payment_mode,
                        'payable' => $newOrder->payable
                    ]);
                }
    
                foreach ($request['order'] as $orderItem) {
                    $item = new Orderitem();
                    $item->order_id = $newOrder->id;
                    $item->item_id = $orderItem['id'];
                    $item->name = $orderItem['name'];
                    $item->quantity = $orderItem['quantity'];
                    $item->price = $orderItem['price'];
                    $item->save();
    
                    if (isset($orderItem['selectedaddons'])) {
                        foreach ($orderItem['selectedaddons'] as $selectedaddon) {
                            $addon = new OrderItemAddon();
                            $addon->orderitem_id = $item->id;
                            $addon->addon_category_name = $selectedaddon['addon_category_name'];
                            $addon->addon_name = $selectedaddon['addon_name'];
                            $addon->addon_price = $selectedaddon['price'];
                            $addon->save();
                        }
                    }
                }
    
                if (!$restaurant->auto_acceptable && $newOrder->orderstatus_id == '1' && config('setting.smsRestaurantNotify') == 'true') {
                    sendSmsToStoreOwner($restaurant_id, $orderTotal);
                }
    
                if ($newOrder->delivery_type == 1 && $restaurant->auto_acceptable && $newOrder->orderstatus_id == 2) {
                    if (config('setting.autoAssignNearestDeliveryGuy') == "true") {
                        if (config('setting.autoAssignDeliveryGuyDelay') != null || config('setting.autoAssignDeliveryGuyDelay') > 0) {
                            sendPushNotificationToDelivery($restaurant_id, $newOrder);
                            sendSmsToDelivery($restaurant_id);
                        }
                        AssignNearestDeliveryGuy::dispatch($newOrder)->delay(config('setting.autoAssignDeliveryGuyDelay') * 60);
                    } else {
                        sendPushNotificationToDelivery($restaurant_id, $newOrder);
                        sendSmsToDelivery($restaurant_id);
                    }
                }
    
                if (in_array($newOrder->orderstatus_id, ['1', '10'])) {
                    sendPushNotificationToStoreOwner($restaurant_id, $unique_order_id);
                }
    
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('6', $newOrder->user_id);
                }
    
                activity()
                    ->performedOn($newOrder)
                    ->causedBy($user)
                    ->withProperties(['type' => 'Order_Placed'])->log('Order placed');
    
                if ($newOrder->orderstatus_id == '2') {
                    activity()
                        ->performedOn($newOrder)
                        ->causedBy(User::find(1))
                        ->withProperties(['type' => 'Order_Accepted_Auto'])->log('Order auto accepted');
                }
    
                return response()->json(['success' => true, 'data' => $newOrder]);
            } else {
                if ($request['method'] == 'COD' && $request->partial_wallet) {
                    $userWalletBalance = $user->balanceFloat;
                    $newOrder->wallet_amount = $userWalletBalance;
                    $newOrder->payment_mode = 'PARTIAL';
                    $newOrder->save();
                    $user->withdraw($userWalletBalance * 100, ['description' => $translationData->orderPartialPaymentWalletComment . $newOrder->unique_order_id]);
                    Log::info('Partial wallet payment processed for COD', [
                        'order_id' => $newOrder->id,
                        'user_id' => $user->id,
                        'wallet_amount' => $userWalletBalance,
                        'payment_mode' => $newOrder->payment_mode,
                        'payable' => $newOrder->payable
                    ]);
                }
    
                if ($request['method'] == 'WALLET') {
                    $userWalletBalance = $user->balanceFloat;
                    $newOrder->wallet_amount = $orderTotal;
                    $newOrder->payment_mode = 'WALLET';
                    $newOrder->save();
                    $user->withdraw($orderTotal * 100, ['description' => $translationData->orderPaymentWalletComment . $newOrder->unique_order_id]);
                    Log::info('Full wallet payment processed', [
                        'order_id' => $newOrder->id,
                        'user_id' => $user->id,
                        'wallet_amount' => $orderTotal,
                        'payment_mode' => $newOrder->payment_mode,
                        'payable' => $newOrder->payable
                    ]);
                }
    
                foreach ($request['order'] as $orderItem) {
                    $item = new Orderitem();
                    $item->order_id = $newOrder->id;
                    $item->item_id = $orderItem['id'];
                    $item->name = $orderItem['name'];
                    $item->quantity = $orderItem['quantity'];
                    $item->price = $orderItem['price'];
                    $item->save();
    
                    if (isset($orderItem['selectedaddons'])) {
                        foreach ($orderItem['selectedaddons'] as $selectedaddon) {
                            $addon = new OrderItemAddon();
                            $addon->orderitem_id = $item->id;
                            $addon->addon_category_name = $selectedaddon['addon_category_name'];
                            $addon->addon_name = $selectedaddon['addon_name'];
                            $addon->addon_price = $selectedaddon['price'];
                            $addon->save();
                        }
                    }
                }
    
                if (!$restaurant->auto_acceptable && $newOrder->orderstatus_id == '1' && config('setting.smsRestaurantNotify') == 'true') {
                    sendSmsToStoreOwner($restaurant_id, $orderTotal);
                }
    
                if ($restaurant->auto_acceptable && config('setting.enablePushNotification') && config('setting.enablePushNotificationOrders') == 'true') {
                    sendPushNotificationToDelivery($restaurant_id, $newOrder);
                }
    
                if (in_array($newOrder->orderstatus_id, ['1', '10'])) {
                    sendPushNotificationToStoreOwner($restaurant_id, $unique_order_id);
                }
    
                activity()
                    ->performedOn($newOrder)
                    ->causedBy($user)
                    ->withProperties(['type' => 'Order_Placed'])->log('Order placed');
    
                if ($newOrder->orderstatus_id == '2') {
                    activity()
                        ->performedOn($newOrder)
                        ->causedBy(User::find(1))
                        ->withProperties(['type' => 'Order_Accepted_Auto'])->log('Order auto accepted');
                }
    
                if ($is_manual_order) {
                    $request->merge([
                        'order_id' => $newOrder->id,
                        'store_id' => $restaurant_id
                    ]);
                    (new \App\Http\Controllers\StoreOwner\StoreOwnerAppController())->acceptOrder($request);
                    Log::info('Manual order processed and accepted', [
                        'order_id' => $newOrder->id,
                        'unique_order_id' => $newOrder->unique_order_id
                    ]);
                }
    
                return response()->json(['success' => true, 'data' => $newOrder]);
            }
        }
    
        Log::error('Unauthorized order creation attempt', ['user_id' => $user ? $user->id : null]);
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
       
    
    
      /**
         * Update the order status and mark related notification as processed.
         *created By Aya 
         * @param Request $request
         * @param string $orderId
         * @return \Illuminate\Http\JsonResponse
         */
           public function updateOrderStatus(Request $request, $orderId)
            {
                $user = auth()->user();
                $order = Order::where('unique_order_id', $orderId)->firstOrFail();
        
                // Check if the user is authorized (e.g., store owner or admin)
                $restaurant = Restaurant::find($order->restaurant_id);
                if (!$user->hasRole('store_owner') || $restaurant->user_id != $user->id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
        
                $request->validate([
                    'status' => 'required|in:accepted,rejected'
                ]);
        
                try {
                    if ($request->status === 'accepted') {
                        $order->orderstatus_id = '2'; // Accepted
                    } elseif ($request->status === 'rejected') {
                        $order->orderstatus_id = '6'; // Canceled
                        $order->cancel_reason = $request->input('cancel_reason', 'Rejected by store');
                    }
        
                    $order->save();
        
                    // Update notification status to processed
                    $title = 'طلب جديد: ' . $order->unique_order_id;
                    Log::info("Attempting to update notification for order {$order->unique_order_id}, title: '{$title}', status: 0");
        
                    $updated = DB::table('store_notifications')
                        ->where('title', $title)
                        ->where('status', 0) // Changed to integer 0
                        ->update(['status' => 2, 'updated_at' => now()]); // Changed to integer 2
        
                    Log::info("Updated $updated notifications for order {$order->unique_order_id} to status 2");
        
                    // If no notifications were updated, check for similar titles
                    if ($updated === 0) {
                        $similarNotifications = DB::table('store_notifications')
                            ->where('title', 'LIKE', "%{$order->unique_order_id}%")
                            ->get();
                        Log::warning("No notifications updated. Found " . count($similarNotifications) . " similar notifications: " . json_encode($similarNotifications));
                    }
        
                    // Log activity
                        activity()
                            ->performedOn($order)
                            ->causedBy($user)
                            ->withProperties(['type' => 'Order_' . ucfirst($request->status)])
                            ->log('Order ' . $request->status);
        
                    // Send push notification to user
                    if (config('setting.enablePushNotificationOrders') == 'true') {
                        $notify = new PushNotify();
                        $notify->sendPushNotification($request->status === 'accepted' ? '2' : '6', $order->user_id, $order->unique_order_id);
                    }
        
                    return response()->json(['success' => true, 'message' => 'Order status updated']);
                } catch (\Exception $e) {
                    Log::error('Error updating order status: ' . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Failed to update order status'], 500);
                }
            }

    /**
     * @param Request $request
     */
    public function getOrders(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $orders = Order::where('user_id', $user->id)->with('orderitems', 'orderitems.order_item_addons', 'restaurant', 'rating')->orderBy('id', 'DESC')->paginate(10);

            foreach ($orders as $order) {
                $ratable = false;
                if ($order->orderstatus_id == 5 && !$order->rating) {
                    $ratable = true;
                }
                $order->is_ratable = $ratable;
                $order->makeHidden(['reviews']);
            }

            return response()->json($orders);
        }
        return response()->json(['success' => false], 401);
    }

    /**
     * @param Request $request
     */
    public function getOrderItems(Request $request)
    {
        $user = auth()->user();
        if ($user) {

            $items = Orderitem::where('order_id', $request->order_id)->get();
            return response()->json($items);
        }
        return response()->json(['success' => false], 401);
    }

    /**
     * Cancel an order and process refund if applicable.
     *
     * @param Request $request
     * @param TranslationHelper $translationHelper
     * @return \Illuminate\Http\JsonResponse
     
    public function cancelOrder(Request $request, TranslationHelper $translationHelper)
    {
        $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
        $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

        $order = Order::where('id', $request->order_id)->first();
        $user = auth()->user();

        // Check if user is cancelling their own order
        if ($order->user_id == $user->id && ($order->orderstatus_id == 1 || $order->orderstatus_id == 10)) {
            $refund = false;

            // Handle refund for COD
            if ($order->payment_mode == 'COD' && $order->wallet_amount != null) {
                $user->deposit($order->wallet_amount * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
                $refund = true;
            } elseif ($order->payment_mode != 'COD') {
                $user->deposit(($order->total) * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                $refund = true;
            }

            // Cancel order
            $order->orderstatus_id = 6; // Canceled
            $order->cancel_reason = $request->cancel_reason;
            $order->save();

            // Send notification to user
            if (config('setting.enablePushNotificationOrders') == 'true') {
                $notify = new PushNotify();
                $notify->sendPushNotification('6', $order->user_id);
            }

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Canceled'])
                ->log('Order canceled');

            return response()->json([
                'success' => true,
                'refund' => $refund,
            ]);
        }

        return response()->json([
            'success' => false,
            'refund' => false,
        ]);
    }*/
    
    public function cancelOrder(Request $request, TranslationHelper $translationHelper)
    {
        $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
        $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);
    
        $order = Order::where('id', $request->order_id)->first();
        $user = auth()->user();
    
        // Check if user is cancelling their own order
        if ($order && $order->user_id == $user->id && ($order->orderstatus_id == 1 || $order->orderstatus_id == 10)) {
            $refund = false;
    
            // Handle refund for COD or PARTIAL payment
            if (($order->payment_mode == 'COD' || $order->payment_mode == 'PARTIAL') && $order->wallet_amount != null && $order->wallet_amount > 0) {
                $user->deposit($order->wallet_amount * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
                $refund = true;
                Log::info('Partial refund processed', [
                    'order_id' => $order->id,
                    'unique_order_id' => $order->unique_order_id,
                    'wallet_amount' => $order->wallet_amount,
                    'payment_mode' => $order->payment_mode
                ]);
            } elseif ($order->payment_mode == 'WALLET' || $order->payment_mode == 'ONLINE') {
                $user->deposit(($order->total) * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                $refund = true;
                Log::info('Full refund processed', [
                    'order_id' => $order->id,
                    'unique_order_id' => $order->unique_order_id,
                    'total' => $order->total,
                    'payment_mode' => $order->payment_mode
                ]);
            }
    
            // Cancel order
            $order->orderstatus_id = 6; // Canceled
            $order->cancel_reason = $request->cancel_reason ? 'تم إلغاء الطلب من قبل العميل بسبب: ' . $request->cancel_reason : 'إلغاء من قبل العميل';
            $order->save();
    
            // Send notification to user and restaurant
            if (config('setting.enablePushNotificationOrders') == 'true') {
                $notify = new PushNotify();
                $notify->sendPushNotification('6', $order->user_id, $order->unique_order_id);
                // Optional: Notify restaurant
                $notify->sendPushNotificationToRestaurant('6', $order->restaurant_id, $order->unique_order_id);
            }
    
            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties([
                    'type' => 'Order_Canceled_User',
                    'refund_amount' => $refund ? ($order->payment_mode == 'PARTIAL' || $order->payment_mode == 'COD' ? $order->wallet_amount : $order->total) : 0
                ])
                ->log('تم إلغاء الطلب من قبل العميل');
    
            // Log debug information
            \Log::info('User Cancel Order Debug', [
                'order_id' => $order->id,
                'unique_order_id' => $order->unique_order_id,
                'payment_mode' => $order->payment_mode,
                'wallet_amount' => $order->wallet_amount,
                'total' => $order->total,
                'refund_amount' => $refund ? ($order->payment_mode == 'PARTIAL' || $order->payment_mode == 'COD' ? $order->wallet_amount : $order->total) : 0
            ]);
    
            return response()->json([
                'success' => true,
                'refund' => $refund,
                'message' => 'تم إلغاء الطلب بنجاح'
            ], 200);
        }
    
        return response()->json([
            'success' => false,
            'refund' => false,
            'message' => 'الطلب غير موجود أو لا يمكن إلغاؤه'
        ], 401);
    }
}