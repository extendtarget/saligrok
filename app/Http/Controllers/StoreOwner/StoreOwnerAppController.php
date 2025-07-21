<?php

namespace App\Http\Controllers\StoreOwner;

use Auth;
use Lang;
use Image;
use JWTAuth;
use App\Item;
use App\Page;
use App\User;
use App\Order;
use App\Rating;
use App\Orderitem;
use Carbon\Carbon;
use App\PushNotify;
use App\Restaurant;
use App\ItemCategory;
use JWTAuthException;
use App\RestaurantPayout;
use App\RestaurantEarning;
use Illuminate\Http\Request;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Jobs\AssignNearestDeliveryGuy;
use App\AddonCategory; // added by qusay
use App\Addon; // added by qusay
use Illuminate\Support\Facades\Validator; // added by qusay
use Illuminate\Support\Facades\Log;
class StoreOwnerAppController extends Controller
{

    public function getAllLanguage()
    {
        $languages = [];
        $jsonFiles = glob(storage_path('storeapp-language') . '/*');
        foreach ($jsonFiles as $file) {
            $fileContents = file_get_contents($file);
            if ($this->isValidJson($fileContents)) {
                $fileName = basename($file);
                $fileName = str_replace(".json", "", $fileName);
                array_push($languages, $fileName);
            }
        }
        return response()->json($languages);
    }

    public function getSingleLanguage($language_code)
    {
        $path = '/storeapp-language/' . $language_code . '.json';
        $data = json_decode(File::get(storage_path($path)), true);
        return response()->json($data);
    }

    /**
     * @param $email
     * @param $password
     * @return mixed
     */
    // private function getToken($email, $password)
    // {
    //     $token = null;
    //     try {
    //         if (!$token = JWTAuth::attempt(['email' => $email, 'password' => $password])) {
    //             return response()->json([
    //                 'response' => 'error',
    //                 'message' => 'Password or email is invalid..',
    //                 'token' => $token,
    //             ]);
    //         }
    //     } catch (JWTAuthException $e) {
    //         return response()->json([
    //             'response' => 'error',
    //             'message' => 'Token creation failed',
    //         ]);
    //     }
    //     return $token;
    // }
private function getToken($email, $password)
{
    try {
       
        $token = \Tymon\JWTAuth\Facades\JWTAuth::attempt(['email' => $email, 'password' => $password]);
        if (!$token) {
            \Log::warning('Invalid credentials for email: ' . $email);
            return response()->json([
                'response' => 'error',
                'message' => 'Password or email is invalid',
                'token' => null,
            ], 401);
        }
        return $token;
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        \Log::error('Token creation failed for email ' . $email . ': ' . $e->getMessage());
        return response()->json([
            'response' => 'error',
            'message' => 'Token creation failed',
        ], 500);
    }
}
    /**
     * @param Request $request
     */
    // public function login(Request $request)
    // {
       
    //     $user = User::where('email', $request->email)->get()->first();
    //     if ($user && Hash::check($request->password, $user->password)) {

    //         if ($user->hasRole('Store Owner')) {
    //             $token = self::getToken($request->email, $request->password);
    //             $user->auth_token = $token;
    //             $user->save();

    //             $response = [
    //                 'success' => true,
    //                 'data' => [
    //                     'id' => $user->id,
    //                     'auth_token' => $user->auth_token,
    //                     'name' => $user->name,
    //                     'email' => $user->email,
    //                     'phone' => $user->phone,
    //                     'stores' => $user->restaurants,
    //                 ],
    //             ];
    //         } else {
    //             $response = ['success' => false, 'data' => 'Record doesnt exists'];
    //         }
    //     } else {
    //         $response = ['success' => false, 'data' => 'Record doesnt exists...'];
    //     }
    //     return response()->json($response, 201);
    // }
    public function login(Request $request)
    {
        $user = \App\User::where('email', $request->email)->first();
        if ($user && \Hash::check($request->password, $user->password)) {
            if ($user->hasRole('Store Owner')) {
              
                $tokenResponse = $this->getToken($request->email, $request->password);
    
                
                if (is_string($tokenResponse)) {
                    $token = $tokenResponse;
               
                    $user->auth_token = $token;
                    $user->save();
    
                    $response = [
                        'success' => true,
                        'data' => [
                            'id' => $user->id,
                            'auth_token' => $token,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'stores' => $user->restaurants,
                        ],
                    ];
                } else {
                 
                    return response()->json($tokenResponse, $tokenResponse['response'] === 'error' ? 401 : 500);
                }
            } else {
                $response = ['success' => false, 'data' => 'User is not a Store Owner'];
            }
        } else {
            $response = ['success' => false, 'data' => 'Invalid credentials'];
        }
    
        return response()->json($response, 201);
    }

// Extend By Aya 
 /**
     * Logout the store owner and invalidate the JWT token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('Store Owner')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or invalid user role'
            ], 401);
        }

        try {
            // Invalidate the current JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            // Clear the auth_token in the database
            $user->auth_token = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200);

        } catch (JWTAuthException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }
    // ================================

    public function dashboard(Request $request)
    {
        $user = Auth::user();

        $store = Restaurant::where('id', $request->store_id)
        ->select([
            'id',
            'name',
            'description',
            'image',
            'rating',
            'restaurant_charges',
            'address',
            'is_active',
            'is_accepted',
            'is_featured',
            'delivery_type',
            'delivery_radius',
            'phone'
        ])
        ->first();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }


        $newOrdersIds = Order::where('restaurant_id', $request->store_id)
            ->where(function ($query) {
                $query->where('orderstatus_id', 1)
                    ->orWhere('orderstatus_id', 10);
             })
            ->pluck('id');

        $ordersCount = Order::where('restaurant_id', $request->store_id)
            ->where('orderstatus_id', '5')->count();

        
        $orderItemsCount = Order::where('orders.restaurant_id', $request->store_id)
        ->where('orders.orderstatus_id', 5)
        ->join('orderitems', 'orders.id', '=', 'orderitems.order_id')
        ->sum('orderitems.quantity');

        
        $totalEarning = Order::where('restaurant_id', $request->store_id)
        ->where('orderstatus_id', 5)
        ->selectRaw('SUM(COALESCE(total, 0) - COALESCE(delivery_charge, 0) - COALESCE(tip_amount, 0)) as total_earning')
        ->value('total_earning') ?? 0;
        

        $todayOrders = Order::where('orderstatus_id', 5)
            ->where('restaurant_id', $request->store_id)
            ->select('id', 'orderstatus_id', 'created_at', 'total', 'delivery_charge', 'tip_amount')
            ->whereBetween('created_at', [
                Carbon::now()->startOfDay(),
                Carbon::now(),
            ])->get();

        $todayOrdersCount = $todayOrders->count();

        $todayEarning = 0;
        foreach ($todayOrders as $todayOrder) {
            $todayEarning += $todayOrder->total - ($todayOrder->delivery_charge + $todayOrder->tip_amount);
        }

        $orderIdsToday = $todayOrders->pluck('id');

        $topItemsToday = Orderitem::whereIn('order_id', $orderIdsToday)
            ->select('item_id', 'name', 'price', DB::raw('SUM(quantity) as qty'))
            ->groupBy('item_id')
            ->orderBy('qty', 'DESC')
            ->take(3)
            ->get();

        $inactiveItemCount = Item::where('restaurant_id', $request->store_id)
            ->where('is_active', 0)->count();

        $arrayData = [
            'store' => $store,
            'restaurantsCount' => count($user->restaurants),
            'ordersCount' => $ordersCount,
            'orderItemsCount' => (float)$orderItemsCount,
            'totalEarning' => number_format((float) $totalEarning, 2, '.', ','),
            'newOrdersIds' => $newOrdersIds,
            'todayOrdersCount' => $todayOrdersCount,
            'todayEarning' => $todayEarning,
            'topItemsToday' => $topItemsToday,
            'inactiveItemCount' => $inactiveItemCount
        ];

        return response()->json($arrayData, 200);
    }
    
    
    // public function dashboard(Request $request)
    // {
    //     $user = Auth::user();

    //     $store = Restaurant::where('id', $request->store_id)->first();

    //     $restaurantIds = $user->restaurants->pluck('id')->toArray();

    //     if (!in_array($request->store_id, $restaurantIds)) {
    //         return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
    //     }

    //     $newOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['1', '10'])
    //         ->orderBy('id', 'DESC')
    //         ->with('restaurant')
    //         ->get();

    //     // dd($newOrders);

    //     $newOrdersIds = $newOrders->pluck('id')->toArray();

    //     $preparingOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['2', '3', '11'])
    //         ->where('delivery_type', '<>', 2)
    //         ->orderBy('orderstatus_id', 'ASC')
    //         ->with('restaurant')
    //         ->get();

    //     $selfpickupOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['2', '7'])
    //         ->where('delivery_type', 2)
    //         ->orderBy('orderstatus_id', 'DESC')
    //         ->with('restaurant')
    //         ->get();

    //     $ongoingOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['4'])
    //         ->orderBy('orderstatus_id', 'DESC')
    //         ->with('restaurant')
    //         ->get();

    //     $ordersCount = Order::where('restaurant_id', $request->store_id)
    //         ->where('orderstatus_id', '5')->count();

    //     $allCompletedOrders = Order::where('restaurant_id', $request->store_id)
    //         ->where('orderstatus_id', '5')
    //         ->with('orderitems')
    //         ->get();

    //     $orderItemsCount = 0;
    //     foreach ($allCompletedOrders as $cO) {
    //         foreach ($cO->orderitems as $orderItem) {
    //             $orderItemsCount += $orderItem->quantity;
    //         }
    //     }

    //     $totalEarning = 0;
    //     settype($var, 'float');

    //     foreach ($allCompletedOrders as $completedOrder) {
    //         $totalEarning += $completedOrder->total - ($completedOrder->delivery_charge + $completedOrder->tip_amount);
    //     }

    //     $todayOrders = Order::where('orderstatus_id', 5)
    //         ->where('restaurant_id', $request->store_id)
    //         ->select('id', 'orderstatus_id', 'created_at', 'total', 'delivery_charge', 'tip_amount')
    //         ->whereBetween('created_at', [
    //             Carbon::now()->startOfDay(),
    //             Carbon::now(),
    //         ])->get();

    //     $todayOrdersCount = $todayOrders->count();

    //     $todayEarning = 0;
    //     foreach ($todayOrders as $todayOrder) {
    //         $todayEarning += $todayOrder->total - ($todayOrder->delivery_charge + $todayOrder->tip_amount);
    //     }

    //     $orderIdsToday = $todayOrders->pluck('id');

    //     $topItemsToday = Orderitem::whereIn('order_id', $orderIdsToday)
    //         ->select('item_id', 'name', 'price', DB::raw('SUM(quantity) as qty'))
    //         ->groupBy('item_id')
    //         ->orderBy('qty', 'DESC')
    //         ->take(3)
    //         ->get();

    //     $inactiveItemCount = Item::where('restaurant_id', $request->store_id)
    //         ->where('is_active', 0)->count();

    //     $arrayData = [
    //         'store' => $store,
    //         'restaurantsCount' => count($user->restaurants),
    //         'ordersCount' => $ordersCount,
    //         'orderItemsCount' => $orderItemsCount,
    //         'totalEarning' => number_format((float) $totalEarning, 2, '.', ','),
    //         'newOrders' => $newOrders,
    //         'newOrdersIds' => $newOrdersIds,
    //         'preparingOrders' => $preparingOrders,
    //         'ongoingOrders' => $ongoingOrders,
    //         'selfpickupOrders' => $selfpickupOrders,
    //         'todayOrdersCount' => $todayOrdersCount,
    //         'todayEarning' => $todayEarning,
    //         'topItemsToday' => $topItemsToday,
    //         'inactiveItemCount' => $inactiveItemCount
    //     ];

    //     return response()->json($arrayData, 200);
    // }

    public function toggleStoreStatus(Request $request)
    {
        $user = Auth::user();
        $store = Restaurant::where('id', $request->store_id)->first();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $store->is_schedulable = false;
        $store->toggleActive();
        $store->save();

        $response = [
            'success' => true,
            'status' => $store->is_active,
        ];
        return response()->json($response, 200);
    }
        // Extend By Aya
    public function getStoreStatus(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();
    
       
        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
    
      
        $store = Restaurant::where('id', $request->store_id)
            ->select('id', 'is_active')
            ->first();
    
        if (!$store) {
            return response()->json(['success' => false, 'message' => "Store not found"], 404);
        }
    
        $response = [
            'success' => true,
            'status' => $store->is_active,
        ];
    
        return response()->json($response, 200);
    }

 
   // public function getOrders(Request $request)
    // {
    //     // sleep(1000);
    //     $storeOwner = Auth::user();
    //     $storeOwnerId = $storeOwner->id;
    //     $storeOwner = User::where('id', $storeOwnerId)->first();
    //     $restaurantIds = $storeOwner->restaurants->pluck('id')->toArray();
    //     $restaurant = Restaurant::find($request->store_id); // added by qusay

    //     if (!in_array($request->store_id, $restaurantIds)) {
    //         return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
    //     }

    //     /* added by qusay */
    //     $newOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['1', '10']);
        
    //     if ( $restaurant->is_order_need_approval_by_admin ) {
    //         $newOrders->where('is_accepted_by_admin', true);
    //     }
        
    //     $newOrders = $newOrders->orderBy('id', 'DESC')
    //         ->with('restaurant')
    //         ->with('orderitems.order_item_addons')
    //         ->withCount('orderitems')
    //         ->get();
    //     /* added by qusay */
        
    // $preparingOrders = Order::where('restaurant_id', $request->store_id)
    //     ->whereIn('orderstatus_id', ['2', '3', '11'])
    //     ->where(function ($query) {
    //         $query->where('delivery_type', '<>', 2)
    //               ->orWhere('is_manual_order', 1); 
    //     })
    //     ->orderBy('orderstatus_id', 'ASC')
    //     ->with('restaurant')
    //     ->with('orderitems.order_item_addons')
    //     ->withCount('orderitems')
    //     ->get();

    //       $selfpickupOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['2', '7'])
    //         ->where('delivery_type', 2)
    //         ->where('is_manual_order', 0) 
    //         ->orderBy('orderstatus_id', 'DESC')
    //         ->with('restaurant')
    //         ->with('orderitems.order_item_addons')
    //         ->withCount('orderitems')
    //         ->get();

    //     $ongoingOrders = Order::where('restaurant_id', $request->store_id)
    //         ->whereIn('orderstatus_id', ['4'])
    //         ->orderBy('orderstatus_id', 'DESC')
    //         ->with('restaurant')
    //         ->with('orderitems.order_item_addons')
    //         ->withCount('orderitems')
    //         ->get();

    //     $response = [
    //         'new_orders' => $newOrders,
    //         'preparing_orders' => $preparingOrders,
    //         'selfpickup_orders' => $selfpickupOrders,
    //         'ongoing_orders' => $ongoingOrders,
    //     ];
    //     return response()->json($response, 200);
    // }
    
    public function getOrders(Request $request)
    {
        $storeOwner = Auth::user();
        $storeOwnerId = $storeOwner->id;
        $storeOwner = User::where('id', $storeOwnerId)->first();
        $restaurantIds = $storeOwner->restaurants->pluck('id')->toArray();
        $restaurant = Restaurant::find($request->store_id);
    
        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
    
        $newOrders = Order::where('restaurant_id', $request->store_id)
            ->whereIn('orderstatus_id', ['1', '10']);
        
        if ($restaurant->is_order_need_approval_by_admin) {
            $newOrders->where('is_accepted_by_admin', true);
        }
        
        $newOrders = $newOrders->orderBy('id', 'DESC')
            ->with('restaurant')
            ->with('orderitems.order_item_addons')
            ->withCount('orderitems')
            ->get();
    
        $preparingOrders = Order::where('restaurant_id', $request->store_id)
            ->whereIn('orderstatus_id', ['2', '3', '11'])
            ->where(function ($query) {
                $query->where('delivery_type', '<>', 2)
                      ->orWhere('is_manual_order', 1); 
            })
            ->orderBy('orderstatus_id', 'ASC')
            ->with('restaurant')
            ->with('orderitems.order_item_addons')
            ->withCount('orderitems')
            ->get();
    
        $selfpickupOrders = Order::where('restaurant_id', $request->store_id)
            ->whereIn('orderstatus_id', ['2', '7'])
            ->where('delivery_type', 2)
            ->where('is_manual_order', 0) 
            ->orderBy('orderstatus_id', 'DESC')
            ->with('restaurant')
            ->with('orderitems.order_item_addons')
            ->withCount('orderitems')
            ->get();
    
        $ongoingOrders = Order::where('restaurant_id', $request->store_id)
            ->whereIn('orderstatus_id', ['4'])
            ->orderBy('orderstatus_id', 'DESC')
            ->with('restaurant')
            ->with('orderitems.order_item_addons')
            ->withCount('orderitems')
            ->get();
    
        $response = [
            'new_orders' => $newOrders,
            'preparing_orders' => $preparingOrders,
            'selfpickup_orders' => $selfpickupOrders,
            'ongoing_orders' => $ongoingOrders,
        ];
        return response()->json($response, 200);
    }
    
    public function getSingleOrder(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $order = Order::where('id', $request->order_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address');
            }))
            ->with(array('accept_delivery.user' => function ($query) {
                $query->select('id', 'name');
            }))
            ->first();

        if ($order) {
            // start added by qusay
            if ( $order->is_scheduled ) {
                $day = json_decode($order->schedule_date)->day;
                $date = json_decode($order->schedule_date)->date;
                $slot_open = json_decode($order->schedule_slot)->open;
                $slot_close = json_decode($order->schedule_slot)->close;
                $order->order_comment .= ' ' . "Date: $day, $date \n, Slot: $slot_open - $slot_close";
            }
            // end added by qusay
            
            return response()->json($order, 200);
        }

        $response = [
            'success' => false,
            'message' => 'Order not found',
        ];
        return response()->json($response, 401);
    }

    public function cancelOrder(Request $request, TranslationHelper $translationHelper)
    {
        $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
        $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $order = Order::where('id', $request->order_id)->where('restaurant_id', $request->store_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address');
            }))->first();

        $customer = User::where('id', $order->user_id)->first();
        $storeOwner = Auth::user();

        if ($order && $user) {
            if ($order->orderstatus_id == '1') {
                //change order status to 6 (Canceled)
                $order->orderstatus_id = 6;
                $order->refund_type = 'NOREFUND'; // added by qusay
                $statis_reason = 'تم إلغاء طلبك من قبل المطعم بسبب: '; // added by qusay
                $order->cancel_reason = $statis_reason . $request->cancel_reason; // added by qusay
                $order->save();

                //if COD, then check if wallet is present
                if ($order->payment_mode == 'COD') {
                    if ($order->wallet_amount != null) {
                        //refund wallet amount
                        $customer->deposit($order->wallet_amount * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
                    }
                    activity()
                        ->performedOn($order)
                        ->causedBy($storeOwner)
                        ->withProperties(['type' => 'Order_Canceled_Store'])->log('Order canceled');
                } else {
                    //if online payment, refund the total to wallet
                    $customer->deposit(($order->total) * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                    activity()
                        ->performedOn($order)
                        ->causedBy($storeOwner)
                        ->withProperties(['type' => 'Order_Canceled_Store'])->log('Order canceled with Full Refund');
                }

                //show notification to user
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    //to user
                    $notify = new PushNotify();
                    $notify->sendPushNotification('6', $order->user_id, $order->unique_order_id);
                }
                

                return response()->json($order, 200);
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Order not found',
            ];
            return response()->json($response, 401);
        }
    }
    public function acceptOrder(Request $request)
    {
    
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();
    
        // if (!in_array($request->store_id, $restaurantIds)) {
        //     return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        // }
        
        
        /* changed by qusay */
        if (!in_array($request->store_id, $restaurantIds) && !isset($request->order_id) ) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
        /* added by qusay */
    


        /* changed by qusay */
        $order = Order::where('id', $request->order_id)->where('restaurant_id', $request->store_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address', 'show_time_on_order_accept');
            }))->first();
        /* changed by qusay */

        if ($order->orderstatus_id == '1') {
            $order->orderstatus_id = 2;
            
            
            /* added by qusay */
            if ( $order->restaurant->show_time_on_order_accept == true ) {
                
                
                if ( !isset($request->delay_before_driver_visibility) ) {
                    $request->delay_before_driver_visibility = 15;
                    // return response()->json(["success" => false, 'message' => 'delay_before_driver_visibility is required'], 400);
                }

                $delay_before_driver_visibility = $request->delay_before_driver_visibility;

                if ( $delay_before_driver_visibility == 'other' ) {
                    
                    if ( !isset($request->custom_time) ) {
                        return response()->json(["success" => false, 'message' => 'custom_time is required'], 400);
                    } else if ( (int)$request->custom_time < 15 ) {
                        return response()->json(["success" => false, 'message' => 'Minimum 15 minutes'], 400);
                    }
                    
                    $delay_before_driver_visibility = (int)$request->custom_time;
                }
                
                $delay_before_driver_visibility -= 15;
                
                $wating_date = new \DateTime();
                $wating_date->modify("+$delay_before_driver_visibility minutes");
                $order->delay_before_driver_visibility = $wating_date->format('Y-m-d H:i:s');
            }
            /* added by qusay */

            

            if (isset($request->order_prep_time) && $request->order_prep_time != null) {
                $order->prep_time = Carbon::now()->addMinutes($request->order_prep_time)->toDateTimeString();
            }
            

            $order->save();

            if (config('setting.enablePushNotificationOrders') == 'true') {
                //to user
                $notify = new PushNotify();
                $notify->sendPushNotification('2', $order->user_id, $order->unique_order_id);
            }


            if ($order->delivery_type == '1') {
                if (config('setting.autoAssignNearestDeliveryGuy') == "true") {
                    if (config('setting.autoAssignDeliveryGuyDelay') != null || config('setting.autoAssignDeliveryGuyDelay') > 0) {
                        // sendPushNotificationToDelivery($order->restaurant->id, $order);
                        // sendSmsToDelivery($order->restaurant->id);
                    }
                    // $delay = config('setting.autoAssignDeliveryGuyDelay') != null ? config('setting.autoAssignDeliveryGuyDelay') * 60 : 0;
                    $prepTime = Carbon::parse($order->prep_time);
                    $delay = $prepTime->subMinutes(1)->diffInSeconds(Carbon::now());
                    if ($delay < 0) $delay = 0;
                    AssignNearestDeliveryGuy::dispatch($order)->delay($delay);
                } else {
                    sendPushNotificationToDelivery($order->restaurant->id, $order);
                    sendSmsToDelivery($order->restaurant->id);
                }
            }

            /* changed by qusay */
            if ( !isset($request->order_id) ) {
              activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Accepted_Store'])->log('Order accepted');  
            } else {
                $user = User::where('id', 14723)->first(); // Manualorder
                 activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Accepted_Store'])->log('Order accepted');
            }
            /* changed by qusay */
            

            

            return response()->json($order, 200);
        } else {
            $order->already_action = true;
            return response()->json($order, 200);
        }
    }
    // aya 
    public function markSelfpickupOrderReady(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $order = Order::where('id', $request->order_id)->where('restaurant_id', $request->store_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address');
            }))->first();

        if ($order->orderstatus_id == '2') {
            $order->orderstatus_id = 7;
            $order->save();

            if (config('setting.enablePushNotificationOrders') == 'true') {

                //to user
                $notify = new PushNotify();
                $notify->sendPushNotification('7', $order->user_id, $order->unique_order_id);
            }

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Ready_Store'])->log('Order prepared');

            return response()->json($order, 200);
        } else {
            $response = [
                'success' => false,
                'message' => 'Order not found',
            ];
            return response()->json($response, 401);
        }
    }

    public function markSelfpickupOrderCompleted(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $order = Order::where('id', $request->order_id)->where('restaurant_id', $request->store_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone', 'zone_id');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address', 'zone_id', 'commission_rate');
            }))->first();

        if ($order->orderstatus_id == '7') {

            if (config('setting.enableReferAndEarn') == "true" && config('setting.referralBonusType') == "order") {
                if ($order->user->orders->where('orderstatus_id', 5)->count() == 0) {
                    $referredByUser = User::where('id', $order->user->referred_by)->with('wallet')->first(); // Use 'find' instead of 'where' for single user retrieval
                    if ($referredByUser) {

                        $referralBonusReferringUser = config('setting.referralSuccessAmountReferringUser') * 100;
                        $referredByUser->deposit($referralBonusReferringUser, ['description' => "Referral Bonus Deposited"]);

                        $alert = new PushNotify();
                        $alert->sendWalletAlert($referredByUser->id, $referralBonusReferringUser, "Referral Bonus Deposited", 'deposit');

                        $referralBonusReferredUser = config('setting.referralSuccessAmountReferredUser') * 100;
                        $order->user->deposit($referralBonusReferredUser, ['description' => "Referral Bonus Deposited"]);

                        $alert = new PushNotify();
                        $alert->sendWalletAlert($order->user->id, $referralBonusReferredUser, "Referral Bonus Deposited", 'deposit');
                    }
                }
            }

            $order->orderstatus_id = 5;
            //Commission Amount Datas Saved Here
            $order->commission_rate = $order->restaurant->commission_rate;
            $order->commission_amount = $order->sub_total * $order->commission_rate / 100;
            $order->final_profit = $order->commission_amount;
            $order->restaurant_net_amount = $order->sub_total + $order->restaurant_charge - $order->coupon_amount - $order->commission_amount;
            $order->final_profit = $order->commission_amount;
            $order->save();

            //if selfpickup add amount to restaurant earnings if not COD then add order total
            $restaurant_earning = RestaurantEarning::where('restaurant_id', $order->restaurant->id)
                ->where('is_requested', 0)
                ->first();
            if (!$restaurant_earning) {
                $restaurant_earning = new RestaurantEarning();
                $restaurant_earning->restaurant_id = $order->restaurant->id;
            }
            if ($order->payment_mode != 'COD') {
                $restaurant_earning->amount += $order->sub_total + $order->restaurant_charge - $order->coupon_amount;
                $restaurant_earning->net_amount += $order->restaurant_net_amount;
                $restaurant_earning->zone_id = $order->restaurant->zone_id ? $order->restaurant->zone_id : null;
                $restaurant_earning->save();
            } else {
                $restaurant_earning->amount += $order->sub_total + $order->restaurant_charge - $order->coupon_amount - $order->payable;
                $restaurant_earning->net_amount += $order->restaurant_net_amount - $order->payable;
                $restaurant_earning->zone_id = $order->restaurant->zone_id ? $order->restaurant->zone_id : null;
                $restaurant_earning->save();
            }

            if (config('setting.enablePushNotificationOrders') == 'true') {
                //to user
                $notify = new PushNotify();
                $notify->sendPushNotification('5', $order->user_id, $order->unique_order_id);
            }

            if (config('setting.sendOrderInvoiceOverEmail') == 'true') {
                Mail::send('emails.invoice', ['order' => $order], function ($email) use ($order) {
                    $email->subject(config('setting.orderInvoiceEmailSubject') . '#' . $order->unique_order_id);
                    $email->from(config('setting.sendEmailFromEmailAddress'), config('setting.sendEmailFromEmailName'));
                    $email->to($order->user->email);
                });
            }

            $order->user->zone_id = $order->restaurant->zone_id;
            $order->user->save();

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Completed_Store'])->log('Order completed');

            return response()->json($order, 200);
        } else {
            $response = [
                'success' => false,
                'message' => 'Order not found',
            ];
            return response()->json($response, 401);
        }
    }

    public function confirmScheduledOrder(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $order = Order::where('id', $request->order_id)->where('restaurant_id', $request->store_id)
            ->with('orderitems.order_item_addons')
            ->with(array('user' => function ($query) {
                $query->select('id', 'name', 'email', 'phone');
            }))
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address');
            }))->first();

        if ($order->orderstatus_id == '10') {
            $order->orderstatus_id = 11;
            $order->save();

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Confirm_Scheduled_Order_Store'])->log('Scheduled order confirmed');

            return response()->json($order, 200);
        } else {
            $response = [
                'success' => false,
                'message' => 'Order not found',
            ];
            return response()->json($response, 401);
        }
    }

    public function getMenu(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $items = Item::where('restaurant_id', $request->store_id)->pluck('item_category_id')->toArray();
        $filteredItemCategoryIds = array_unique($items);

        $itemCategories = ItemCategory::whereIn('id', $filteredItemCategoryIds)
            ->with(array('items' => function ($query) use ($request) {
                $query->where('restaurant_id', $request->store_id);
            }))
            ->get();

        foreach ($itemCategories as $itemCategory) {
            if ($itemCategory->user_id == $user->id) {
                $itemCategory->canEdit = true;
            } else {
                $itemCategory->canEdit = false;
            }
        }

        return response()->json($itemCategories, 200);
    }

    public function toggleItemStatus(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        $item = Item::where('id', $request->item_id)
            ->where('restaurant_id', $request->store_id)
            ->first();

        if (!in_array($item->restaurant_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        if ($item) {
            $item->toggleActive()->save();
            $response = [
                'success' => true,
                'status' => $item->is_active,
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'success' => false,
                'message' => "Something went wrong"
            ];
            return response()->json($response, 400);
        }
    }

    public function searchItems(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $items = Item::where('restaurant_id', $request->store_id)
            ->where('name', 'LIKE', "%$request->q%")
            ->take(100)
            ->get();

        return response()->json($items, 200);
    }

    public function editItem(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $item = Item::where('id', $request->item_id)
            ->where('restaurant_id', $request->store_id)
            ->first();
        return response()->json($item, 200);
    }
    
    /* added by qusay */
    public function createItem(Request $request) {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'price' => 'required|numeric',
            'old_price' => 'nullable|numeric',
            'restaurant_id' => 'required|numeric',
            'item_category_id' => 'required|numeric',
            'image' => 'nullable|file',
            'is_recommended' => 'required|boolean',
            'is_popular' => 'required|boolean',
            'is_new' => 'required|boolean',
            'is_veg' => 'required|in:veg,nonveg,none',
            'addon_category_item' => 'nullable|array',
            'token' => 'required|string'
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();
        
        if (!in_array($request->restaurant_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
        
        $new_item = new Item();
        $new_item->name = $request->name;
        $new_item->desc = $request->description;
        $new_item->price = $request->price;
        $new_item->old_price = $request->old_price == null ? 0 : $request->old_price;
        $new_item->restaurant_id = $request->restaurant_id;
        $new_item->item_category_id = $request->item_category_id;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $rand_name = time() . str_random(10);
            $filename = $rand_name . '.jpg';
            Image::make($image)
                ->resize(486, 355)
                ->save(base_path('assets/img/items/' . $filename), config('setting.uploadImageQuality '), 'jpg');
            $new_item->image = '/assets/img/items/' . $filename;
        }
        
        
        $new_item->is_recommended = $request->is_recommended;
        $new_item->is_new = $request->is_new;
        $new_item->is_popular = $request->is_popular;
        

        if ($request->is_veg == 'veg') {
            $new_item->is_veg = true;
        } elseif ($request->is_veg == 'nonveg') {
            $new_item->is_veg = false;
        } else {
            $new_item->is_veg = null;
        }
        
        
        $new_item->save();
        
        $new_item->zone_id = $new_item->restaurant->zone_id ? $new_item->restaurant->zone_id : null;
        
        if (isset($request->addon_category_item)) {
            $new_item->addon_categories()->sync($request->addon_category_item);
        }
        
        $new_item->save();
        
        return response()->json(['success' => true, 'message' => "Item Created"], 200);
    }
    /* added by qusay */

    // public function updateItem(Request $request)
    // {
    //     $user = Auth::user();
    //     $restaurantIds = $user->restaurants->pluck('id')->toArray();

    //     $item = Item::where('id', $request->item_id)
    //         ->where('restaurant_id', $request->store_id)
    //         ->first();

    //     if (!in_array($item->restaurant_id, $restaurantIds)) {
    //         return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
    //     }

    //     if ($item) {
    //         $item->name = $request->name;
    //         $item->price = $request->price;
    //         $item->save();

    //         $response = [
    //             'success' => true,
    //         ];
    //         return response()->json($response, 200);
    //     } else {
    //         $response = [
    //             'success' => false,
    //         ];
    //         return response()->json($response, 400);
    //     }
    // }
    
    public function updateItem(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'price' => 'required|numeric',
            'old_price' => 'nullable|numeric',
            'restaurant_id' => 'required|numeric',
            'item_category_id' => 'required|numeric',
            'image' => 'nullable|file',
            'is_recommended' => 'required|boolean',
            'is_popular' => 'required|boolean',
            'is_new' => 'required|boolean',
            'is_veg' => 'required|in:veg,nonveg,none',
            'addon_category_item' => 'nullable|array',
            'token' => 'required|string'
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();
        
        if (!in_array($request->restaurant_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
        
        $new_item = Item::where('id', $request->id)->first();
        $new_item->name = $request->name;
        $new_item->desc = $request->description;
        $new_item->price = $request->price;
        $new_item->old_price = $request->old_price == null ? 0 : $request->old_price;
        $new_item->restaurant_id = $request->restaurant_id;
        $new_item->item_category_id = $request->item_category_id;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $rand_name = time() . str_random(10);
            $filename = $rand_name . '.jpg';
            Image::make($image)
                ->resize(486, 355)
                ->save(base_path('assets/img/items/' . $filename), config('setting.uploadImageQuality '), 'jpg');
            $new_item->image = '/assets/img/items/' . $filename;
        }
        
        $new_item->is_recommended = $request->is_recommended;
        $new_item->is_new = $request->is_new;
        $new_item->is_popular = $request->is_popular;

        if ($request->is_veg == 'veg') {
            $new_item->is_veg = true;
        } elseif ($request->is_veg == 'nonveg') {
            $new_item->is_veg = false;
        } else {
            $new_item->is_veg = null;
        }
        
        
        if (isset($request->addon_category_item)) {
            $new_item->addon_categories()->sync($request->addon_category_item);
        } else {
            $new_item->addon_categories()->sync([]);   
        }
        
        $new_item->save();
        
        return response()->json(['success' => true, 'message' => "Item Updated"], 200);
    }
    // extend by aya-----------------
    public function deleteItem(Request $request, $id) {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        $item = Item::find($id);

        if (!$item || !in_array($item->restaurant_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => 'Item Not Found or Unauthorized'], 404);
        }

        try {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Item Deleted'], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

// end aya  -----------------------------
    public function getPastOrders(Request $request)
    {
        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $orders =  Order::where('restaurant_id', $request->store_id)
            ->whereIn('orderstatus_id', ['1', '2', '3', '4', '5', '6', '7', '10', '11'])
            ->with(array('restaurant' => function ($query) {
                $query->select('id', 'name', 'address');
            }))
            ->with('orderitems')
            ->orderBy('created_at', 'DESC')
            ->select('id', 'unique_order_id', 'orderstatus_id', 'total', 'created_at', 'updated_at')
            ->paginate(20);

        return response()->json($orders, 200);
    }

    public function searchOrders(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $orders = Order::where('restaurant_id', $request->store_id)
            ->where('unique_order_id', 'LIKE', "%$request->q%")
            ->with('orderitems')
            ->withCount('orderitems')
            ->take(100)
            ->get();

        return response()->json($orders, 200);
    }

    public function updateItemImage(Request $request)
    {
        // \Log::info(json_encode($request->all()));
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        $item = Item::where('id', $request->id)
            ->first();

        if (!in_array($item->restaurant_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        if ($item && $request->image != null) {
            $rand_name = time() . str_random(10);
            $filename = $rand_name . '.jpg';
            Image::make($request->image)
                ->resize(486, 355)
                ->save(base_path('assets/img/items/' . $filename), config('setting.uploadImageQuality '), 'jpg');
            $item->image = '/assets/img/items/' . $filename;
            $item->save();
        }

        return response()->json(['success' => true]);
    }

    public function getRatings(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if ($user) {
            if (!in_array($request->store_id, $restaurantIds)) {
                return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
            }

            $restaurant = Restaurant::where('id', $request->store_id)
                ->with(array('ratings' => function ($query) {
                    $query->orderBy('id', 'DESC');
                }))->first();

            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->makeHidden(['delivery_areas', 'ratings', 'schedule_data']);

            $reviews = Rating::where('restaurant_id', $restaurant->id)
                ->with('user')
                ->with(array('order' => function ($query) {
                    $query->select('id');
                }))
                ->orderBy('id', 'DESC')
                ->get();

            $reviews = $reviews->map(function ($review) {
                $review->username = $review->user->name;
                $review->order_id = $review->order->id;
                return $review->only(['id', 'username', 'rating_store', 'review_store', 'order_id']);
            });


            $response = [
                'restaurant' => $restaurant,
                'reviews' => $reviews,
            ];

            return response()->json($response, 200);
        }
    }

    public function getEarnings(Request $request)
    {
        $user = Auth::user();
        $restaurant = $user->restaurants;

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $restaurant = Restaurant::where('id', $request->store_id)->first();

        $allCompletedOrders = Order::where('restaurant_id', $restaurant->id)
            ->where('orderstatus_id', '5')
            ->get();

        $totalEarning = 0;
        settype($var, 'float');

        foreach ($allCompletedOrders as $completedOrder) {
            // $totalEarning += $completedOrder->total - $completedOrder->delivery_charge;
            $totalEarning += $completedOrder->total - ($completedOrder->delivery_charge + $completedOrder->tip_amount);
        }

        $totalEarning =  number_format((float) $totalEarning, 2, '.', '');

        $balance = RestaurantEarning::where('restaurant_id', $restaurant->id)
            ->where('is_requested', 0)
            ->first();

        if (!$balance) {
            $balanceBeforeCommission = 0;
            $balanceAfterCommission = 0;
        } else {
            $balanceBeforeCommission = $balance->amount;
            $balanceAfterCommission = ($balance->amount - ($restaurant->commission_rate / 100) * $balance->amount);
            $balanceAfterCommission = number_format((float) $balanceAfterCommission, 2, '.', '');
        }

        $payoutRequests = RestaurantPayout::where('restaurant_id', $request->store_id)->orderBy('id', 'DESC')->get();

        $minPayout = (float)config('setting.minPayout');
        if (!((float)$balanceAfterCommission > (float)$minPayout)) {
            $canRequestForPayout = false;
        } else {
            $canRequestForPayout = true;
        }


        $response = [
            'restaurant' => $restaurant,
            'totalEarning' => $totalEarning,
            'balanceBeforeCommission' => $balanceBeforeCommission,
            'balanceAfterCommission' => $balanceAfterCommission,
            'payoutRequests' => $payoutRequests,
            'canRequestForPayout' => $canRequestForPayout,
            'minPayout' => $minPayout,
        ];

        return response()->json($response, 200);
    }

    public function sendPayoutRequest(Request $request)
    {
        $user = Auth::user();

        $restaurant = $user->restaurants;

        $restaurantIds = $user->restaurants->pluck('id')->toArray();
        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $restaurant = Restaurant::where('id', $request->store_id)->first();

        $earning = RestaurantEarning::where('restaurant_id', $restaurant->id)
            ->where('is_requested', 0)
            ->first();

        $balanceAfterCommission = ($earning->amount - ($restaurant->commission_rate / 100) * $earning->amount);
        $balanceAfterCommission = number_format((float) $balanceAfterCommission, 2, '.', '');

        if ($earning) {
            $payoutRequest = new RestaurantPayout;
            $payoutRequest->restaurant_id = $restaurant->id;
            $payoutRequest->restaurant_earning_id = $earning->id;
            $payoutRequest->amount = $balanceAfterCommission;
            $payoutRequest->status = 'PENDING';
            $payoutRequest->zone_id = $restaurant->zone_id ? $restaurant->zone_id : null;

            $payoutRequest->save();
            $earning->is_requested = 1;
            $earning->restaurant_payout_id = $payoutRequest->id;
            $earning->save();

            $response = [
                'success' => true,
            ];

            return response()->json($response, 200);
        }
    }

    public function getInactiveItems(Request $request)
    {

        $user = Auth::user();
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $items = Item::where('restaurant_id', $request->store_id)
            ->where('is_active', 0)
            ->join('item_categories', function ($join) {
                $join->on('items.item_category_id', '=', 'item_categories.id');
            })
            ->orderBy('item_categories.order_column', 'asc')
            ->ordered()
            ->get(array('items.*', 'item_categories.name as category_name'));

        $items = json_decode($items, true);

        $array = [];
        foreach ($items as $item) {
            $array[$item['category_name']][] = $item;
        }

        return response()->json($array, 200);
    }
    
    /* START BY qusay */
    
    
    public function getMenuWithDetails(Request $request)
    {
        $user = Auth::user();
        
        $restaurantIds = $user->restaurants->pluck('id')->toArray();
        $store_id = $request->store_id;

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
        
        $store = Restaurant::where('id', $store_id)->first(); 


        $itemCategories = ItemCategory::whereIn('user_id', $store->users->pluck('id'))
            ->with(array('items' => function ($query) use ($request) {
                $query->where('restaurant_id', $request->store_id);
                // $query->with('addon_categories');
                $query->with([
                    'addon_categories' => function ($q) {
                        $q->with('addons');
                    }
                ]);
            }))
            ->get();


        foreach ($itemCategories as $itemCategory) {
            if ($itemCategory->user_id == $user->id) {
                $itemCategory->canEdit = true;
            } else {
                $itemCategory->canEdit = false;
            }
        }

        return response()->json($itemCategories, 200);
    }
    
    public function getAddonCategories(Request $request) {
        $user = Auth::user();
        $store_id = $request->input('store_id');
        
        $search_term = $request->get('search', null);
        
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $addonCategories = AddonCategory::where('user_id', $user->id);
        
        if ($search_term) {

            $addonCategories->where('name', 'LIKE', "%$search_term%");
                
        }
    
        $addonCategories = $addonCategories->orderBy('id', 'DESC')->get();
            
         return response()->json($addonCategories, 200);
    }
    
    public function saveAddonCategory(Request $request){
        $user_id = Auth::user()->id;
        
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:SINGLE,MULTI',
            'description' => 'nullable|string|max:80',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        // save data
        $addonCategory = new AddonCategory();

        $addonCategory->name = $request->name;
        $addonCategory->type = $request->type;
        $addonCategory->description = $request->description;
        $addonCategory->user_id = $user_id;
        $addonCategory->addon_limit = $request->addon_limit ? $request->addon_limit : 0;
        
        
        try {
            $addonCategory->save();
            if ($request->has('addons')) {
                foreach ($request->addons as $key => $addon) {
                    $new_addon = new Addon();
                    $new_addon->name = $addon['addon_name'];
                    $new_addon->price = $addon['addon_price'];
                    $new_addon->addon_category_id = $addonCategory->id;
                    $new_addon->user_id = $user_id;
                    $new_addon->save();
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' =>  'Addon Category Saved'
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' =>  $th->getMessage()
            ], 400);
        }
    }
    
    public function getAddonCategoryInformationToEdit(Request $request) {
        
        $user = Auth::user();
        $store_id = $request->store_id;
        
        $addon_category_id = $request->input('addon_category_id');
        $addonCategory = AddonCategory::where('id', $addon_category_id)->with('addons:id,name,price,addon_category_id,is_active')->first();
        
        if (!$addonCategory) {
            return response()->json(['success' => false, 'message' => "Addon Category Not Found"], 404);
        }
        
        
            
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        return response()->json($addonCategory, 200);
    }
    
    public function updateAddonCategory(Request $request) {
        
        // Validation rules
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|numeric',
            'addon_category_id' => 'required|numeric',
            'name' => 'required|string|max:255',
            'type' => 'required|in:SINGLE,MULTI',
            'description' => 'nullable|string|max:80',
        ]);
        
                // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        $addon_category_id = $request->input('addon_category_id');
        
        $addonCategory = AddonCategory::where('id', $addon_category_id)->first();
        $user = Auth::user();

        if (!$addonCategory) {
            return response()->json(['success' => false, 'message' => "Addon Category Not Found"], 404);
        }
        
        
                    
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
        
        
        DB::beginTransaction();
        $addons_ids = [];
        try{
            
            $addonCategory->name = $request->name;
            $addonCategory->type = $request->type;
            $addonCategory->description = $request->description;
            $addonCategory->addon_limit = $request->addon_limit ? $request->addon_limit : 0;
            $addonCategory->save();
            
            $addons = $request->addons;
            
            if ( $addons ) {
               foreach ( $addons as $addon ) {
                    if ( isset($addon['id']) ) {
                        
                        $addon_by_id = Addon::find($addon['id']);
                        $addon_by_id->name = $addon['addon_name'];
                        $addon_by_id->price = $addon['addon_price'];
                        $addon_by_id->is_active = $addon['is_active'];
                        $addon_by_id->save();
                        
                        $addons_ids[] = $addon['id'];
                    } else {
                        $addon_by_id = new Addon();
                        $addon_by_id->name = $addon['addon_name'];
                        $addon_by_id->price = $addon['addon_price'];
                        $addon_by_id->is_active = $addon['is_active'];
                        $addon_by_id->addon_category_id = $addonCategory->id;
                        $addon_by_id->save();
                        
                        $addons_ids[] =$addon_by_id->id;
                    }
                } 
            }
            
            
            
            Addon::where('addon_category_id', $addonCategory->id)->whereNotIn('id', $addons_ids)->delete();
            
            DB::commit();
            
            return response()->json(['success' => true, 'message' => "Addon Category Updated"], 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
        
    }
     // extend by aya ---------------------------------------------------
     public function deleteAddonCategory(Request $request, $id) {
    $user = Auth::user();
  
    $addonCategory = AddonCategory::find($id);

    if (!$addonCategory) {
        return response()->json(['success' => false, 'message' => 'Addon Category Not Found'], 404);
    }

    try {
        $addonCategory->addons()->delete();
        $addonCategory->delete();
        return response()->json(['success' => true, 'message' => 'Addon Category Deleted'], 200);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'message' => 'Error Deleting Addon Category: ' . $th->getMessage()], 400);
    }
}
    
    // ----------------------------------------------------------
    public function getAddons(Request $request) {
        $user = Auth::user();
        $search_term = $request->get('search', null);
        $store_id = $request->input('store_id', null);
        
        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $addons = Addon::where('user_id', $user->id)->with('addon_category:id,name,description');
        
        if ($search_term) {

            $addons->where('name', 'LIKE', "%$search_term%");
                
        }
        
        $addons = $addons->get();
            
        return response()->json($addons, 200);
    }
    // extend by aya--------------------------------------
    public function deleteAddon(Request $request, $id) {
        $user = Auth::user();
        $addon = Addon::find($id);
        if (!$addon) {
            return response()->json(['success' => false, 'message' => 'Addon Not Found'], 404);
        }
        try {
            $addon->delete();
            return response()->json(['success' => true, 'message' => 'Addon Deleted'], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }
    
    
    
    // -------------------------------------------------
    public function getItemsOfCategories(Request $request) {
        $user = Auth::user();
        $store_id = $request->input('store_id', null);    
        $search_term = $request->get('search', null);
               
        $restaurantIds = $user->restaurants->pluck('id')->toArray();


        if (!in_array($store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }
    
        $store = Restaurant::where('id', $store_id)->first(); 
        
        // $items = Item::where('restaurant_id', $request->store_id)->pluck('item_category_id')->toArray();
        // $filteredItemCategoryIds = array_unique($items);

        $itemCategories = ItemCategory::whereIn('user_id', $store->users->pluck('id'));
        
        if ($search_term) {

            $itemCategories->where('name', 'LIKE', "%$search_term%");
                
        }
        
        
        $itemCategories = $itemCategories->orderBy('id', 'DESC')->get();
            
        return $itemCategories;
    }
    public function createItemCategory(Request $request) {
        
       // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        
        $itemCategory = new ItemCategory();

        $itemCategory->name = $request->name;
        $itemCategory->user_id = Auth::user()->id;
        $itemCategory->save();
        
        return response()->json(['success' => true, 'data' => "Category Created"], 200);
    }
    
    public function updateItemCategory(Request $request) {
        
        // Validation rules
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'name' => 'required|string|max:255',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>  $validator->errors()->first()
            ], 422);
        }
        
        $itemCategory = ItemCategory::where('id', $request->id)->firstOrFail();
        $itemCategory->name = $request->name;
        $itemCategory->save();
        
        return response()->json(['success' => true, 'data' => "Category Updated"], 200);
    }
    
    public function toggleStatusOfItemOfCategory(Request $request) {
        $item_category_id = $request->input('item_category_id');
        
        $itemCategory = ItemCategory::where('id', $item_category_id)->first();
        if ($itemCategory) {
            $itemCategory->toggleEnable()->save();
            return response()->json(['success' => true, 'data' => "Operation Successful"], 200);
        } else {
            return response()->json(['success' => false, 'data' => "Item of category not found"], 404);
        }
    }
      // extend by aya---------------
     public function deleteItemCategory(Request $request, $id) {
    $user = Auth::user();

    $itemCategory = ItemCategory::find($id);

    if (!$itemCategory) {
        return response()->json(['success' => false, 'message' => 'Item Category Not Found'], 404);
    }

    try {
        $itemCategory->items()->delete();
        $itemCategory->delete();
        return response()->json(['success' => true, 'message' => 'Item Category Deleted'], 200);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'message' => 'Error Deleting Item Category: ' . $th->getMessage()], 400);
    }
}
    //end aya -----------------------------------------------------------------
    
    /* END BY qusay */

    public function getStorePage(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $page = Page::where('slug', $request->slug)->first();
            if ($page) {
                return response()->json($page, 200);
            } else {
                $page = null;
                return response()->json($page, 200);
            }
        }
    }

    public function toggleCategoryStatus(Request $request)
    {
        $user = Auth::user();

        $restaurantIds = $user->restaurants->pluck('id')->toArray();

        if (!in_array($request->store_id, $restaurantIds)) {
            return response()->json(['success' => false, 'message' => "Unauthorized"], 401);
        }

        $itemCategory = ItemCategory::where('id', $request->category_id)->where('user_id', $user->id)->first();
        if ($itemCategory) {
            $itemCategory->toggleEnable()->save();
            return response()->json(['success' => true, 'status' => $itemCategory->is_enabled], 200);
        }
    }

    private function isValidJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
