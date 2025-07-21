<?php

namespace App\Http\Controllers;

use Auth;
use Mail;
use JWTAuth;
use App\User;
use App\Order;
use App\Rating;
use Carbon\Carbon;
use App\PushNotify;
use JWTAuthException;
use App\AcceptDelivery;
use App\RestaurantEarning;
use App\DeliveryCollection;
use Illuminate\Http\Request;
use App\DeliveryLiveLocation;
use App\DeliveryGuyActiveRecord;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use App\Coupon;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    /**
     * @param $email
     * @param $password
     * @return mixed
     */
    private function getToken($email, $password)
    {
        $token = null;
        try {
            if (!$token = JWTAuth::attempt(['email' => $email, 'password' => $password])) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'Password or email is invalid..',
                    'token' => $token,
                ]);
            }
        } catch (JWTAuthException $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Token creation failed',
            ]);
        }
        return $token;
    }

    // aya
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if ($user && \Hash::check($request->password, $user->password)) {
            if ($user->hasRole('Delivery Guy')) {
                $token = $this->getToken($request->email, $request->password);
                if (is_array($token) && isset($token['response']) && $token['response'] === 'error') {
                    return response()->json($token, 401);
                }
    
                // Save the token to the user
                $user->auth_token = $token;
                $user->save();
    
                // Fetch ongoing and completed deliveries count
                $onGoingDeliveriesCount = AcceptDelivery::whereHas('order', function ($query) {
                    $query->whereIn('orderstatus_id', ['3', '4']);
                })->where('user_id', $user->id)->where('is_complete', 0)->count();
    
                $completedDeliveriesCount = AcceptDelivery::whereHas('order', function ($query) {
                    $query->whereIn('orderstatus_id', ['5']);
                })->where('user_id', $user->id)->where('is_complete', 1)->count();
    
                $status = $user->delivery_guy_detail->status;
    
                $response = [
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'auth_token' => $user->auth_token,
                        'name' => $user->name,
                        'email' => $user->email,
                        'wallet_balance' => number_format($user->balanceFloat, 2, '.', ''), // Use actual wallet balance
                        'onGoingCount' => $onGoingDeliveriesCount,
                        'completedCount' => $completedDeliveriesCount,
                        'status' => $status,
                    ],
                ];
            } else {
                $response = ['success' => false, 'data' => 'User is not a Delivery Guy'];
            }
        } else {
            $response = ['success' => false, 'data' => 'Invalid email or password'];
        }
    
        return response()->json($response, 201);
    }

    /**
     * @param Request $request
     */
     
     public function updateDeliveryUserInfo(Request $request)
    {
        $deliveryUser = auth()->user();
    
        if ($deliveryUser && $deliveryUser->hasRole('Delivery Guy')) {
            $onGoingDeliveriesCount = AcceptDelivery::whereHas('order', function ($query) {
                $query->whereIn('orderstatus_id', ['3', '4']);
            })->where('user_id', $deliveryUser->id)->where('is_complete', 0)->count();
    
            $completedDeliveriesCount = AcceptDelivery::whereHas('order', function ($query) {
                $query->whereIn('orderstatus_id', ['5']);
            })->where('user_id', $deliveryUser->id)->where('is_complete', 1)->count();
    
            $orders = AcceptDelivery::whereHas('order', function ($query) {
                $query->whereIn('orderstatus_id', ['3', '4']);
            })->where('user_id', $deliveryUser->id)
                ->with(['order' => function ($q) {
                    $q->select('id', 'orderstatus_id', 'unique_order_id', 'address', 'payment_mode', 'payable');
                }])->orderBy('created_at', 'ASC')->paginate(20);
    
            $earnings = $deliveryUser->transactions()->orderBy('id', 'ASC')->paginate(50);
            $totalEarnings = $deliveryUser->transactions()->where('type', 'deposit')->sum('amount') / 100;
    
            $deliveryCollection = DeliveryCollection::where('user_id', $deliveryUser->id)->first();
            $deliveryCollectionAmount = $deliveryCollection ? $deliveryCollection->amount : 0;
    
            $dateRange = Carbon::today()->subDays(7);
            $earningData = DB::table('transactions')
                ->where('payable_id', $deliveryUser->id)
                ->where('created_at', '>=', $dateRange)
                ->where('type', 'deposit')
                ->select(DB::raw('sum(amount) as total'), DB::raw('date(created_at) as dates'))
                ->groupBy('dates')
                ->orderBy('dates', 'ASC')
                ->get();
    
            $amount = [];
            for ($i = 0; $i <= 6; $i++) {
                $amount[] = isset($earningData[$i]) ? $earningData[$i]->total / 100 : 0;
            }
    
            $days = [];
            for ($i = 0; $i <= 6; $i++) {
                $days[] = Carbon::now()->subDays($i)->format('D');
            }
    
            $amtArr = array_map(function ($amt) {
                return ['y' => $amt];
            }, array_reverse($amount));
    
            $dayArr = array_map(function ($day) {
                return ['x' => $day];
            }, array_reverse($days));
    
            $chartData = array_map(function ($amt, $day) {
                return array_merge($amt, $day);
            }, $amtArr, $dayArr);
    
            $ratings = Rating::where('delivery_id', $deliveryUser->id)->select(['rating_delivery', 'review_delivery'])->orderBy('id', 'ASC')->get();
            $averageRating = number_format((float) $ratings->avg('rating_delivery'), 1, '.', '');
    
            if ($request->has('toggle_status')) {
                $status = $deliveryUser->delivery_guy_detail->status;
                if ($request->has('force_offline') && $request->force_offline == 'true') {
                    $deliveryUser->delivery_guy_detail->status = false;
                } else {
                    $deliveryUser->delivery_guy_detail->status = !$status;
                }
                $deliveryUser->delivery_guy_detail->save();
            }
            $status = $deliveryUser->delivery_guy_detail->status;
    
            $record = $this->updateDeliveryGuyActiveRecords($deliveryUser, $status);
    
            $response = [
                'success' => true,
                'data' => [
                    'id' => $deliveryUser->id,
                    'auth_token' => $deliveryUser->auth_token,
                    'name' => $deliveryUser->name,
                    'email' => $deliveryUser->email,
                    'wallet_balance' => number_format($deliveryUser->balanceFloat, 2, '.', ''), // Use actual wallet balance
                    'onGoingCount' => $onGoingDeliveriesCount,
                    'completedCount' => $completedDeliveriesCount,
                    'orders' => $orders,
                    'earnings' => $earnings,
                    'totalEarnings' => $totalEarnings,
                    'deliveryCollection' => $deliveryCollectionAmount,
                    'averageRating' => $averageRating,
                    'ratings' => $ratings,
                    'status' => $status,
                ],
                'chart' => [
                    'chartData' => $chartData,
                ],
            ];
            return response()->json($response, 201);
        }
    
        $response = ['success' => false, 'data' => 'Record doesnt exists'];
        return response()->json($response);
    }

    private function updateDeliveryGuyActiveRecords($user, $status)
    {
        $record = DeliveryGuyActiveRecord::where('user_id', $user->id)->latest()->first();
        if ($record && $record->offline_time == null) {
            if ($status == 0) {
                $record->offline_time = Carbon::now();
                $record->save();
            } elseif ($status == 1) {
                return false;
            }
        } else {
            if ($status == 1) {
                $record = new DeliveryGuyActiveRecord();
                $record->user_id = $user->id;
                $record->date = Carbon::today();
                $record->online_time = Carbon::now();
                $record->save();
            } else {
                return false;
            }
        }
        return true;
    }
    
    public function getCompletedOrders(Request $request)
    {
        $deliveryUser = auth()->user();
        if ($deliveryUser) {
            $orders = AcceptDelivery::whereHas('order', function ($query) {
                $query->where('orderstatus_id', '5'); //only completed orders
            })->where('user_id', $deliveryUser->id)
                ->with(array('order' => function ($q) {
                    $q->select('id', 'orderstatus_id', 'unique_order_id', 'address', 'payment_mode', 'payable');
                }))->orderBy('created_at', 'DESC')->paginate(5);

            return response()->json($orders);
        }
        return response()->json(['success' => false], 401);
    }
      public function getorders(){
        $delivery = auth()->user();
        
    }

      

public function getDeliveryOrders(Request $request)
{
    $deliveryUser = Auth::user();

    Log::info('Starting getDeliveryOrders', [
        'driver_id' => $deliveryUser->id,
        'driver_balance' => $deliveryUser->balanceFloat,
        'timestamp' => now()->toDateTimeString()
    ]);

    $userRestaurants = $deliveryUser->restaurants;
    $deliveryGuyCommissionRate = $deliveryUser->delivery_guy_detail->commission_rate;

    // استرجاع الحد النقدي من إعدادات السائق
    $cash_limit = $deliveryUser->delivery_guy_detail->cash_limit;

    // التحقق من الحد النقدي بناءً على رصيد المحفظة
    $is_in_limit = ($cash_limit == 0) || ($deliveryUser->balanceFloat <= $cash_limit);

    Log::info('Cash limit check', [
        'driver_id' => $deliveryUser->id,
        'balanceFloat' => $deliveryUser->balanceFloat,
        'cash_limit' => $cash_limit,
        'is_in_limit' => $is_in_limit
    ]);

    $max_accept_delivery_limit = $deliveryUser->delivery_guy_detail->max_accept_delivery_limit;
    $nonCompleteOrders = AcceptDelivery::where('user_id', $deliveryUser->id)->where('is_complete', 0)->with('order')->get();
    $countNonCompleteOrders = 0;
    if ($nonCompleteOrders) {
        foreach ($nonCompleteOrders as $nonCompleteOrder) {
            if ($nonCompleteOrder->order && $nonCompleteOrder->order->orderstatus_id != 6) {
                $countNonCompleteOrders++;
            }
        }
    }
    $isUnderQueueLimit = $countNonCompleteOrders < $max_accept_delivery_limit;

    // استرجاع الطلبات الجديدة بناءً على delay_before_driver_visibility
    $orders = Order::where('orderstatus_id', '2')
        ->where('delivery_type', '1')
        ->where(function ($query) {
            $query->where('delay_before_driver_visibility', '<=', now())
                  ->orWhereNull('delay_before_driver_visibility');
        })
        ->with(['restaurant' => function ($query) {
            $query->select('id', 'name', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm');
        }])
        ->orderByRaw('is_scheduled ASC, delay_before_driver_visibility ASC')
        ->get();
    
    // $orders = Order::whereIn('orderstatus_id', ['2', '10'])
    //     ->where('delivery_type', '1')
    //     ->where(function ($query) {
    //         $query->where('delay_before_driver_visibility', '<=', now())
    //               ->orWhereNull('delay_before_driver_visibility')
    //               ->orWhereExists(function ($subQuery) {
    //                   $subQuery->select(DB::raw(1))
    //                           ->from('orders as o')
    //                           ->whereRaw('o.id = orders.id')
    //                           ->where('is_scheduled', true)
    //                           ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(schedule_slot, "$.open")) <= ?', [now()->addMinutes(15)->format('H:i')]);
    //               });
    //     })
    //     ->with(['restaurant' => function ($query) {
    //         $query->select('id', 'name', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm');
    //     }])
    //     ->orderByRaw('is_scheduled DESC, FIELD(orderstatus_id, 10, 2), JSON_UNQUOTE(JSON_EXTRACT(schedule_slot, "$.open")) ASC, delay_before_driver_visibility ASC')
    //     ->get();

    // Log::info('Orders retrieved before filtering', [
    //     'orders_count' => $orders->count(),
    //     'orders' => $orders->pluck('unique_order_id')->toArray(),
    //     'restaurants' => $orders->pluck('restaurant_id')->toArray()
    // ]);

    $deliveryGuyNewOrders = collect();
    foreach ($orders as $order) {
        Log::info('Processing order', [
            'order_id' => $order->id,
            'delivery_type' => $order->delivery_type ?? 'Not set',
            'orderstatus_id' => $order->orderstatus_id ?? 'Not set',
            'updated_at' => $order->updated_at,
            'delay_before_driver_visibility' => $order->delay_before_driver_visibility,
        ]);

        $orderData = (object) [];
        $adjusted_total = $order->total;
        $adjusted_delivery_charge = $order->delivery_charge;
        $discounted_delivery_charge = $order->delivery_charge;
        $remaining_amount = 0;
        $is_wallet_payment = false;
        $coupon_amount = null;
        $coupon_delivery_discount = 0;

        if ($order->coupon_name) {
            $coupon = Coupon::where('code', $order->coupon_name)->first();
            if ($coupon) {
                Log::info('Coupon found for new order', [
                    'order_id' => $order->id,
                    'coupon_code' => $coupon->code,
                    'is_used_for_delivery' => $coupon->is_used_for_delivery,
                    'amount' => $coupon->amount,
                    'delivery_discount_percentage' => $coupon->delivery_discount_percentage
                ]);
                if ($coupon->is_used_for_delivery) {
                    $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                    if ($coupon->delivery_discount_percentage) {
                        $discount_percentage = $coupon->delivery_discount_percentage;
                        $discount_factor = $discount_percentage > 1 ? $discount_percentage / 100 : $discount_percentage;
                        $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                        $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                    } else {
                        $coupon_delivery_discount = $coupon->amount ?? $order->coupon_amount;
                        $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                    }
                    $adjusted_delivery_charge = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100 ? -$original_delivery_charge : $discounted_delivery_charge;
                    $order->is_free_delivery = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100;
                    $order->actual_delivery_charge = $original_delivery_charge;
                    $order->delivery_charge = $adjusted_delivery_charge;
                    $coupon_amount = $coupon_delivery_discount;
                } else {
                    $coupon_amount = $coupon->amount ?? $order->coupon_amount;
                    $adjusted_total = max(0, $order->total - $coupon_amount);
                }
            } else {
                $coupon_amount = $order->coupon_amount ?? 0;
                if ($order->is_free_delivery) {
                    $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                    $coupon_delivery_discount = $original_delivery_charge;
                    $discounted_delivery_charge = 0;
                    $adjusted_delivery_charge = -$original_delivery_charge;
                    $order->delivery_charge = $adjusted_delivery_charge;
                    $order->actual_delivery_charge = $original_delivery_charge;
                    $coupon_amount = $coupon_delivery_discount;
                } else {
                    $adjusted_total = max(0, $order->total - $coupon_amount);
                }
            }
            Log::info('Coupon processing result for new order', [
                'order_id' => $order->id,
                'coupon_amount' => $coupon_amount,
                'coupon_delivery_discount' => $coupon_delivery_discount,
                'adjusted_total' => $adjusted_total,
                'adjusted_delivery_charge' => $adjusted_delivery_charge,
                'discounted_delivery_charge' => $discounted_delivery_charge
            ]);
        }

        if ($order->actual_payment_mode == 'WALLET') {
            $is_wallet_payment = true;
            if ($order->wallet_amount >= $adjusted_total) {
                $remaining_amount = 0;
                $delivery_charge_value = $order->actual_delivery_charge ?? $order->delivery_charge;
                if ($delivery_charge_value <= 0) {
                    Log::warning('Invalid delivery charge for WALLET payment', [
                        'order_id' => $order->id,
                        'actual_delivery_charge' => $order->actual_delivery_charge,
                        'delivery_charge' => $order->delivery_charge
                    ]);
                } else {
                    $adjusted_delivery_charge = -$delivery_charge_value;
                    $discounted_delivery_charge = $adjusted_delivery_charge;
                }
            } else {
                $remaining_amount = $adjusted_total - $order->wallet_amount;
            }
            Log::info('WALLET payment details for new order', [
                'order_id' => $order->id,
                'wallet_amount' => $order->wallet_amount,
                'adjusted_total' => $adjusted_total,
                'remaining_amount' => $remaining_amount,
                'delivery_charge' => $adjusted_delivery_charge
            ]);
        } else {
            $is_wallet_payment = false;
            $remaining_amount = $adjusted_total;
            Log::info('Non-WALLET payment for new order', [
                'order_id' => $order->id,
                'payment_mode' => $order->actual_payment_mode,
                'remaining_amount' => $remaining_amount,
                'delivery_charge' => $adjusted_delivery_charge
            ]);
        }

        $commission = 0;
        $min_order_price = $order->restaurant->min_order_price ?? 0;
        $free_delivery_subtotal = $order->restaurant->free_delivery_subtotal ?? 0;
        $is_below_min_order = $adjusted_total < $min_order_price;
        $is_free_delivery_eligible = $free_delivery_subtotal > 0 && $adjusted_total >= $free_delivery_subtotal;

        if ($is_free_delivery_eligible) {
            $free_delivery_cost = $order->restaurant->free_delivery_cost ?? ($order->actual_delivery_charge ?? abs($adjusted_delivery_charge));
            $free_delivery_comm = $order->restaurant->free_delivery_comm ?? 0;
            $commission = $free_delivery_cost * ($free_delivery_comm / 100);
            Log::info('Driver commission calculated using free_delivery_cost', [
                'order_id' => $order->id,
                'adjusted_total' => $adjusted_total,
                'free_delivery_subtotal' => $free_delivery_subtotal,
                'free_delivery_cost' => $free_delivery_cost,
                'free_delivery_comm' => $free_delivery_comm,
                'commission' => $commission
            ]);
        } elseif ($is_below_min_order) {
            if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                $commission = 0;
            } else {
                if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                    $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                    $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                    if ($order->coupon_name) {
                        $coupon = Coupon::where('code', $order->coupon_name)->first();
                        if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                            $commission = $delivery_charge;
                        } else {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                        }
                    } else {
                        $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                    }
                }
            }
            Log::info('Driver commission calculated for below minimum order without free delivery', [
                'order_id' => $order->id,
                'adjusted_total' => $adjusted_total,
                'min_order_price' => $min_order_price,
                'commission' => $commission
            ]);
        } else {
            if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                $commission = 0;
            } else {
                if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount < $adjusted_total) {
                    if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                        $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                    } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                        $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                        if ($order->coupon_name) {
                            $coupon = Coupon::where('code', $order->coupon_name)->first();
                            if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                $commission = $delivery_charge;
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        } else {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                        }
                    }
                } else {
                    if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                        $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                    } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                        $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                        if ($order->coupon_name) {
                            $coupon = Coupon::where('code', $order->coupon_name)->first();
                            if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                $commission = $delivery_charge;
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        } else {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                        }
                    }
                }
            }
        }

        if (!empty($deliveryUser->delivery_guy_detail) && $deliveryUser->delivery_guy_detail->tip_commission_rate && !is_null($deliveryUser->delivery_guy_detail->tip_commission_rate)) {
            $tip_amount = $deliveryUser->delivery_guy_detail->tip_commission_rate / 100 * $order->tip_amount;
            $tip_amount = number_format((float) $tip_amount, 2, '.', '');
        } else {
            $tip_amount = null;
        }

        $orderData->commission = number_format((float) $commission, 2, '.', '');
        $orderData->tip_amount = $tip_amount;
        $orderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
        $orderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
        $orderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
        $orderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
        $orderData->is_wallet_payment = $is_wallet_payment;
        $orderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
        $orderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');

        $notificationOrder = (object) array_merge($order->toArray(), (array) $orderData);
        $notificationOrder->delivery_type = $order->delivery_type;
        $notificationOrder->orderstatus_id = $order->orderstatus_id;

        Log::info('Driver commission calculated for new order', [
            'order_id' => $order->id,
            'actual_payment_mode' => $order->actual_payment_mode,
            'wallet_amount' => $order->wallet_amount,
            'total' => $order->total,
            'adjusted_total' => $adjusted_total,
            'delivery_charge' => $orderData->delivery_charge,
            'discounted_delivery_charge' => $orderData->discounted_delivery_charge,
            'actual_delivery_charge' => $order->actual_delivery_charge,
            'is_free_delivery' => $order->is_free_delivery,
            'coupon_amount' => $coupon_amount,
            'coupon_delivery_discount' => $orderData->coupon_delivery_discount,
            'commission' => $commission,
            'commission_rate' => $deliveryGuyCommissionRate,
            'remaining_amount' => $remaining_amount,
            'alert_given' => $order->alert_given ?? false,
            'balanceFloat' => $deliveryUser->balanceFloat,
            'cash_limit' => $cash_limit,
            'is_in_limit' => $is_in_limit
        ]);

        foreach ($userRestaurants as $ur) {
            if ($order->restaurant->id == $ur->id && $is_in_limit && $isUnderQueueLimit) {
                $deliveryGuyNewOrders->push(array_merge($order->toArray(), (array) $orderData));

                $alertGiven = $order->alert_given ?? false;
                if (count($deliveryGuyNewOrders) > 0 && !$alertGiven && $order->restaurant->show_time_on_order_accept) {
                    Log::info('Sending notification for order', [
                        'order_id' => $order->id,
                        'delivery_type' => $notificationOrder->delivery_type,
                        'orderstatus_id' => $notificationOrder->orderstatus_id
                    ]);
                    sendPushNotificationToDelivery($order->restaurant->id, $notificationOrder);
                    sendSmsToDelivery($order->restaurant->id);
                    Order::where('id', $order->id)->update(['alert_given' => true]);
                }
            }
        }
    }

    $alreadyAcceptedDeliveries = collect();
    $acceptDeliveries = AcceptDelivery::where('user_id', Auth::user()->id)
        ->where('is_complete', 0)
        ->whereHas('order', function ($q) {
            $q->where('orderstatus_id', '3')
              ->whereNotNull('delivery_type')
              ->where('delivery_type', '1');
        })
        ->with(['order.restaurant' => function ($query) {
            $query->select('id', 'name', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm');
        }])
        ->get();
    foreach ($acceptDeliveries as $ad) {
        $order = $ad->order;
        if ($order) {
            Log::info('Processing accepted order', [
                'order_id' => $order->id,
                'delivery_type' => $order->delivery_type ?? 'Not set',
                'orderstatus_id' => $order->orderstatus_id ?? 'Not set'
            ]);

            $orderData = (object) [];
            $adjusted_total = $order->total;
            $adjusted_delivery_charge = $order->delivery_charge;
            $discounted_delivery_charge = $order->delivery_charge;
            $remaining_amount = 0;
            $is_wallet_payment = false;
            $coupon_amount = null;
            $coupon_delivery_discount = 0;

            if ($order->coupon_name) {
                $coupon = Coupon::where('code', $order->coupon_name)->first();
                if ($coupon) {
                    Log::info('Coupon found for accepted order', [
                        'order_id' => $order->id,
                        'coupon_code' => $coupon->code,
                        'is_used_for_delivery' => $coupon->is_used_for_delivery,
                        'amount' => $coupon->amount,
                        'delivery_discount_percentage' => $coupon->delivery_discount_percentage
                    ]);
                    if ($coupon->is_used_for_delivery) {
                        $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                        if ($coupon->delivery_discount_percentage) {
                            $discount_percentage = $coupon->delivery_discount_percentage;
                            $discount_factor = $discount_percentage > 1 ? $discount_percentage / 100 : $discount_percentage;
                            $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                            $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                        } else {
                            $coupon_delivery_discount = $coupon->amount ?? $order->coupon_amount;
                            $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                        }
                        $adjusted_delivery_charge = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100 ? -$original_delivery_charge : $discounted_delivery_charge;
                        $order->is_free_delivery = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100;
                        $order->actual_delivery_charge = $original_delivery_charge;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $coupon_amount = $coupon_delivery_discount;
                    } else {
                        $coupon_amount = $coupon->amount ?? $order->coupon_amount;
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                } else {
                    $coupon_amount = $order->coupon_amount ?? 0;
                    if ($order->is_free_delivery) {
                        $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                        $coupon_delivery_discount = $original_delivery_charge;
                        $discounted_delivery_charge = 0;
                        $adjusted_delivery_charge = -$original_delivery_charge;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $order->actual_delivery_charge = $original_delivery_charge;
                        $coupon_amount = $coupon_delivery_discount;
                    } else {
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                }
                Log::info('Coupon processing result for accepted order', [
                    'order_id' => $order->id,
                    'coupon_amount' => $coupon_amount,
                    'coupon_delivery_discount' => $coupon_delivery_discount,
                    'adjusted_total' => $adjusted_total,
                    'adjusted_delivery_charge' => $adjusted_delivery_charge,
                    'discounted_delivery_charge' => $discounted_delivery_charge
                ]);
            }

            if ($order->actual_payment_mode == 'WALLET') {
                $is_wallet_payment = true;
                if ($order->wallet_amount >= $adjusted_total) {
                    $remaining_amount = 0;
                    $delivery_charge_value = $order->actual_delivery_charge ?? $order->delivery_charge;
                    if ($delivery_charge_value <= 0) {
                        Log::warning('Invalid delivery charge for WALLET payment', [
                            'order_id' => $order->id,
                            'actual_delivery_charge' => $order->actual_delivery_charge,
                            'delivery_charge' => $order->delivery_charge
                        ]);
                    } else {
                        $adjusted_delivery_charge = -$delivery_charge_value;
                        $discounted_delivery_charge = $adjusted_delivery_charge;
                    }
                } else {
                    $remaining_amount = $adjusted_total - $order->wallet_amount;
                }
                Log::info('WALLET payment details for accepted order', [
                    'order_id' => $order->id,
                    'wallet_amount' => $order->wallet_amount,
                    'adjusted_total' => $adjusted_total,
                    'remaining_amount' => $remaining_amount,
                    'delivery_charge' => $adjusted_delivery_charge
                ]);
            } else {
                $is_wallet_payment = false;
                $remaining_amount = $adjusted_total;
                Log::info('Non-WALLET payment for accepted order', [
                    'order_id' => $order->id,
                    'payment_mode' => $order->actual_payment_mode,
                    'remaining_amount' => $remaining_amount,
                    'delivery_charge' => $adjusted_delivery_charge
                ]);
            }

            $commission = 0;
            $min_order_price = $order->restaurant->min_order_price ?? 0;
            $free_delivery_subtotal = $order->restaurant->free_delivery_subtotal ?? 0;
            $is_below_min_order = $adjusted_total < $min_order_price;
            $is_free_delivery_eligible = $free_delivery_subtotal > 0 && $adjusted_total >= $free_delivery_subtotal;

            if ($is_below_min_order || $is_free_delivery_eligible) {
                $free_delivery_cost = $order->restaurant->free_delivery_cost ?? ($order->actual_delivery_charge ?? abs($adjusted_delivery_charge));
                $free_delivery_comm = $order->restaurant->free_delivery_comm ?? 0;
                $commission = $free_delivery_cost * ($free_delivery_comm / 100);
                Log::info('Driver commission calculated using free_delivery_cost for accepted order', [
                    'order_id' => $order->id,
                    'adjusted_total' => $adjusted_total,
                    'min_order_price' => $min_order_price,
                    'free_delivery_subtotal' => $free_delivery_subtotal,
                    'free_delivery_cost' => $free_delivery_cost,
                    'free_delivery_comm' => $free_delivery_comm,
                    'commission' => $commission
                ]);
            } else {
                if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                    $commission = 0;
                } else {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount < $adjusted_total) {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                } else {
                                    $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                                }
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        }
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                } else {
                                    $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                                }
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        }
                    }
                }
            }

            if (!empty($deliveryUser->delivery_guy_detail) && $deliveryUser->delivery_guy_detail->tip_commission_rate && !is_null($deliveryUser->delivery_guy_detail->tip_commission_rate)) {
                $tip_amount = $deliveryUser->delivery_guy_detail->tip_commission_rate / 100 * $order->tip_amount;
                $tip_amount = number_format((float) $tip_amount, 2, '.', '');
            } else {
                $tip_amount = null;
            }

            $orderData->commission = number_format((float) $commission, 2, '.', '');
            $orderData->tip_amount = $tip_amount;
            $orderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
            $orderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
            $orderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
            $orderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
            $orderData->is_wallet_payment = $is_wallet_payment;
            $orderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
            $orderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');

            $order->setAppends([]);
            $orderArray = $order->toArray();
            $orderArray['delivery_charge'] = $orderData->delivery_charge;
            $orderArray['discounted_delivery_charge'] = $orderData->discounted_delivery_charge;
            $orderArray['coupon_delivery_discount'] = $orderData->coupon_delivery_discount;
            $orderArray['delivery_type'] = $order->delivery_type;
            $orderArray['orderstatus_id'] = $order->orderstatus_id;
            $alreadyAcceptedDeliveries->push(array_merge($orderArray, (array) $orderData));
        }
    }

    $pickedupOrders = collect();
    $acceptDeliveries = AcceptDelivery::where('user_id', Auth::user()->id)
        ->where('is_complete', 0)
        ->whereHas('order', function ($q) {
            $q->where('orderstatus_id', '4')
              ->whereNotNull('delivery_type')
              ->where('delivery_type', '1');
        })
        ->with(['order.restaurant' => function ($query) {
            $query->select('id', 'name', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm');
        }])
        ->get();
    foreach ($acceptDeliveries as $ad) {
        $order = $ad->order;
        if ($order) {
            Log::info('Processing picked up order', [
                'order_id' => $order->id,
                'delivery_type' => $order->delivery_type ?? 'Not set',
                'orderstatus_id' => $order->orderstatus_id ?? 'Not set'
            ]);

            $orderData = (object) [];
            $adjusted_total = $order->total;
            $adjusted_delivery_charge = $order->delivery_charge;
            $discounted_delivery_charge = $order->delivery_charge;
            $remaining_amount = 0;
            $is_wallet_payment = false;
            $coupon_amount = null;
            $coupon_delivery_discount = 0;

            if ($order->coupon_name) {
                $coupon = Coupon::where('code', $order->coupon_name)->first();
                if ($coupon) {
                    Log::info('Coupon found for picked up order', [
                        'order_id' => $order->id,
                        'coupon_code' => $coupon->code,
                        'is_used_for_delivery' => $coupon->is_used_for_delivery,
                        'amount' => $coupon->amount,
                        'delivery_discount_percentage' => $coupon->delivery_discount_percentage
                    ]);
                    if ($coupon->is_used_for_delivery) {
                        $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                        if ($coupon->delivery_discount_percentage) {
                            $discount_percentage = $coupon->delivery_discount_percentage;
                            $discount_factor = $discount_percentage > 1 ? $discount_percentage / 100 : $discount_percentage;
                            $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                            $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                        } else {
                            $coupon_delivery_discount = $coupon->amount ?? $order->coupon_amount;
                            $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                        }
                        $adjusted_delivery_charge = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100 ? -$original_delivery_charge : $discounted_delivery_charge;
                        $order->is_free_delivery = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100;
                        $order->actual_delivery_charge = $original_delivery_charge;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $coupon_amount = $coupon_delivery_discount;
                    } else {
                        $coupon_amount = $coupon->amount ?? $order->coupon_amount;
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                } else {
                    $coupon_amount = $order->coupon_amount ?? 0;
                    if ($order->is_free_delivery) {
                        $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                        $coupon_delivery_discount = $original_delivery_charge;
                        $discounted_delivery_charge = 0;
                        $adjusted_delivery_charge = -$original_delivery_charge;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $order->actual_delivery_charge = $original_delivery_charge;
                        $coupon_amount = $coupon_delivery_discount;
                    } else {
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                }
                Log::info('Coupon processing result for picked up order', [
                    'order_id' => $order->id,
                    'coupon_amount' => $coupon_amount,
                    'coupon_delivery_discount' => $coupon_delivery_discount,
                    'adjusted_total' => $adjusted_total,
                    'adjusted_delivery_charge' => $adjusted_delivery_charge,
                    'discounted_delivery_charge' => $discounted_delivery_charge
                ]);
            }

            if ($order->actual_payment_mode == 'WALLET') {
                $is_wallet_payment = true;
                if ($order->wallet_amount >= $adjusted_total) {
                    $remaining_amount = 0;
                    $delivery_charge_value = $order->actual_delivery_charge ?? $order->delivery_charge;
                    if ($delivery_charge_value <= 0) {
                        Log::info('Invalid delivery charge for WALLET payment', [
                            'order_id' => $order->id,
                            'actual_delivery_charge' => $order->actual_delivery_charge,
                            'delivery_charge' => $order->delivery_charge
                        ]);
                    } else {
                        $adjusted_delivery_charge = -$delivery_charge_value;
                        $discounted_delivery_charge = $adjusted_delivery_charge;
                    }
                } else {
                    $remaining_amount = $adjusted_total - $order->wallet_amount;
                }
                Log::info('WALLET payment details for picked up order', [
                    'order_id' => $order->id,
                    'wallet_amount' => $order->wallet_amount,
                    'adjusted_total' => $adjusted_total,
                    'remaining_amount' => $remaining_amount,
                    'delivery_charge' => $adjusted_delivery_charge
                ]);
            } else {
                $is_wallet_payment = false;
                $remaining_amount = $adjusted_total;
                Log::info('Non-WALLET payment for picked up order', [
                    'order_id' => $order->id,
                    'payment_mode' => $order->actual_payment_mode,
                    'remaining_amount' => $remaining_amount,
                    'delivery_charge' => $adjusted_delivery_charge
                ]);
            }

            $commission = 0;
            $min_order_price = $order->restaurant->min_order_price ?? 0;
            $free_delivery_subtotal = $order->restaurant->free_delivery_subtotal ?? 0;
            $is_below_min_order = $adjusted_total < $min_order_price;
            $is_free_delivery_eligible = $free_delivery_subtotal > 0 && $adjusted_total >= $free_delivery_subtotal;

            if ($is_below_min_order || $is_free_delivery_eligible) {
                $free_delivery_cost = $order->restaurant->free_delivery_cost ?? ($order->actual_delivery_charge ?? abs($adjusted_delivery_charge));
                $free_delivery_comm = $order->restaurant->free_delivery_comm ?? 0;
                $commission = $free_delivery_cost * ($free_delivery_comm / 100);
                Log::info('Driver commission calculated using free_delivery_cost for picked up order', [
                    'order_id' => $order->id,
                    'adjusted_total' => $adjusted_total,
                    'min_order_price' => $min_order_price,
                    'free_delivery_subtotal' => $free_delivery_subtotal,
                    'free_delivery_cost' => $free_delivery_cost,
                    'free_delivery_comm' => $free_delivery_comm,
                    'commission' => $commission
                ]);
            } else {
                if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                    $commission = 0;
                } else {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount < $adjusted_total) {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                } else {
                                    $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                                }
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        }
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - $order->tip_amount);
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $delivery_charge = $this->getDeliveryChargeForCommissionCalc($order);
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                } else {
                                    $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                                }
                            } else {
                                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            }
                        }
                    }
                }
            }

            if (!empty($deliveryUser->delivery_guy_detail) && $deliveryUser->delivery_guy_detail->tip_commission_rate && !is_null($deliveryUser->delivery_guy_detail->tip_commission_rate)) {
                $tip_amount = $deliveryUser->delivery_guy_detail->tip_commission_rate / 100 * $order->tip_amount;
                $tip_amount = number_format((float) $tip_amount, 2, '.', '');
            } else {
                $tip_amount = null;
            }

            $orderData->commission = number_format((float) $commission, 2, '.', '');
            $orderData->tip_amount = $tip_amount;
            $orderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
            $orderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
            $orderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
            $orderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
            $orderData->is_wallet_payment = $is_wallet_payment;
            $orderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
            $orderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');

            $order->setAppends([]);
            $orderArray = $order->toArray();
            $orderArray['delivery_charge'] = $orderData->delivery_charge;
            $orderArray['discounted_delivery_charge'] = $orderData->discounted_delivery_charge;
            $orderArray['coupon_delivery_discount'] = $orderData->coupon_delivery_discount;
            $orderArray['delivery_type'] = $order->delivery_type;
            $orderArray['orderstatus_id'] = $order->orderstatus_id;
            $pickedupOrders->push(array_merge($orderArray, (array) $orderData));
        }
    }

    $response = [
        'new_orders' => $deliveryGuyNewOrders,
        'accepted_orders' => $alreadyAcceptedDeliveries,
        'pickedup_orders' => $pickedupOrders
    ];

    Log::info('getDeliveryOrders response', [
        'driver_id' => $deliveryUser->id,
        'new_orders_count' => count($deliveryGuyNewOrders),
        'accepted_orders_count' => count($alreadyAcceptedDeliveries),
        'pickedup_orders_count' => count($pickedupOrders)
    ]);

    return response()->json($response);
}


    // aya
    public function getSingleDeliveryOrder(Request $request)
    {
    $deliveryUser = Auth::user();
    $singleOrder = Order::where('unique_order_id', $request->unique_order_id)->first();

    if ($singleOrder && $singleOrder->delivery_type == '2') {
        abort(401, 'This order is now set to Self-Pickup and cannot be viewed by drivers.');
    }

    if (!$this->canPerformAction($deliveryUser, $singleOrder)) {
        abort(401, 'Order cancelled or not found or cannot view order.');
    }

    $singleOrderId = $singleOrder->id;
    $checkOrder = AcceptDelivery::where('order_id', $singleOrderId)
        ->where('user_id', $deliveryUser->id)
        ->first();

    $deliveryGuyCommissionRate = $deliveryUser->delivery_guy_detail->commission_rate;

    // استخدام sub_total بدل total لحساب adjusted_total
    $adjusted_total = (float) $singleOrder->sub_total;
    $adjusted_delivery_charge = (float) ($singleOrder->delivery_charge ?? 0);
    $discounted_delivery_charge = $adjusted_delivery_charge;
    $remaining_amount = 0;
    $is_wallet_payment = false;
    $coupon_amount = 0;
    $coupon_delivery_discount = 0;

    // تحديث actual_delivery_charge إذا كان مختلفًا عن delivery_charge
    if (!$singleOrder->actual_delivery_charge || $singleOrder->delivery_charge != $singleOrder->actual_delivery_charge) {
        $singleOrder->actual_delivery_charge = $singleOrder->delivery_charge;
        \Log::info("Updated actual_delivery_charge to match delivery_charge in getSingleDeliveryOrder", [
            "order_id" => $singleOrder->id,
            "delivery_charge" => $singleOrder->delivery_charge,
            "actual_delivery_charge" => $singleOrder->actual_delivery_charge
        ]);
    }

    \Log::info("Delivery charge details in getSingleDeliveryOrder", [
        "order_id" => $singleOrder->id,
        "delivery_charge" => $singleOrder->delivery_charge,
        "actual_delivery_charge" => $singleOrder->actual_delivery_charge
    ]);

    // التحقق من أهلية التوصيل المجاني بناءً على sub_total قبل خصم الكوبون
    $is_free_delivery_eligible = false;
    $free_delivery_subtotal = (float) ($singleOrder->restaurant->free_delivery_subtotal ?? 0);
    $free_delivery_distance = (float) ($singleOrder->restaurant->free_delivery_distance ?? 0);
    $delivery_distance = (float) ($singleOrder->distance ?? 0);
    if ($free_delivery_subtotal > 0 && $singleOrder->sub_total >= $free_delivery_subtotal &&
        ($free_delivery_distance == 0 || $delivery_distance <= $free_delivery_distance)) {
        $is_free_delivery_eligible = true;
        $adjusted_delivery_charge = 0;
        $discounted_delivery_charge = 0;
        $singleOrder->delivery_charge = 0;
        $singleOrder->is_free_delivery = true;
    }

    // معالجة الكوبون
    if ($singleOrder->coupon_name) {
        $coupon = Coupon::where('code', $singleOrder->coupon_name)->first();
        if ($coupon) {
            \Log::info('Coupon found for single order', [
                'order_id' => $singleOrder->id,
                'coupon_code' => $coupon->code,
                'is_used_for_delivery' => $coupon->is_used_for_delivery,
                'amount' => $coupon->amount,
                'delivery_discount_percentage' => $coupon->delivery_discount_percentage
            ]);

            if ($coupon->is_used_for_delivery && !$is_free_delivery_eligible) {
                $original_delivery_charge = (float) ($singleOrder->delivery_charge); // Use updated delivery_charge
                if ($coupon->delivery_discount_percentage) {
                    $discount_factor = $coupon->delivery_discount_percentage / 100;
                    $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                    $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                } else {
                    $coupon_delivery_discount = (float) ($coupon->amount ?? $singleOrder->coupon_amount);
                    $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                }
                $adjusted_delivery_charge = $coupon->discount_type === 'FREE' ? 0 : $discounted_delivery_charge;
                $singleOrder->delivery_charge = $adjusted_delivery_charge;
                $singleOrder->is_free_delivery = $adjusted_delivery_charge == 0;
                $singleOrder->actual_delivery_charge = $original_delivery_charge;
                $coupon_amount = $coupon_delivery_discount;
            } else {
                if ($coupon->discount_type == 'PERCENTAGE') {
                    $coupon_amount = ($coupon->discount / 100) * $singleOrder->sub_total;
                    if ($coupon->max_discount && $coupon_amount > $coupon->max_discount) {
                        $coupon_amount = (float) $coupon->max_discount;
                    }
                } else {
                    $coupon_amount = (float) ($coupon->amount ?? $singleOrder->coupon_amount);
                }
                $adjusted_total = max(0, $singleOrder->sub_total - $coupon_amount);
            }
        } else {
            $coupon_amount = (float) ($singleOrder->coupon_amount ?? 0);
            if ($singleOrder->is_free_delivery) {
                $original_delivery_charge = (float) ($singleOrder->delivery_charge);
                $coupon_delivery_discount = $original_delivery_charge;
                $discounted_delivery_charge = 0;
                $adjusted_delivery_charge = 0;
                $singleOrder->delivery_charge = $adjusted_delivery_charge;
                $singleOrder->actual_delivery_charge = $original_delivery_charge;
            } else {
                $adjusted_total = max(0, $singleOrder->sub_total - $coupon_amount);
            }
        }
        $singleOrder->coupon_amount = $coupon_amount;
    }

    \Log::info('Coupon and delivery processing', [
        'order_id' => $singleOrder->id,
        'coupon_amount' => $coupon_amount,
        'coupon_delivery_discount' => $coupon_delivery_discount,
        'adjusted_total' => $adjusted_total,
        'adjusted_delivery_charge' => $adjusted_delivery_charge,
        'is_free_delivery_eligible' => $is_free_delivery_eligible
    ]);

    // معالجة الدفع عبر المحفظة
    if ($singleOrder->actual_payment_mode == 'WALLET') {
        $is_wallet_payment = true;
        if ($singleOrder->wallet_amount >= $adjusted_total) {
            $remaining_amount = 0;
            $delivery_charge_value = (float) ($singleOrder->delivery_charge);
            if ($delivery_charge_value <= 0 && !$is_free_delivery_eligible && !$singleOrder->is_free_delivery) {
                \Log::warning('Invalid delivery charge for WALLET payment', [
                    'order_id' => $singleOrder->id,
                    'actual_delivery_charge' => $singleOrder->actual_delivery_charge,
                    'delivery_charge' => $singleOrder->delivery_charge
                ]);
            }
        } else {
            $remaining_amount = $adjusted_total - $singleOrder->wallet_amount;
        }
        \Log::info('WALLET payment details for single order', [
            'order_id' => $singleOrder->id,
            'wallet_amount' => $singleOrder->wallet_amount,
            'adjusted_total' => $adjusted_total,
            'remaining_amount' => $remaining_amount,
            'delivery_charge' => $adjusted_delivery_charge,
            'actual_delivery_charge' => $singleOrder->actual_delivery_charge
        ]);
    } else {
        $is_wallet_payment = false;
        $remaining_amount = $adjusted_total;
        \Log::info('Non-WALLET payment for single order', [
            'order_id' => $singleOrder->id,
            'payment_mode' => $singleOrder->actual_payment_mode,
            'remaining_amount' => $remaining_amount,
            'delivery_charge' => $adjusted_delivery_charge
        ]);
    }

    // حساب العمولة
    $commission = 0;
    $min_order_price = (float) ($singleOrder->restaurant->min_order_price ?? 0);
    $is_below_min_order = $singleOrder->sub_total < $min_order_price;
    $delivery_charge = (float) ($singleOrder->delivery_charge);

    if ($is_below_min_order || $is_free_delivery_eligible) {
        $free_delivery_cost = (float) ($singleOrder->restaurant->free_delivery_cost ?? $delivery_charge);
        $free_delivery_comm = (float) ($singleOrder->restaurant->free_delivery_comm ?? $deliveryGuyCommissionRate);
        $commission = $free_delivery_cost * ($free_delivery_comm / 100);
        \Log::info('Driver commission calculated using free_delivery_cost', [
            'order_id' => $singleOrder->id,
            'adjusted_total' => $adjusted_total,
            'min_order_price' => $min_order_price,
            'free_delivery_subtotal' => $free_delivery_subtotal,
            'free_delivery_distance' => $free_delivery_distance,
            'delivery_distance' => $delivery_distance,
            'free_delivery_cost' => $free_delivery_cost,
            'free_delivery_comm' => $free_delivery_comm,
            'commission' => $commission
        ]);
    } else {
        if ($singleOrder->actual_payment_mode == 'WALLET' && $singleOrder->wallet_amount >= $adjusted_total) {
            $commission = 0;
        } else {
            if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - ($singleOrder->tip_amount ?? 0));
            } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                if ($singleOrder->coupon_name) {
                    $coupon = Coupon::where('code', $singleOrder->coupon_name)->first();
                    if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                        $commission = $delivery_charge;
                    }
                }
            }
        }
        \Log::info('Driver commission calculated for regular order', [
            'order_id' => $singleOrder->id,
            'delivery_charge' => $delivery_charge,
            'commission_rate' => $deliveryGuyCommissionRate,
            'commission' => $commission
        ]);
    }

    // حساب عمولة البقشيش
    $tip_amount = 0;
    if ($deliveryUser->delivery_guy_detail && $deliveryUser->delivery_guy_detail->tip_commission_rate) {
        $tip_amount = ($deliveryUser->delivery_guy_detail->tip_commission_rate / 100) * ($singleOrder->tip_amount ?? 0);
        $tip_amount = number_format((float) $tip_amount, 2, '.', '');
    }

    // جلب بيانات الطلب
    $singleOrder = Order::where('unique_order_id', $request->unique_order_id)
        ->with(['restaurant' => function ($query) {
            $query->select('id', 'name', 'description', 'address', 'pincode', 'latitude', 'longitude', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm', 'free_delivery_distance');
        }])
        ->with(['orderitems' => function ($query) {
            $query->with(['order_item_addons' => function ($subQuery) {
                $subQuery->select('id', 'orderitem_id', 'addon_name', 'addon_price');
            }])->select('id', 'order_id', 'name', 'price', 'quantity', 'item_id');
        }])
        ->with(['user' => function ($query) {
            $query->select('id', 'name', 'phone');
        }])
        ->first();

    if (!$singleOrder) {
        abort(404, 'Order not found.');
    }

    // إعداد استجابة JSON
    $singleOrderData = (object) [];
    $singleOrderData->commission = number_format((float) $commission, 2, '.', '');
    $singleOrderData->tip_amount = $tip_amount;
    $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
    $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
    $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
    $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
    $singleOrderData->is_wallet_payment = $is_wallet_payment;
    $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
    $singleOrderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');
    $singleOrderData->free_delivery_cost = number_format((float) ($is_free_delivery_eligible || $singleOrder->is_free_delivery ? $singleOrder->restaurant->free_delivery_cost : 0), 2, '.', '');

    $singleOrder->setAppends([]);
    $singleOrderArray = $singleOrder->toArray();
    $singleOrderArray['delivery_charge'] = $singleOrderData->delivery_charge;
    $singleOrderArray['discounted_delivery_charge'] = $singleOrderData->discounted_delivery_charge;
    $singleOrderArray['coupon_delivery_discount'] = $singleOrderData->coupon_delivery_discount;
    $singleOrderArray['sub_total'] = number_format((float) $singleOrder->sub_total, 2, '.', '');
    $singleOrderArray['free_delivery_cost'] = $singleOrderData->free_delivery_cost;
    $singleOrderArray['delivery_distance'] = number_format((float) $delivery_distance, 2, '.', '');
    $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);

    \Log::info('Single Delivery Order Response', [
        'order_id' => $singleOrder->id,
        'sub_total' => $singleOrder->sub_total,
        'total' => $singleOrder->total,
        'adjusted_total' => $adjusted_total,
        'delivery_charge' => $singleOrderArray['delivery_charge'],
        'discounted_delivery_charge' => $singleOrderArray['discounted_delivery_charge'],
        'actual_delivery_charge' => $singleOrder->actual_delivery_charge,
        'wallet_amount' => $singleOrder->wallet_amount,
        'remaining_amount' => $remaining_amount,
        'commission' => $commission,
        'commission_rate' => $deliveryGuyCommissionRate,
        'tip_amount' => $tip_amount,
        'is_wallet_payment' => $is_wallet_payment,
        'coupon_amount' => $coupon_amount,
        'coupon_delivery_discount' => $singleOrderArray['coupon_delivery_discount'],
        'orderitems_count' => count($singleOrderArray['orderitems'] ?? []),
        'orderitems' => $singleOrderArray['orderitems'] ?? [],
        'delivery_distance' => $delivery_distance,
        'free_delivery_distance' => $singleOrder->restaurant->free_delivery_distance ?? 0,
        'free_delivery_cost' => $singleOrderArray['free_delivery_cost']
    ]);

    return response()->json($singleOrderArray);
}
    /**
     * @param Request $request
     */
    public function setDeliveryGuyGpsLocation(Request $request)
    {
        $deliveryUser = auth()->user();

        if ($deliveryUser->hasRole('Delivery Guy')) {
            $deliveryUser->delivery_guy_detail->delivery_lat = $request->delivery_lat;
            $deliveryUser->delivery_guy_detail->delivery_long = $request->delivery_long;
            $deliveryUser->delivery_guy_detail->heading = $request->heading;
            $deliveryUser->delivery_guy_detail->save();

            return response()->json(true);
        }
    }

    /**
     * @param Request $request
     */
    public function getDeliveryGuyGpsLocation(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();

        $location = new DeliveryLiveLocation();
        return $location->getDeliveryLiveLocation($order);
    }

    public function acceptToDeliver(Request $request)
    {
        $deliveryUser = auth()->user();
    
        \Log::info('Starting acceptToDeliver', [
            'order_id' => $request->order_id,
            'driver_id' => $deliveryUser->id,
        ]);
    
        if ($deliveryUser && $deliveryUser->hasRole('Delivery Guy')) {
            $max_accept_delivery_limit = $deliveryUser->delivery_guy_detail->max_accept_delivery_limit;
    
            $order = Order::where('id', $request->order_id)
                ->with(['restaurant' => function ($query) {
                    $query->select('id', 'name', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm', 'free_delivery_distance');
                }])
                ->with('orderitems.order_item_addons')
                ->with(['user' => function ($query) {
                    $query->select('id', 'name', 'phone');
                }])
                ->first();
    
            if ($order && $this->canPerformAction($deliveryUser, $order)) {
                $deliveryGuyCommissionRate = $deliveryUser->delivery_guy_detail->commission_rate;
    
                // استخدام sub_total لحساب adjusted_total
                $adjusted_total = (float) $order->sub_total;
                $adjusted_delivery_charge = (float) ($order->delivery_charge ?? 0);
                $discounted_delivery_charge = $adjusted_delivery_charge;
                $remaining_amount = 0;
                $is_wallet_payment = false;
                $coupon_amount = 0;
                $coupon_delivery_discount = 0;
                $is_free_delivery_eligible = false;
                $is_free_delivery_coupon = false;
    
                // تحديث actual_delivery_charge إذا كان مختلفًا عن delivery_charge
                if (!$order->actual_delivery_charge || $order->delivery_charge != $order->actual_delivery_charge) {
                    $order->actual_delivery_charge = $order->delivery_charge;
                    \Log::info("Updated actual_delivery_charge to match delivery_charge in acceptToDeliver", [
                        "order_id" => $order->id,
                        "delivery_charge" => $order->delivery_charge,
                        "actual_delivery_charge" => $order->actual_delivery_charge
                    ]);
                }
    
                \Log::info("Delivery charge details in acceptToDeliver", [
                    "order_id" => $order->id,
                    "delivery_charge" => $order->delivery_charge,
                    "actual_delivery_charge" => $order->actual_delivery_charge
                ]);
    
                // التحقق من أهلية التوصيل المجاني بناءً على sub_total قبل خصم الكوبون
                $free_delivery_subtotal = (float) ($order->restaurant->free_delivery_subtotal ?? 0);
                $free_delivery_distance = (float) ($order->restaurant->free_delivery_distance ?? 0);
                $delivery_distance = (float) ($order->distance ?? 0);
                if ($free_delivery_subtotal > 0 && $order->sub_total >= $free_delivery_subtotal &&
                    ($free_delivery_distance == 0 || $delivery_distance <= $free_delivery_distance)) {
                    $is_free_delivery_eligible = true;
                    $adjusted_delivery_charge = 0;
                    $discounted_delivery_charge = 0;
                    $order->delivery_charge = 0;
                    $order->is_free_delivery = true;
                }
    
                // معالجة الكوبون
                if ($order->coupon_name) {
                    $coupon = Coupon::where('code', $order->coupon_name)->first();
                    if ($coupon) {
                        \Log::info('Coupon found in acceptToDeliver', [
                            'order_id' => $order->id,
                            'coupon_code' => $coupon->code,
                            'is_used_for_delivery' => $coupon->is_used_for_delivery,
                            'amount' => $coupon->amount,
                            'delivery_discount_percentage' => $coupon->delivery_discount_percentage
                        ]);
    
                        if ($coupon->is_used_for_delivery && !$is_free_delivery_eligible) {
                            $original_delivery_charge = (float) ($order->delivery_charge); // Use updated delivery_charge
                            if ($coupon->delivery_discount_percentage) {
                                $discount_factor = $coupon->delivery_discount_percentage / 100;
                                $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                                $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                            } else {
                                $coupon_delivery_discount = (float) ($coupon->amount ?? $order->coupon_amount);
                                $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                            }
                            $adjusted_delivery_charge = $coupon->discount_type === 'FREE' || $coupon->delivery_discount_percentage == 100 ? 0 : $discounted_delivery_charge;
                            $order->delivery_charge = $adjusted_delivery_charge;
                            $is_free_delivery_coupon = $adjusted_delivery_charge == 0;
                            $order->actual_delivery_charge = $original_delivery_charge;
                            $coupon_amount = $coupon_delivery_discount;
                        } else {
                            if ($coupon->discount_type == 'PERCENTAGE') {
                                $coupon_amount = ($coupon->discount / 100) * $order->sub_total;
                                if ($coupon->max_discount && $coupon_amount > $coupon->max_discount) {
                                    $coupon_amount = (float) $coupon->max_discount;
                                }
                            } else {
                                $coupon_amount = (float) ($coupon->amount ?? $order->coupon_amount);
                            }
                            $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                        }
                    } else {
                        $coupon_amount = (float) ($order->coupon_amount ?? 0);
                        if ($order->is_free_delivery) {
                            $original_delivery_charge = (float) ($order->delivery_charge);
                            $coupon_delivery_discount = $original_delivery_charge;
                            $discounted_delivery_charge = 0;
                            $adjusted_delivery_charge = 0;
                            $order->delivery_charge = $adjusted_delivery_charge;
                            $order->actual_delivery_charge = $original_delivery_charge;
                            $is_free_delivery_coupon = true;
                        } else {
                            $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                        }
                    }
                    \Log::info('Coupon processing result in acceptToDeliver', [
                        'order_id' => $order->id,
                        'coupon_amount' => $coupon_amount,
                        'coupon_delivery_discount' => $coupon_delivery_discount,
                        'adjusted_total' => $adjusted_total,
                        'adjusted_delivery_charge' => $adjusted_delivery_charge,
                        'discounted_delivery_charge' => $discounted_delivery_charge,
                        'is_free_delivery_coupon' => $is_free_delivery_coupon
                    ]);
                }
    
                // معالجة الدفع عبر المحفظة
                if ($order->actual_payment_mode == 'WALLET') {
                    $is_wallet_payment = true;
                    if ($order->wallet_amount >= $adjusted_total) {
                        $remaining_amount = 0;
                        $delivery_charge_value = (float) ($order->delivery_charge);
                        if ($delivery_charge_value <= 0 && !$is_free_delivery_eligible && !$is_free_delivery_coupon) {
                            \Log::warning('Invalid delivery charge for WALLET payment', [
                                'order_id' => $order->id,
                                'actual_delivery_charge' => $order->actual_delivery_charge,
                                'delivery_charge' => $order->delivery_charge
                            ]);
                        }
                    } else {
                        $remaining_amount = $adjusted_total - $order->wallet_amount;
                    }
                    \Log::info('WALLET payment details in acceptToDeliver', [
                        'order_id' => $order->id,
                        'wallet_amount' => $order->wallet_amount,
                        'adjusted_total' => $adjusted_total,
                        'remaining_amount' => $remaining_amount,
                        'delivery_charge' => $adjusted_delivery_charge
                    ]);
                } else {
                    $is_wallet_payment = false;
                    $remaining_amount = $adjusted_total;
                    \Log::info('Non-WALLET payment in acceptToDeliver', [
                        'order_id' => $order->id,
                        'payment_mode' => $order->actual_payment_mode,
                        'remaining_amount' => $remaining_amount,
                        'delivery_charge' => $adjusted_delivery_charge
                    ]);
                }
    
                // حساب العمولة
                $commission = 0;
                $min_order_price = (float) ($order->restaurant->min_order_price ?? 0);
                $is_below_min_order = $order->sub_total < $min_order_price;
                $delivery_charge = (float) ($order->delivery_charge);
    
                if ($is_free_delivery_eligible) {
                    $free_delivery_cost = (float) ($order->restaurant->free_delivery_cost ?? $delivery_charge);
                    $free_delivery_comm = (float) ($order->restaurant->free_delivery_comm ?? $deliveryGuyCommissionRate);
                    $commission = $free_delivery_cost * ($free_delivery_comm / 100);
                    \Log::info('Driver commission calculated using free_delivery_cost', [
                        'order_id' => $order->id,
                        'adjusted_total' => $adjusted_total,
                        'free_delivery_subtotal' => $free_delivery_subtotal,
                        'free_delivery_distance' => $free_delivery_distance,
                        'delivery_distance' => $delivery_distance,
                        'free_delivery_cost' => $free_delivery_cost,
                        'free_delivery_comm' => $free_delivery_comm,
                        'commission' => $commission
                    ]);
                } elseif ($is_below_min_order) {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                        $commission = 0;
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0));
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                }
                            }
                        }
                    }
                    \Log::info('Driver commission calculated for below minimum order without free delivery', [
                        'order_id' => $order->id,
                        'adjusted_total' => $adjusted_total,
                        'min_order_price' => $min_order_price,
                        'commission' => $commission
                    ]);
                } else {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                        $commission = 0;
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0));
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                }
                            }
                        }
                    }
                    \Log::info('Driver commission calculated for regular order', [
                        'order_id' => $order->id,
                        'delivery_charge' => $delivery_charge,
                        'commission_rate' => $deliveryGuyCommissionRate,
                        'commission' => $commission
                    ]);
                }
    
                // حساب عمولة البقشيش
                $tip_amount = 0;
                if ($deliveryUser->delivery_guy_detail && $deliveryUser->delivery_guy_detail->tip_commission_rate) {
                    $tip_amount = ($deliveryUser->delivery_guy_detail->tip_commission_rate / 100) * ($order->tip_amount ?? 0);
                    $tip_amount = number_format((float) $tip_amount, 2, '.', '');
                    $tip_amount = -$tip_amount; // جعل عمولة البقشيش سالبة
                }
    
                $checkOrder = AcceptDelivery::where('order_id', $order->id)->first();
    
                if (!$checkOrder) {
                    $nonCompleteOrders = AcceptDelivery::where('user_id', $deliveryUser->id)->where('is_complete', 0)->with('order')->get();
                    $countNonCompleteOrders = 0;
                    if ($nonCompleteOrders) {
                        foreach ($nonCompleteOrders as $nonCompleteOrder) {
                            if ($nonCompleteOrder->order && $nonCompleteOrder->order->orderstatus_id != 6) {
                                $countNonCompleteOrders++;
                            }
                        }
                    }
    
                    if ($countNonCompleteOrders < $max_accept_delivery_limit) {
                        try {
                            $order->orderstatus_id = '3';
                            $order->save();
    
                            $acceptDelivery = new AcceptDelivery();
                            $acceptDelivery->order_id = $order->id;
                            $acceptDelivery->user_id = $deliveryUser->id;
                            $acceptDelivery->customer_id = $order->user->id;
                            $acceptDelivery->save();
    
                            $singleOrder = $order;
                            if (config('setting.enablePushNotificationOrders') == 'true') {
                                $notify = new PushNotify();
                                $notify->sendPushNotification('3', $order->user_id, $order->unique_order_id);
                            }
                        } catch (Illuminate\Database\QueryException $e) {
                            \Log::error('Error accepting delivery: ' . $e->getMessage());
                            $errorCode = $e->errorInfo[1];
                            if ($errorCode == 1062) {
                                $singleOrder->already_accepted = true;
                            }
                        }
    
                        $singleOrder->commission = number_format((float) $commission, 2, '.', '');
                        $singleOrder->tip_amount = $tip_amount;
                        $singleOrder->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                        $singleOrder->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                        $singleOrder->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                        $singleOrder->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                        $singleOrder->is_wallet_payment = $is_wallet_payment;
                        $singleOrder->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
                        $singleOrder->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');
                        $singleOrder->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $order->restaurant->free_delivery_cost : 0), 2, '.', '');
                        $singleOrder->actual_payment_mode = $order->actual_payment_mode;
                        $singleOrder->sub_total = number_format((float) $order->sub_total, 2, '.', '');
    
                        \Log::info('Driver commission calculated', [
                            'order_id' => $order->id,
                            'payment_mode' => $order->actual_payment_mode,
                            'wallet_amount' => $order->wallet_amount,
                            'total' => $order->total,
                            'adjusted_total' => $adjusted_total,
                            'delivery_charge' => $singleOrder->delivery_charge,
                            'discounted_delivery_charge' => $singleOrder->discounted_delivery_charge,
                            'actual_delivery_charge' => $order->actual_delivery_charge,
                            'is_free_delivery' => $order->is_free_delivery,
                            'is_free_delivery_coupon' => $is_free_delivery_coupon,
                            'commission' => $commission,
                            'commission_rate' => $deliveryGuyCommissionRate,
                            'remaining_amount' => $remaining_amount,
                            'coupon_amount' => $coupon_amount,
                            'coupon_delivery_discount' => $singleOrder->coupon_delivery_discount,
                            'delivery_distance' => $order->distance,
                            'free_delivery_distance' => $order->restaurant->free_delivery_distance ?? 0,
                            'free_delivery_cost' => $singleOrder->free_delivery_cost
                        ]);
    
                        activity()
                            ->performedOn($order)
                            ->causedBy($deliveryUser)
                            ->withProperties(['type' => 'Delivery_Accepted'])->log('Delivery accepted');
    
                        if (config('setting.iHaveFoodomaaDeliveryApp') == "true") {
                            if (config('setting.hasSocketPush') == 'true') {
                                stopPlayingNotificationSoundDeliveryAppHelper($order);
                            } else {
                                $notify = new PushNotify();
                                $notify->stopPlayingNotificationSoundDeliveryApp($order->unique_order_id);
                            }
                        }
    
                        return response()->json($singleOrder);
                    } else {
                        $singleOrder = $order;
                        $singleOrder->max_order = true;
                        $singleOrder->commission = number_format((float) $commission, 2, '.', '');
                        $singleOrder->tip_amount = $tip_amount;
                        $singleOrder->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                        $singleOrder->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                        $singleOrder->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                        $singleOrder->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                        $singleOrder->is_wallet_payment = $is_wallet_payment;
                        $singleOrder->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
                        $singleOrder->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');
                        $singleOrder->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $order->restaurant->free_delivery_cost : 0), 2, '.', '');
                        $singleOrder->actual_payment_mode = $order->actual_payment_mode;
                        $singleOrder->sub_total = number_format((float) $order->sub_total, 2, '.', '');
                        return response()->json($singleOrder);
                    }
                } else {
                    $singleOrder = $order;
                    $singleOrder->already_accepted = true;
                    $singleOrder->commission = number_format((float) $commission, 2, '.', '');
                    $singleOrder->tip_amount = $tip_amount;
                    $singleOrder->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                    $singleOrder->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                    $singleOrder->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                    $singleOrder->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                    $singleOrder->is_wallet_payment = $is_wallet_payment;
                    $singleOrder->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
                    $singleOrder->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');
                    $singleOrder->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $order->restaurant->free_delivery_cost : 0), 2, '.', '');
                    $singleOrder->actual_payment_mode = $order->actual_payment_mode;
                    $singleOrder->sub_total = number_format((float) $order->sub_total, 2, '.', '');
                    return response()->json($singleOrder);
                }
            } else {
                \Log::error('Unauthorized or invalid order for accept to deliver', [
                    'order_id' => $request->order_id,
                    'driver_id' => $deliveryUser->id
                ]);
                abort(401, 'Order cancelled or not found or cannot view order.');
            }
        }
    }
          

    /**
     * @param Request $request
     */

    public function pickedupOrder(Request $request)
    {
        $deliveryUser = auth()->user();
    
        \Log::info('Starting pickedupOrder', [
            'order_id' => $request->order_id,
            'driver_id' => $deliveryUser->id,
            'driver_balance' => $deliveryUser->balanceFloat,
            'timestamp' => now()->toDateTimeString()
        ]);
    
        if ($deliveryUser->hasRole('Delivery Guy')) {
            $order = Order::where('id', $request->order_id)
                ->with(['restaurant' => function ($query) {
                    $query->select('id', 'name', 'description', 'address', 'pincode', 'latitude', 'longitude', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm', 'free_delivery_distance');
                }])
                ->with(['orderitems' => function ($query) {
                    $query->with(['order_item_addons' => function ($subQuery) {
                        $subQuery->select('id', 'orderitem_id', 'addon_name', 'addon_price');
                    }])->select('id', 'order_id', 'name', 'price', 'quantity', 'item_id');
                }])
                ->with(['user' => function ($query) {
                    $query->select('id', 'name', 'phone', 'referred_by');
                }])
                ->first();
    
            if ($order && $this->canPerformAction($deliveryUser, $order)) {
                \Log::info('Order details in pickedupOrder', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'sub_total' => $order->sub_total,
                    'total' => $order->total,
                    'delivery_charge' => $order->delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'coupon_name' => $order->coupon_name,
                    'coupon_amount' => $order->coupon_amount,
                    'payment_mode' => $order->actual_payment_mode,
                    'wallet_amount' => $order->wallet_amount,
                    'customer_balance' => $order->user->balanceFloat,
                    'distance' => $order->distance,
                    'restaurant_id' => $order->restaurant_id,
                    'has_restaurant' => !is_null($order->restaurant)
                ]);
    
                $deliveryGuyCommissionRate = $deliveryUser->delivery_guy_detail->commission_rate;
    
                // استخدام sub_total
                $adjusted_total = (float) $order->sub_total;
                $adjusted_delivery_charge = (float) ($order->delivery_charge ?? 0);
                $discounted_delivery_charge = $adjusted_delivery_charge;
                $remaining_amount = 0;
                $is_wallet_payment = false;
                $coupon_amount = 0;
                $coupon_delivery_discount = 0;
                $is_free_delivery_eligible = false;
                $is_free_delivery_coupon = false;
    
                // تحديث actual_delivery_charge إذا كان مختلفًا عن delivery_charge
                if (!$order->actual_delivery_charge || $order->delivery_charge != $order->actual_delivery_charge) {
                    $order->actual_delivery_charge = $order->delivery_charge;
                    \Log::info("Updated actual_delivery_charge to match delivery_charge in pickedupOrder", [
                        "order_id" => $order->id,
                        "delivery_charge" => $order->delivery_charge,
                        "actual_delivery_charge" => $order->actual_delivery_charge
                    ]);
                }
    
                \Log::info("Delivery charge details in pickedupOrder", [
                    "order_id" => $order->id,
                    "delivery_charge" => $order->delivery_charge,
                    "actual_delivery_charge" => $order->actual_delivery_charge
                ]);
    
               
                $free_delivery_subtotal = (float) ($order->restaurant->free_delivery_subtotal ?? 0);
                $free_delivery_distance = (float) ($order->restaurant->free_delivery_distance ?? 0);
                $delivery_distance = (float) ($order->distance ?? 0);
                if ($free_delivery_subtotal > 0 && $order->sub_total >= $free_delivery_subtotal &&
                    ($free_delivery_distance == 0 || $delivery_distance <= $free_delivery_distance)) {
                    $is_free_delivery_eligible = true;
                    $adjusted_delivery_charge = 0;
                    $discounted_delivery_charge = 0;
                    $order->delivery_charge = 0;
                    $order->is_free_delivery = true;
                }
    
               
                if ($order->coupon_name) {
                    $coupon = Coupon::where('code', $order->coupon_name)->first();
                    if ($coupon) {
                        \Log::info('Coupon found in pickedupOrder', [
                            'order_id' => $order->id,
                            'coupon_code' => $coupon->code,
                            'is_used_for_delivery' => $coupon->is_used_for_delivery,
                            'amount' => $coupon->amount,
                            'delivery_discount_percentage' => $coupon->delivery_discount_percentage
                        ]);
    
                        if ($coupon->is_used_for_delivery && !$is_free_delivery_eligible) {
                            $original_delivery_charge = (float) ($order->delivery_charge); // Use updated delivery_charge
                            if ($coupon->delivery_discount_percentage) {
                                $discount_factor = $coupon->delivery_discount_percentage / 100;
                                $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                                $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                            } else {
                                $coupon_delivery_discount = (float) ($coupon->amount ?? $order->coupon_amount);
                                $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                            }
                            $adjusted_delivery_charge = $coupon->discount_type === 'FREE' || $coupon->delivery_discount_percentage == 100 ? 0 : $discounted_delivery_charge;
                            $order->delivery_charge = $adjusted_delivery_charge;
                            $is_free_delivery_coupon = $adjusted_delivery_charge == 0;
                            $order->actual_delivery_charge = $original_delivery_charge;
                            $coupon_amount = $coupon_delivery_discount;
                        } else {
                            if ($coupon->discount_type == 'PERCENTAGE') {
                                $coupon_amount = ($coupon->discount / 100) * $order->sub_total;
                                if ($coupon->max_discount && $coupon_amount > $coupon->max_discount) {
                                    $coupon_amount = (float) $coupon->max_discount;
                                }
                            } else {
                                $coupon_amount = (float) ($coupon->amount ?? $order->coupon_amount);
                            }
                            $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                        }
                    } else {
                        $coupon_amount = (float) ($order->coupon_amount ?? 0);
                        if ($order->is_free_delivery) {
                            $original_delivery_charge = (float) ($order->delivery_charge);
                            $coupon_delivery_discount = $original_delivery_charge;
                            $discounted_delivery_charge = 0;
                            $adjusted_delivery_charge = 0;
                            $order->delivery_charge = $adjusted_delivery_charge;
                            $order->actual_delivery_charge = $original_delivery_charge;
                            $is_free_delivery_coupon = true;
                        } else {
                            $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                        }
                    }
                    $order->coupon_amount = $coupon_amount;
                    \Log::info('Coupon processing result in pickedupOrder', [
                        'order_id' => $order->id,
                        'coupon_amount' => $coupon_amount,
                        'coupon_delivery_discount' => $coupon_delivery_discount,
                        'adjusted_total' => $adjusted_total,
                        'adjusted_delivery_charge' => $adjusted_delivery_charge,
                        'discounted_delivery_charge' => $discounted_delivery_charge,
                        'is_free_delivery_coupon' => $is_free_delivery_coupon
                    ]);
                }
    
                // معالجة الدفع عبر المحفظة
                if ($order->actual_payment_mode == 'WALLET') {
                    $is_wallet_payment = true;
                    if ($order->wallet_amount >= $adjusted_total) {
                        $remaining_amount = 0;
                        $delivery_charge_value = (float) ($order->delivery_charge);
                        if ($delivery_charge_value <= 0 && !$is_free_delivery_eligible && !$is_free_delivery_coupon) {
                            \Log::warning('Invalid delivery charge for WALLET payment', [
                                'order_id' => $order->id,
                                'actual_delivery_charge' => $order->actual_delivery_charge,
                                'delivery_charge' => $order->delivery_charge
                            ]);
                        }
                    } else {
                        $remaining_amount = $adjusted_total - $order->wallet_amount;
                    }
                    \Log::info('WALLET payment details in pickedupOrder', [
                        'order_id' => $order->id,
                        'wallet_amount' => $order->wallet_amount,
                        'adjusted_total' => $adjusted_total,
                        'remaining_amount' => $remaining_amount,
                        'delivery_charge' => $adjusted_delivery_charge
                    ]);
                } else {
                    $is_wallet_payment = false;
                    $remaining_amount = $adjusted_total;
                    \Log::info('Non-WALLET payment in pickedupOrder', [
                        'order_id' => $order->id,
                        'payment_mode' => $order->actual_payment_mode,
                        'remaining_amount' => $remaining_amount,
                        'delivery_charge' => $adjusted_delivery_charge
                    ]);
                }
    
                // حساب العمولة
                $commission = 0;
                $min_order_price = (float) ($order->restaurant->min_order_price ?? 0);
                $is_below_min_order = $order->sub_total < $min_order_price;
                $delivery_charge = (float) ($order->delivery_charge);
    
                if ($is_free_delivery_eligible) {
                    $free_delivery_cost = (float) ($order->restaurant->free_delivery_cost ?? $delivery_charge);
                    $free_delivery_comm = (float) ($order->restaurant->free_delivery_comm ?? $deliveryGuyCommissionRate);
                    $commission = $free_delivery_cost * ($free_delivery_comm / 100);
                    \Log::info('Driver commission calculated using free_delivery_cost in pickedupOrder', [
                        'order_id' => $order->id,
                        'adjusted_total' => $adjusted_total,
                        'free_delivery_subtotal' => $free_delivery_subtotal,
                        'free_delivery_distance' => $free_delivery_distance,
                        'delivery_distance' => $delivery_distance,
                        'free_delivery_cost' => $free_delivery_cost,
                        'free_delivery_comm' => $free_delivery_comm,
                        'commission' => $commission
                    ]);
                } elseif ($is_below_min_order) {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                        $commission = 0;
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0));
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                }
                            }
                        }
                    }
                    \Log::info('Driver commission calculated for below minimum order without free delivery in pickedupOrder', [
                        'order_id' => $order->id,
                        'adjusted_total' => $adjusted_total,
                        'min_order_price' => $min_order_price,
                        'commission' => $commission
                    ]);
                } else {
                    if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $adjusted_total) {
                        $commission = 0;
                    } else {
                        if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                            $commission = $deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0));
                        } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                            $commission = $deliveryGuyCommissionRate / 100 * $delivery_charge;
                            if ($order->coupon_name) {
                                $coupon = Coupon::where('code', $order->coupon_name)->first();
                                if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === 'FREE') {
                                    $commission = $delivery_charge;
                                }
                            }
                        }
                    }
                    \Log::info('Driver commission calculated for regular order in pickedupOrder', [
                        'order_id' => $order->id,
                        'delivery_charge' => $delivery_charge,
                        'commission_rate' => $deliveryGuyCommissionRate,
                        'commission' => $commission
                    ]);
                }
    
            
                $tip_amount = 0;
                if ($deliveryUser->delivery_guy_detail && $deliveryUser->delivery_guy_detail->tip_commission_rate) {
                    $tip_amount = ($deliveryUser->delivery_guy_detail->tip_commission_rate / 100) * ($order->tip_amount ?? 0);
                    $tip_amount = number_format((float) $tip_amount, 2, '.', '');
                    $tip_amount = -$tip_amount; 
                }
    
               
                $order->orderstatus_id = '4';
                $order->save();
                \Log::info('Order status updated to Picked-up', [
                    'order_id' => $order->id,
                    'orderstatus_id' => $order->orderstatus_id,
                    'customer_balance' => $order->user->balanceFloat
                ]);
    
                // جلب بيانات الطلب المحدثة
                $singleOrder = Order::where('id', $order->id)
                    ->with(['restaurant' => function ($query) {
                        $query->select('id', 'name', 'description', 'address', 'pincode', 'latitude', 'longitude', 'min_order_price', 'free_delivery_subtotal', 'free_delivery_cost', 'free_delivery_comm', 'free_delivery_distance');
                    }])
                    ->with(['orderitems' => function ($query) {
                        $query->with(['order_item_addons' => function ($subQuery) {
                            $subQuery->select('id', 'orderitem_id', 'addon_name', 'addon_price');
                        }])->select('id', 'order_id', 'name', 'price', 'quantity', 'item_id');
                    }])
                    ->with(['user' => function ($query) {
                        $query->select('id', 'name', 'phone', 'referred_by');
                    }])
                    ->first();
    
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('4', $order->user_id, $order->unique_order_id);
                    \Log::info('Push notification sent for Picked-up', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id
                    ]);
                }
    
                // إعداد استجابة JSON
                $singleOrderData = (object) [];
                $singleOrderData->commission = number_format((float) $commission, 2, '.', '');
                $singleOrderData->tip_amount = $tip_amount;
                $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                $singleOrderData->is_wallet_payment = $is_wallet_payment;
                $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');
                $singleOrderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, '.', '');
                $singleOrderData->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $order->restaurant->free_delivery_cost : 0), 2, '.', '');
    
                $singleOrder->setAppends([]);
                $singleOrderArray = $singleOrder->toArray();
                $singleOrderArray['delivery_charge'] = $singleOrderData->delivery_charge;
                $singleOrderArray['discounted_delivery_charge'] = $singleOrderData->discounted_delivery_charge;
                $singleOrderArray['coupon_delivery_discount'] = $singleOrderData->coupon_delivery_discount;
                $singleOrderArray['sub_total'] = number_format((float) $order->sub_total, 2, '.', '');
                $singleOrderArray['free_delivery_cost'] = $singleOrderData->free_delivery_cost;
                $singleOrderArray['delivery_distance'] = number_format((float) $delivery_distance, 2, '.', '');
                $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);
    
                activity()
                    ->performedOn($order)
                    ->causedBy($deliveryUser)
                    ->withProperties(['type' => 'Order_Pickedup'])
                    ->log('Order picked-up');
                \Log::info('Activity logged: Order picked-up', [
                    'order_id' => $order->id,
                    'driver_id' => $deliveryUser->id
                ]);
    
                \Log::info('Picked Up Order Response', [
                    'order_id' => $order->id,
                    'actual_payment_mode' => $order->actual_payment_mode,
                    'wallet_amount' => $order->wallet_amount,
                    'total' => $order->total,
                    'adjusted_total' => $adjusted_total,
                    'delivery_charge' => $singleOrderArray['delivery_charge'],
                    'discounted_delivery_charge' => $singleOrderArray['discounted_delivery_charge'],
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'commission' => $commission,
                    'commission_rate' => $deliveryGuyCommissionRate,
                    'remaining_amount' => $remaining_amount,
                    'is_wallet_payment' => $is_wallet_payment,
                    'coupon_amount' => $coupon_amount,
                    'coupon_delivery_discount' => $singleOrderArray['coupon_delivery_discount'],
                    'orderitems_count' => count($singleOrderArray['orderitems'] ?? []),
                    'orderitems' => $singleOrderArray['orderitems'] ?? [],
                    'customer_balance' => $order->user->balanceFloat,
                    'delivery_distance' => $order->distance,
                    'free_delivery_distance' => $order->restaurant->free_delivery_distance ?? 0,
                    'free_delivery_cost' => $singleOrderArray['free_delivery_cost']
                ]);
    
                return response()->json($singleOrderArray);
            } else {
                \Log::error('Unauthorized or invalid order for picked-up', [
                    'order_id' => $request->order_id,
                    'driver_id' => $deliveryUser->id
                ]);
                abort(401, 'Order cancelled or not found or cannot view order.');
            }
        }
    }
        
    // extend by aya
    
    
    // ===========

      public function deliverOrder(Request $request, TranslationHelper $translationHelper)
{
    $keys = ["deliveryCommissionMessage", "deliveryTipTransactionMessage"];
    $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

    $deliveryUser = auth()->user();

    \Log::info("Starting deliverOrder", [
        "order_id" => $request->order_id,
        "driver_id" => $deliveryUser->id,
        "driver_balance" => $deliveryUser->balanceFloat ?? 'N/A',
        "timestamp" => now()->toDateTimeString(),
        "delivery_pin" => $request->delivery_pin
    ]);

    if (!$deliveryUser->hasRole("Delivery Guy")) {
        \Log::error("Unauthorized access to deliverOrder", ["user_id" => $deliveryUser->id]);
        abort(403, "Unauthorized action.");
    }

    DB::beginTransaction();
    try {
        $order = Order::where("id", $request->order_id)
            ->with(["restaurant" => function ($query) {
                $query->select("id", "name", "description", "address", "pincode", "latitude", "longitude", "commission_rate", "zone_id",
                    "free_delivery_subtotal", "free_delivery_cost", "free_delivery_comm", "free_delivery_distance",
                    "base_delivery_charge", "base_delivery_distance", "extra_delivery_charge", "extra_delivery_distance");
            }])
            ->with(["orderitems" => function ($query) {
                $query->with(["order_item_addons" => function ($subQuery) {
                    $subQuery->select("id", "orderitem_id", "addon_name", "addon_price");
                }])->select("id", "order_id", "name", "price", "quantity", "item_id");
            }])
            ->with(["user" => function ($query) {
                $query->select("id", "name", "phone", "email", "zone_id", "referred_by");
            }])
            ->lockForUpdate()
            ->first();

        if (!$order) {
            \Log::error("Order not found", ["order_id" => $request->order_id, "driver_id" => $deliveryUser->id]);
            DB::rollBack();
            abort(404, "Order not found.");
        }

        // فحص إضافي: تأكد من وجود user و restaurant
        if (!$order->user || !$order->restaurant) {
            \Log::error("Order missing user or restaurant", ["order_id" => $order->id]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Order missing required data (user or restaurant)."
            ], 400);
        }

        if ($order->orderstatus_id == 5) {
            \Log::warning("Order already delivered", ["order_id" => $order->id, "driver_id" => $deliveryUser->id]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Order already delivered."
            ], 400);
        }

        if ($order->orderstatus_id != 4) {
            \Log::error("Order not in Picked Up status", ["order_id" => $order->id, "orderstatus_id" => $order->orderstatus_id]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Order must be in Picked Up status to deliver."
            ], 400);
        }

        if (count($order->orderitems) == 0) {
            \Log::error("Order has no items", [
                "order_id" => $order->id,
                "driver_id" => $deliveryUser->id
            ]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Order has no items."
            ], 400);
        }

        \Log::info("Order details in deliverOrder", [
            "order_id" => $order->id,
            "user_id" => $order->user_id,
            "sub_total" => $order->sub_total,
            "total" => $order->total,
            "delivery_charge" => $order->delivery_charge,
            "actual_delivery_charge" => $order->actual_delivery_charge,
            "coupon_name" => $order->coupon_name,
            "coupon_amount" => $order->coupon_amount,
            "payment_mode" => $order->actual_payment_mode,
            "wallet_amount" => $order->wallet_amount,
            "customer_balance" => $order->user ? $order->user->balanceFloat : null,
            "restaurant_free_delivery_subtotal" => $order->restaurant->free_delivery_subtotal ?? 0,
            "restaurant_free_delivery_cost" => $order->restaurant->free_delivery_cost ?? 0,
            "restaurant_free_delivery_comm" => $order->restaurant->free_delivery_comm ?? 0,
            "restaurant_free_delivery_distance" => $order->restaurant->free_delivery_distance ?? 0,
            "distance" => $order->distance ?? 0,
            "base_delivery_charge" => $order->restaurant->base_delivery_charge ?? 14000,
            "base_delivery_distance" => $order->restaurant->base_delivery_distance ?? 4,
            "extra_delivery_charge" => $order->restaurant->extra_delivery_charge ?? 4000,
            "extra_delivery_distance" => $order->restaurant->extra_delivery_distance ?? 1
        ]);

        $adjusted_total = (float) $order->sub_total;
        $adjusted_delivery_charge = (float) ($order->delivery_charge ?? 0);
        $discounted_delivery_charge = $adjusted_delivery_charge;
        $remaining_amount = 0;
        $is_wallet_payment = false;
        $coupon_amount = 0;
        $coupon_delivery_discount = 0;
        $is_free_delivery_eligible = false;
        $is_free_delivery_coupon = false;
        $original_delivery_charge_for_cod = (float) ($order->actual_delivery_charge ?? $order->delivery_charge ?? 0);
        $coupon_discount_for_company = 0;

        // Sync delivery_charge with actual_delivery_charge
        if ($order->delivery_charge != $order->actual_delivery_charge) {
            $order->actual_delivery_charge = $order->delivery_charge;
            \Log::info("Synced actual_delivery_charge with updated delivery_charge", [
                "order_id" => $order->id,
                "delivery_charge" => $order->delivery_charge,
                "actual_delivery_charge" => $order->actual_delivery_charge
            ]);
        }

        // Fallback delivery charge calculation based on distance
        $distance = (float) ($order->distance ?? 0);
        $base_delivery_charge = (float) ($order->restaurant->base_delivery_charge ?? 14000);
        $base_delivery_distance = (float) ($order->restaurant->base_delivery_distance ?? 4);
        $extra_delivery_charge = (float) ($order->restaurant->extra_delivery_charge ?? 4000);
        $extra_delivery_distance = (float) ($order->restaurant->extra_delivery_distance ?? 1);

        if ($adjusted_delivery_charge == 0 && !$order->is_free_delivery) {
            $calculated_delivery_charge = $base_delivery_charge;
            if ($distance > $base_delivery_distance) {
                $extra_distance = $distance - $base_delivery_distance;
                $extra_units = ceil($extra_distance / $extra_delivery_distance);
                $calculated_delivery_charge += $extra_units * $extra_delivery_charge;
            }
            $adjusted_delivery_charge = $calculated_delivery_charge;
            $order->delivery_charge = $calculated_delivery_charge;
            $order->actual_delivery_charge = $calculated_delivery_charge;
            $original_delivery_charge_for_cod = $calculated_delivery_charge;
            \Log::info("Calculated delivery_charge based on distance as fallback", [
                "order_id" => $order->id,
                "calculated_delivery_charge" => $calculated_delivery_charge
            ]);
        }

        // Check for free delivery eligibility
     // Check for free delivery eligibility
$free_delivery_subtotal = (float) ($order->restaurant->free_delivery_subtotal ?? 120000);
$free_delivery_distance = (float) ($order->restaurant->free_delivery_distance ?? 5);
if ($order->is_free_delivery && // احترام قيمة is_free_delivery
    $free_delivery_subtotal > 0 && $order->sub_total >= $free_delivery_subtotal &&
    ($free_delivery_distance == 0 || $distance <= $free_delivery_distance)) {
    $is_free_delivery_eligible = true;
    $adjusted_delivery_charge = 0;
    $discounted_delivery_charge = 0;
    $order->delivery_charge = 0;
    $order->is_free_delivery = true;
}

        // Process coupon
        if ($order->coupon_name) {
            $coupon = Coupon::where("code", $order->coupon_name)->first();
            if ($coupon) {
                \Log::info("Coupon found in deliverOrder", [
                    "order_id" => $order->id,
                    "coupon_code" => $coupon->code,
                    "is_used_for_delivery" => $coupon->is_used_for_delivery,
                    "amount" => $coupon->amount,
                    "discount_type" => $coupon->discount_type,
                    "discount" => $coupon->discount,
                    "max_discount" => $coupon->max_discount,
                    "delivery_discount_percentage" => $coupon->delivery_discount_percentage
                ]);
                if ($coupon->is_used_for_delivery && !$is_free_delivery_eligible) {
                    $original_delivery_charge = (float) ($order->actual_delivery_charge);
                    if ($coupon->delivery_discount_percentage) {
                        $discount_factor = $coupon->delivery_discount_percentage / 100;
                        $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                        $coupon_delivery_discount = $original_delivery_charge - $discounted_delivery_charge;
                    } else {
                        $coupon_delivery_discount = (float) ($coupon->amount ?? $order->coupon_amount);
                        $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_delivery_discount);
                    }
                    $adjusted_delivery_charge = $coupon->discount_type === "FREE" || $coupon->delivery_discount_percentage == 100 ? 0 : $discounted_delivery_charge;
                    $order->delivery_charge = $adjusted_delivery_charge;
                    $is_free_delivery_coupon = $adjusted_delivery_charge == 0;
                    $order->actual_delivery_charge = $original_delivery_charge;
                    $coupon_amount = $coupon_delivery_discount;
                    $coupon_discount_for_company = $coupon_delivery_discount;
                    if ($coupon->discount_type === "FREE" && $order->actual_payment_mode === "COD") {
                        $original_delivery_charge_for_cod = $original_delivery_charge;
                    }
                } else {
                    if ($coupon->discount_type == 'PERCENTAGE') {
                        $coupon_amount = ($coupon->discount / 100) * $order->sub_total;
                        if ($coupon->max_discount && $coupon_amount > $coupon->max_discount) {
                            $coupon_amount = (float) $coupon->max_discount;
                        }
                    } else {
                        $coupon_amount = (float) ($coupon->amount ?? $order->coupon_amount);
                    }
                    $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                    $coupon_discount_for_company = $coupon_amount;
                }
                $order->coupon_amount = $coupon_amount;
                \Log::info("Coupon processing result in deliverOrder", [
                    "order_id" => $order->id,
                    "coupon_amount" => $coupon_amount,
                    "coupon_delivery_discount" => $coupon_delivery_discount,
                    "adjusted_total" => $adjusted_total,
                    "adjusted_delivery_charge" => $adjusted_delivery_charge,
                    "discounted_delivery_charge" => $discounted_delivery_charge,
                    "is_free_delivery_eligible" => $is_free_delivery_eligible,
                    "is_free_delivery_coupon" => $is_free_delivery_coupon,
                    "original_delivery_charge_for_cod" => $original_delivery_charge_for_cod,
                    "coupon_discount_for_company" => $coupon_discount_for_company
                ]);
            } else {
                $coupon_amount = (float) ($order->coupon_amount ?? 0);
                if ($order->is_free_delivery) {
                    $original_delivery_charge = (float) ($order->actual_delivery_charge);
                    $coupon_delivery_discount = $original_delivery_charge;
                    $discounted_delivery_charge = 0;
                    $adjusted_delivery_charge = 0;
                    $order->delivery_charge = $adjusted_delivery_charge;
                    $order->actual_delivery_charge = $original_delivery_charge;
                    $is_free_delivery_coupon = true;
                    $coupon_amount = $coupon_delivery_discount;
                    $coupon_discount_for_company = $coupon_delivery_discount;
                    if ($order->actual_payment_mode === "COD") {
                        $original_delivery_charge_for_cod = $original_delivery_charge;
                    }
                } else {
                    $adjusted_total = max(0, $order->sub_total - $coupon_amount);
                    $coupon_discount_for_company = $coupon_amount;
                }
                $order->coupon_amount = $coupon_amount;
            }
        }

        // Calculate total paid amount
        $total_paid = $adjusted_total + $adjusted_delivery_charge;

        if ($adjusted_total < 0 && !$order->is_free_delivery) {
            \Log::error("Invalid adjusted total after coupon", [
                "order_id" => $order->id,
                "adjusted_total" => $adjusted_total
            ]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Invalid adjusted total after coupon."
            ], 400);
        }

        $payment_type = null;
        if ($order->actual_payment_mode == "WALLET" && $order->wallet_amount >= $total_paid) {
            $is_wallet_payment = true;
            $remaining_amount = 0;
            $payment_type = "WALLET";
            $delivery_charge_value = (float) ($order->delivery_charge);
            $adjusted_delivery_charge = $delivery_charge_value <= 0 ? 0 : $delivery_charge_value;
            $discounted_delivery_charge = $order->is_free_delivery ? 0 : $adjusted_delivery_charge;
            \Log::info("WALLET payment details", [
                "order_id" => $order->id,
                "wallet_amount" => $order->wallet_amount,
                "adjusted_total" => $adjusted_total,
                "total_paid" => $total_paid,
                "remaining_amount" => $remaining_amount,
                "delivery_charge" => $adjusted_delivery_charge,
                "discounted_delivery_charge" => $discounted_delivery_charge,
                "actual_delivery_charge" => $order->actual_delivery_charge
            ]);
        } elseif ($order->actual_payment_mode == "WALLET" && $order->wallet_amount < $total_paid && $order->wallet_amount > 0) {
            $is_wallet_payment = true;
            $remaining_amount = $total_paid - $order->wallet_amount;
            $payment_type = "PARTIAL";
            $delivery_charge_value = (float) ($order->delivery_charge);
            $adjusted_delivery_charge = $delivery_charge_value <= 0 ? 0 : $delivery_charge_value;
            $discounted_delivery_charge = $adjusted_delivery_charge;
            \Log::info("PARTIAL payment details", [
                "order_id" => $order->id,
                "wallet_amount" => $order->wallet_amount,
                "adjusted_total" => $adjusted_total,
                "total_paid" => $total_paid,
                "remaining_amount" => $remaining_amount,
                "delivery_charge" => $adjusted_delivery_charge,
                "actual_delivery_charge" => $order->actual_delivery_charge
            ]);
        } elseif ($order->actual_payment_mode == "PARTIAL" || ($order->actual_payment_mode == "COD" && $order->wallet_amount > 0)) {
            $is_wallet_payment = true;
            $remaining_amount = $total_paid - $order->wallet_amount;
            $payment_type = "PARTIAL";
            $delivery_charge_value = (float) ($order->delivery_charge);
            $adjusted_delivery_charge = $delivery_charge_value <= 0 ? 0 : $delivery_charge_value;
            $discounted_delivery_charge = $adjusted_delivery_charge;
            \Log::info("PARTIAL payment details (COD with wallet)", [
                "order_id" => $order->id,
                "wallet_amount" => $order->wallet_amount,
                "adjusted_total" => $adjusted_total,
                "total_paid" => $total_paid,
                "remaining_amount" => $remaining_amount,
                "delivery_charge" => $adjusted_delivery_charge,
                "actual_delivery_charge" => $order->actual_delivery_charge
            ]);
        } else {
            $is_wallet_payment = false;
            $remaining_amount = $total_paid;
            $payment_type = "COD";
            $delivery_charge_value = (float) ($order->delivery_charge);
            $adjusted_delivery_charge = $delivery_charge_value <= 0 ? 0 : $delivery_charge_value;
            $discounted_delivery_charge = $adjusted_delivery_charge;
            \Log::info("COD payment details", [
                "order_id" => $order->id,
                "payment_mode" => $order->actual_payment_mode,
                "remaining_amount" => $remaining_amount,
                "delivery_charge" => $adjusted_delivery_charge
            ]);
        }

        if ($order && $this->canPerformAction($deliveryUser, $order)) {
            $deliveryGuyCommissionRate = (float) ($deliveryUser->delivery_guy_detail->commission_rate ?? 10);
            $order->driver_order_commission_rate = $deliveryGuyCommissionRate;

            $deliveryUserRecord = AcceptDelivery::where("order_id", $order->id)->first();
            \Log::info("Delivery user record lookup", [
                "order_id" => $order->id,
                "delivery_user_record_exists" => !is_null($deliveryUserRecord),
                "driver_id" => $deliveryUserRecord ? $deliveryUserRecord->user_id : null
            ]);

            // فحص إضافي: تأكد من وجود deliveryUserRecord وتفاصيل السائق
            if (!$deliveryUserRecord || !$deliveryUserRecord->user || !$deliveryUserRecord->user->delivery_guy_detail) {
                \Log::error("Delivery user record or details missing", ["order_id" => $order->id]);
                DB::rollBack();
                return response()->json([
                    "success" => false,
                    "message" => "Delivery user data missing."
                ], 400);
            }

            $delivery_charge = (float) ($order->delivery_charge);
            if ($delivery_charge < 0 && !$order->is_free_delivery) {
                \Log::warning("Negative delivery charge for transaction", [
                    "order_id" => $order->id,
                    "delivery_charge" => $delivery_charge
                ]);
                $delivery_charge = 0;
            }

            $min_order_price = (float) ($order->restaurant->min_order_price ?? 0);
            $is_below_min_order = $order->sub_total < $min_order_price;

         $company_commission = 0;
$coupon_discount = $coupon_delivery_discount;
$free_delivery_cost = (float) ($order->restaurant->free_delivery_cost ?? $delivery_charge);

if ($is_free_delivery_eligible && $order->is_free_delivery) { // احترام قيمة is_free_delivery
    $free_delivery_comm = (float) ($order->restaurant->free_delivery_comm ?? $deliveryGuyCommissionRate);
    $company_commission = -($free_delivery_cost * ($free_delivery_comm / 100));
    $order->driver_order_commission_amount = abs($company_commission);
    $order->delivery_charge = 0;
    $order->is_free_delivery = true;
    $adjusted_delivery_charge = 0;
    $discounted_delivery_charge = 0;
    \Log::info("Free delivery applied due to min order and distance", [
        "order_id" => $order->id,
        "sub_total" => $order->sub_total,
        "free_delivery_subtotal" => $free_delivery_subtotal,
        "distance" => $distance,
        "free_delivery_distance" => $free_delivery_distance,
        "actual_delivery_charge" => $order->actual_delivery_charge,
        "free_delivery_cost" => $free_delivery_cost,
        "free_delivery_comm" => $free_delivery_comm,
        "company_commission" => $company_commission,
        "driver_commission" => $order->driver_order_commission_amount
    ]);
} else {
    if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
        $company_commission = -($deliveryGuyCommissionRate / 100 * ($total_paid - ($order->tip_amount ?? 0)));
    } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
        $commission_base = $is_free_delivery_coupon && $order->actual_payment_mode === "COD" ? $original_delivery_charge_for_cod : $delivery_charge;
        $company_commission = -($deliveryGuyCommissionRate / 100 * $commission_base);
        if ($order->coupon_name) {
            $coupon = Coupon::where("code", $order->coupon_name)->first();
            if ($coupon && $coupon->is_used_for_delivery && $coupon->discount_type === "FREE") {
                $company_commission = -($deliveryGuyCommissionRate / 100 * $original_delivery_charge_for_cod);
            }
        }
    }
    $order->driver_order_commission_amount = abs($company_commission);
    \Log::info("Commission calculated", [
        "order_id" => $order->id,
        "payment_mode" => $order->actual_payment_mode,
        "delivery_charge" => $delivery_charge,
        "commission_base" => $commission_base ?? 0,
        "commission_rate" => $deliveryGuyCommissionRate,
        "company_commission" => $company_commission,
        "driver_commission" => $order->driver_order_commission_amount,
        "distance" => $distance,
        "free_delivery_distance" => $free_delivery_distance,
        "is_free_delivery" => $order->is_free_delivery
    ]);
}

            $order->final_profit = abs($company_commission) - $coupon_discount_for_company + (($payment_type == "WALLET" || $payment_type == "PARTIAL") ? $order->wallet_amount : 0);
            \Log::info("Final profit calculated", [
                "order_id" => $order->id,
                "company_commission" => $company_commission,
                "coupon_discount" => $coupon_discount_for_company,
                "final_profit" => $order->final_profit
            ]);

            $tip_amount = 0;
            if (!empty($deliveryUser->delivery_guy_detail) && $deliveryUser->delivery_guy_detail->tip_commission_rate && !is_null($deliveryUser->delivery_guy_detail->tip_commission_rate)) {
                $tip_amount = ($deliveryUser->delivery_guy_detail->tip_commission_rate / 100) * ($order->tip_amount ?? 0);
                $tip_amount = number_format((float) $tip_amount, 2, ".", "");
                $tip_amount = -$tip_amount;
                $order->driver_order_tip_amount = $tip_amount;
                \Log::info("Driver tip calculated", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUser->id,
                    "tip_amount" => $tip_amount,
                    "tip_commission_rate" => $deliveryUser->delivery_guy_detail->tip_commission_rate
                ]);
            } else {
                $order->driver_order_tip_amount = 0;
                \Log::info("No tip calculated: No tip commission rate", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUser->id
                ]);
            }

            $driver_salary = (float) ($deliveryUser->delivery_guy_detail->fixed_salary ?? 0);
            \Log::info("Driver salary retrieved", [
                "order_id" => $order->id,
                "driver_id" => $deliveryUser->id,
                "driver_salary" => $driver_salary
            ]);

            if (config("setting.enableDeliveryPin") == "true") {
                if ($order->delivery_pin != strtoupper($request->delivery_pin)) {
                    \Log::warning("Invalid delivery PIN", [
                        "order_id" => $order->id,
                        "provided_pin" => $request->delivery_pin,
                        "expected_pin" => $order->delivery_pin
                    ]);
                    $singleOrder = $order;
                    $singleOrderData = (object) [];
                    $singleOrderData->delivery_pin_error = true;
                    $singleOrderData->company_commission = number_format((float) $company_commission, 2, ".", "");
                    $singleOrderData->tip_amount = $tip_amount;
                    $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, ".", "");
                    $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, ".", "");
                    $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, ".", "");
                    $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, ".", "");
                    $singleOrderData->is_wallet_payment = $is_wallet_payment;
                    $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, ".", "");
                    $singleOrderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, ".", "");
                    $singleOrderData->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $delivery_charge : 0), 2, ".", "");
                    $singleOrderData->cod_delivery_charge_deducted = number_format((float) ($cod_delivery_charge_deducted ?? 0), 2, ".", "");

                    $singleOrder->setAppends([]);
                    $singleOrderArray = $singleOrder->toArray();
                    $singleOrderArray['sub_total'] = number_format((float) $order->sub_total, 2, ".", "");
                    $singleOrderArray['free_delivery_cost'] = $singleOrderData->free_delivery_cost;
                    $singleOrderArray['delivery_distance'] = number_format((float) $distance, 2, ".", "");
                    $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);

                    DB::rollBack();
                    return response()->json($singleOrderArray);
                }
                \Log::info("Delivery PIN verified", ["order_id" => $order->id]);
            }

            if (config("setting.enableReferAndEarn") == "true" && config("setting.referralBonusType") == "order") {
                if ($order->user && $order->user->orders->where("orderstatus_id", 5)->count() == 0) {
                    $referredByUser = User::where("id", $order->user->referred_by)->first();
                    if ($referredByUser) {
                        $referralBonusReferringUser = config("setting.referralSuccessAmountReferringUser") * 100;
                        if ($referralBonusReferringUser <= 0) {
                            \Log::warning("Invalid referral bonus amount for referring user", [
                                "user_id" => $referredByUser->id,
                                "referral_bonus" => $referralBonusReferringUser
                            ]);
                        } else {
                            $referredByUser->deposit($referralBonusReferringUser, ["description" => "Referral Bonus Deposited"]);
                            \Log::info("Referral bonus deposited to referring user", [
                                "user_id" => $referredByUser->id,
                                "amount" => $referralBonusReferringUser / 100,
                                "balance_after" => $referredByUser->balanceFloat
                            ]);

                            $alert = new PushNotify();
                            $alert->sendWalletAlert($referredByUser->id, $referralBonusReferringUser / 100, "Referral Bonus Deposited", "deposit");
                        }

                        $referralBonusReferredUser = config("setting.referralSuccessAmountReferredUser") * 100;
                        if ($referralBonusReferredUser <= 0) {
                            \Log::warning("Invalid referral bonus amount for referred user", [
                                "user_id" => $order->user->id,
                                "referral_bonus" => $referralBonusReferredUser
                            ]);
                        } else {
                            $order->user->deposit($referralBonusReferredUser, ["description" => "Referral Bonus Deposited"]);
                            \Log::info("Referral bonus deposited to referred user", [
                                "user_id" => $order->user->id,
                                "amount" => $referralBonusReferredUser / 100,
                                "balance_after" => $order->user->balanceFloat
                            ]);

                            $alert->sendWalletAlert($order->user->id, $referralBonusReferredUser / 100, "Referral Bonus Deposited", "deposit");
                        }
                    } else {
                        \Log::info("No referring user found for referral bonus", ["user_id" => $order->user_id]);
                    }
                } else {
                    \Log::info("Referral bonus not applicable: User has completed orders", ["user_id" => $order->user_id]);
                }
            }

            \Log::info("Driver wallet balance before transactions", [
                "driver_id" => $deliveryUserRecord->user->id,
                "balance" => $deliveryUserRecord->user->balanceFloat ?? 'N/A'
            ]);

            $transaction_delivery_charge = abs($delivery_charge);
            if ($order->is_free_delivery || $is_free_delivery_eligible) {
                $transaction_delivery_charge = 0;
            }

            $coupon = $order->coupon_name ? Coupon::where("code", $order->coupon_name)->first() : null;
            $coupon_type = $coupon ? $coupon->discount_type : null;
            $coupon_is_delivery = $coupon ? $coupon->is_used_for_delivery : false;

            $driver_amount = abs($company_commission) + $driver_salary;
            if ($coupon_discount_for_company > 0) {
                $driver_amount -= $coupon_discount_for_company;
            }

            // تعديل جديد: خصم قيمة المبلغ المدفوع من المحفظة (جزئي أو كامل) من محفظة السائق (الشركة)
            $wallet_paid_amount = 0;
            if ($order->wallet_amount > 0 && ($payment_type == "PARTIAL" || $payment_type == "WALLET")) {
                $driver_amount -= $order->wallet_amount; // خصم المبلغ
                $wallet_paid_amount = - $order->wallet_amount; // للسجل
            }

            if ($driver_amount != 0) {
                $transaction_type = $driver_amount > 0 ? 'deposit' : 'forceWithdraw';
                $amount_to_process = abs($driver_amount) * 100;
                $description = ($payment_type == "COD" ? "Driver commission and salary for COD order" : "Driver commission and salary for order") . " with: " . $order->unique_order_id;

                $deliveryUserRecord->user->$transaction_type($amount_to_process, [
                    "description" => $description,
                    "delivery_charge" => $transaction_delivery_charge,
                    "company_commission" => abs($company_commission),
                    "coupon_discount" => - $coupon_discount_for_company,
                    "coupon_type" => $coupon_type,
                    "coupon_is_delivery" => $coupon_is_delivery,
                    "final_profit" => $order->final_profit,
                    "driver_salary" => $driver_salary,
                    "payment_type" => $payment_type,
                    "paid_amount" => $wallet_paid_amount,
                    "company_owes" => 0
                ]);

                \Log::info("Driver wallet transaction processed", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUserRecord->user->id,
                    "transaction_type" => $transaction_type,
                    "amount" => $amount_to_process / 100,
                    "company_commission" => abs($company_commission),
                    "coupon_discount" => $coupon_discount_for_company,
                    "coupon_type" => $coupon_type,
                    "driver_salary" => $driver_salary,
                    "final_profit" => $order->final_profit,
                    "paid_amount" => $wallet_paid_amount,
                    "balance_after" => $deliveryUserRecord->user->fresh()->balanceFloat
                ]);

                $alert = new PushNotify();
                $alert->sendWalletAlert($deliveryUserRecord->user->id, abs($driver_amount), $description, $transaction_type);
            } else {
                \Log::info("No driver wallet transaction needed: Net amount is zero", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUserRecord->user->id,
                    "company_commission" => abs($company_commission),
                    "coupon_discount" => $coupon_discount_for_company,
                    "driver_salary" => $driver_salary,
                    "final_profit" => $order->final_profit
                ]);
            }

            // Handle tip for driver
            if (config("setting.enableDeliveryGuyEarning") == "true" && $tip_amount < 0) {
                $deliveryUserRecord->user->deposit(abs($tip_amount) * 100, [
                    "description" => $translationData->deliveryTipTransactionMessage . " : " . $order->unique_order_id,
                    "delivery_charge" => 0,
                    "company_commission" => 0,
                    "coupon_discount" => 0,
                    "final_profit" => 0,
                    "driver_salary" => 0,
                    "payment_type" => $payment_type,
                    "paid_amount" => 0,
                    "company_owes" => 0
                ]);
                \Log::info("Driver tip deposited", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUserRecord->user->id,
                    "tip_amount" => abs($tip_amount),
                    "payment_type" => $payment_type,
                    "balance_after" => $deliveryUserRecord->user->fresh()->balanceFloat
                ]);
            } else {
                \Log::info("No tip deposited: Tip is 0 or earning disabled", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUserRecord->user->id
                ]);
            }

            // Handle COD or PARTIAL payments where remaining amount is collected
            if ($payment_type == "COD" || ($is_wallet_payment && $remaining_amount > 0)) {
                $delivery_collection = DeliveryCollection::where("user_id", $deliveryUserRecord->user_id)->first();
                if (!$delivery_collection) {
                    $delivery_collection = new DeliveryCollection();
                    $delivery_collection->user_id = $deliveryUserRecord->user_id;
                }
                $delivery_collection->amount += $remaining_amount > 0 ? $remaining_amount : $order->payable;
                $delivery_collection->zone_id = optional($deliveryUserRecord->user)->zone_id;
                $delivery_collection->save();
                \Log::info("Delivery collection updated", [
                    "order_id" => $order->id,
                    "driver_id" => $deliveryUserRecord->user_id,
                    "amount" => $delivery_collection->amount
                ]);
            }

            $order->orderstatus_id = "5";
            $order->commission_rate = $order->restaurant->commission_rate;
            $order->commission_amount = $order->sub_total * $order->commission_rate / 100;
            $order->driver_salary = $driver_salary;
            $order->restaurant_net_amount = $order->sub_total + $order->restaurant_charge - $order->tax_amount - $coupon_amount - $order->commission_amount;

            $order->save();
            \Log::info("Order status updated to Delivered", [
                "order_id" => $order->id,
                "orderstatus_id" => $order->orderstatus_id,
                "commission_rate" => $order->commission_rate,
                "commission_amount" => $order->commission_amount,
                "driver_salary" => $order->driver_salary,
                "restaurant_net_amount" => $order->restaurant_net_amount,
                "customer_balance" => optional($order->user)->balanceFloat
            ]);

            if ($deliveryUserRecord) {
                $completeDelivery = AcceptDelivery::where("order_id", $order->id)->first();
                $completeDelivery->is_complete = true;
                $completeDelivery->save();
                \Log::info("Delivery status updated", [
                    "order_id" => $order->id,
                    "delivery_id" => $completeDelivery->id,
                    "is_complete" => $completeDelivery->is_complete
                ]);
            } else {
                \Log::warning("Delivery status update skipped: No delivery record found", ["order_id" => $order->id]);
            }

            $singleOrder = Order::where("id", $order->id)
                ->with(["restaurant" => function ($query) {
                    $query->select("id", "name", "description", "address", "pincode", "latitude", "longitude", "commission_rate", "zone_id");
                }])
                ->with(["orderitems" => function ($query) {
                    $query->with(["order_item_addons" => function ($subQuery) {
                        $subQuery->select("id", "orderitem_id", "addon_name", "addon_price");
                    }])->select("id", "order_id", "name", "price", "quantity", "item_id");
                }])
                ->with(["user" => function ($query) {
                    $query->select("id", "name", "phone", "email", "zone_id", "referred_by");
                }])
                ->first();

            if (config("setting.enablePushNotificationOrders") == "true") {
                $notify = new PushNotify();
                $notify->sendPushNotification("5", $order->user_id, $order->unique_order_id);
                \Log::info("Push notification sent to customer", [
                    "order_id" => $order->id,
                    "user_id" => $order->user_id
                ]);
            }

            $restaurant_earning = RestaurantEarning::where("restaurant_id", $order->restaurant->id)
                ->where("is_requested", 0)
                ->first();
            if (!$restaurant_earning) {
                $restaurant_earning = new RestaurantEarning();
                $restaurant_earning->restaurant_id = $order->restaurant->id;
            }
            $restaurant_earning->amount += $order->sub_total + $order->restaurant_charge + $order->tax_amount - $coupon_amount;
            $restaurant_earning->net_amount += $order->restaurant_net_amount;
            $restaurant_earning->zone_id = optional($order->restaurant)->zone_id;
            $restaurant_earning->save();
            \Log::info("Restaurant earnings updated", [
                "order_id" => $order->id,
                "restaurant_id" => $order->restaurant->id,
                "amount" => $restaurant_earning->amount,
                "net_amount" => $restaurant_earning->net_amount
            ]);

            if ($payment_type == "COD" || ($is_wallet_payment && $remaining_amount > 0)) {
                if ($deliveryUserRecord) {
                    $delivery_collection = DeliveryCollection::where("user_id", $deliveryUserRecord->user_id)->first();
                    if (!$delivery_collection) {
                        $delivery_collection = new DeliveryCollection();
                        $delivery_collection->user_id = $deliveryUserRecord->user_id;
                    }
                    $delivery_collection->amount += $remaining_amount > 0 ? $remaining_amount : $order->payable;
                    $delivery_collection->zone_id = optional($deliveryUserRecord->user)->zone_id;
                    $delivery_collection->save();
                    \Log::info("Delivery collection updated", [
                        "order_id" => $order->id,
                        "driver_id" => $deliveryUserRecord->user_id,
                        "amount" => $delivery_collection->amount
                    ]);
                } else {
                    \Log::warning("Delivery collection update skipped: No delivery record found", ["order_id" => $order->id]);
                }
            }

            $order->user->zone_id = $order->restaurant->zone_id;

            if (str_contains($order->user->email, 'app.sali-star.com')) {
                $order->user->phone = '+963945555135';
                $order->user->email = str_replace('app.sali-star.com', Carbon::today()->toDateString() . '-' . str_random(10) . 'app.sali-star.com', $order->user->email);
                $order->user->save();
            }

            $this->sendInvoiceToCustomer($order);
            \Log::info("Invoice sent to customer", ["order_id" => $order->id, "user_id" => $order->user_id]);

            activity()
                ->performedOn($order)
                ->causedBy($deliveryUser)
                ->withProperties(["type" => "Order_Delivered"])
                ->log("Order delivered");
            \Log::info("Activity logged: Order delivered", ["order_id" => $order->id, "driver_id" => $deliveryUser->id]);

            $singleOrderData = (object) [];
            $singleOrderData->company_commission = number_format((float) $company_commission, 2, ".", "");
            $singleOrderData->tip_amount = $tip_amount;
            $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, ".", "");
            $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, ".", "");
            $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, ".", "");
            $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, ".", "");
            $singleOrderData->is_wallet_payment = $is_wallet_payment;
            $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, ".", "");
            $singleOrderData->coupon_delivery_discount = number_format((float) $coupon_delivery_discount, 2, ".", "");
            $singleOrderData->free_delivery_cost = number_format((float) ($is_free_delivery_eligible ? $delivery_charge : 0), 2, ".", "");
            $singleOrderData->cod_delivery_charge_deducted = number_format((float) ($cod_delivery_charge_deducted ?? 0), 2, ".", "");

            $singleOrder->setAppends([]);
            $singleOrderArray = $singleOrder->toArray();
            $singleOrderArray['sub_total'] = number_format((float) $order->sub_total, 2, ".", "");
            $singleOrderArray['free_delivery_cost'] = $singleOrderData->free_delivery_cost;
            $singleOrderArray['delivery_distance'] = number_format((float) $distance, 2, ".", "");
            $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);

            \Log::info("Deliver Order Response", [
                "order_id" => $order->id,
                "actual_payment_mode" => $order->actual_payment_mode,
                "wallet_amount" => $order->wallet_amount,
                "total" => $order->total,
                "sub_total" => $order->sub_total,
                "adjusted_total" => $adjusted_total,
                "total_paid" => $total_paid,
                "delivery_charge" => $adjusted_delivery_charge,
                "discounted_delivery_charge" => $discounted_delivery_charge,
                "actual_delivery_charge" => $order->actual_delivery_charge,
                "company_commission" => $company_commission,
                "coupon_amount" => $coupon_amount,
                "coupon_delivery_discount" => $coupon_delivery_discount,
                "driver_salary" => $driver_salary,
                "final_profit" => $order->final_profit,
                "remaining_amount" => $remaining_amount,
                "is_wallet_payment" => $is_wallet_payment,
                "orderitems_count" => count($singleOrderArray["orderitems"] ?? []),
                "orderitems" => $singleOrderArray["orderitems"] ?? [],
                "customer_balance" => optional($order->user)->balanceFloat,
                "free_delivery_cost" => $singleOrderArray['free_delivery_cost'],
                "driver_commission" => $order->driver_order_commission_amount,
                "cod_delivery_charge_deducted" => $cod_delivery_charge_deducted ?? 0
            ]);

            DB::commit();
            \Log::info("Transaction committed", ["order_id" => $order->id]);
            return response()->json($singleOrderArray);
        } else {
            \Log::error("Unauthorized or invalid order for delivery", [
                "order_id" => $request->order_id,
                "driver_id" => $deliveryUser->id
            ]);
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "Order cancelled/completed not found or cannot view order."
            ], 401);
        }
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Deliver Order Error: " . $e->getMessage(), [
            "order_id" => $request->order_id,
            "driver_id" => $deliveryUser->id,
            "exception_trace" => $e->getTraceAsString()
        ]);
        return response()->json([
            "success" => false,
            "message" => $e->getMessage(),
            "error_code" => 500
        ], 500);
    }
}




    // ========================
     
        
    
    
    
    /**
     * @param $deliveryGuy
     * @param $order
     */
    private function canPerformAction($deliveryGuy, $order)
    {
        if ($order->orderstatus_id == '1') {
            return false;
        }

        if ($order->orderstatus_id == '5' || $order->orderstatus_id == '6') {
            return false;
        }

        if ($order->orderstatus_id == '3' || $order->orderstatus_id == '4') {
            if ($deliveryGuy->id != $order->accept_delivery->user_id) {
                return false;
            }
        }

        return true;
    }
 
     private function getDeliveryChargeForCommissionCalc($order)
    {
        return max(0, $order->delivery_charge);
    }
    
    private function sendInvoiceToCustomer($order)
    {
        if (config('setting.sendOrderInvoiceOverEmail') == 'true') {
            try {
                Mail::send('emails.invoice', ['order' => $order], function ($email) use ($order) {
                    $email->subject(config('setting.orderInvoiceEmailSubject') . '#' . $order->unique_order_id);
                    $email->from(config('setting.sendEmailFromEmailAddress'), config('setting.sendEmailFromEmailName'));
                    $email->to($order->user->email);
                });
            } catch (\Exception $e) {
                \Log::error("Email Invoice sending failed. " . $e->getMessage());
            }
        }
        return true;
    }
    
    
}
 