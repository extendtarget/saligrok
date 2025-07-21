<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Sms;
use Artisan;
use App\Item;
use App\Page;
use App\User;
use App\Zone;
use App\Addon;
use App\Order;
use App\Slide;
use Exception;
use App\Rating;
use App\Address;
use App\Setting;
use App\TodoNote;
use App\EagleView;
use Carbon\Carbon;
use App\PushNotify;
use App\Restaurant;
use App\SmsGateway;
use App\SocketPush;
use App\Orderstatus;
use App\PromoSlider;
use App\Translation;
use App\CancelReason;
use App\FoodomaaNews;
use App\ItemCategory;
use App\AddonCategory;
use App\AcceptDelivery;
use App\PaymentGateway;
use App\PopularGeoPlace;
use App\RestaurantPayout;
use App\DeliveryGuyDetail;
use App\StorePayoutDetail;
use App\RestaurantCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\DeliveryLiveLocation;
use App\ApprovePaymentHistory;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Nwidart\Modules\Facades\Module;
use Bavix\Wallet\Models\Transaction;
use App\Jobs\AssignNearestDeliveryGuy;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
class AdminController extends Controller
{
    /**
     * @return mixed
     */
    public function dashboard()
    {
        $orders = Order::orderBy('id', 'DESC')->with('orderstatus', 'restaurant')->take(6)->get();

        $users = User::orderBy('id', 'DESC')->with('roles')->take(6)->get();

        $todaysDate = Carbon::now()->format('Y-m-d');

        $orderStatusesName = '[';

        $orderStatuses = Orderstatus::get(['name'])
            ->pluck('name')
            ->toArray();
        foreach ($orderStatuses as $key => $value) {
            $orderStatusesName .= "'" . $value . "', ";
        }
        $orderStatusesName = rtrim($orderStatusesName, ' ,');
        $orderStatusesName = $orderStatusesName . ']';

        $ifAnyOrders = Order::count();
        if ($ifAnyOrders == 0) {
            $ifAnyOrders = false;
        } else {
            $ifAnyOrders = true;
        }

        $orderStatusOrders = Order::select('orderstatus_id', DB::raw('count(*) as total'))
            ->groupBy('orderstatus_id')
            ->pluck('total', 'orderstatus_id')->all();

        $orderStatusesData = '[';
        foreach ($orderStatusOrders as $key => $value) {
            if ($key == 1) {
                $orderStatusesData .= '{value:' . $value . ", name:'Order Placed'}, ";
            }
            if ($key == 2) {
                $orderStatusesData .= '{value:' . $value . ", name:'Preparing Order'}, ";
            }
            if ($key == 3) {
                $orderStatusesData .= '{value:' . $value . ", name:'Delivery Guy Assigned'}, ";
            }
            if ($key == 4) {
                $orderStatusesData .= '{value:' . $value . ", name:'Order Picked Up'}, ";
            }
            if ($key == 5) {
                $orderStatusesData .= '{value:' . $value . ", name:'Delivered'}, ";
            }
            if ($key == 6) {
                $orderStatusesData .= '{value:' . $value . ", name:'Canceled'}, ";
            }
            if ($key == 7) {
                $orderStatusesData .= '{value:' . $value . ", name:'Ready For Pick Up'}, ";
            }
            if ($key == 8) {
                $orderStatusesData .= '{value:' . $value . ", name:'Awaiting Payment'}, ";
            }
            if ($key == 9) {
                $orderStatusesData .= '{value:' . $value . ", name:'Payment Failed'}, ";
            }
        }
        $orderStatusesData = rtrim($orderStatusesData, ',');
        $orderStatusesData .= ']';

        $reviews = Rating::orderBy('id', 'DESC')->with('user', 'order.accept_delivery.user')->take(5)->get();

        if (config('setting.adminDailyTargetRevenue') != null) {
            $todayRevenue = Order::where('orderstatus_id', '5')->whereBetween('created_at', [
                Carbon::now()->startOfDay(),
                Carbon::now(),
            ])->select(DB::raw('SUM(total) AS revenue'))->first();
            $todayRevenue = $todayRevenue->revenue ? $todayRevenue->revenue : 0;
        } else {
            $todayRevenue = null;
        }

        $todayOrders = Order::where('orderstatus_id', 5)->select('id', 'orderstatus_id', 'created_at')->whereBetween('created_at', [
            Carbon::now()->startOfDay(),
            Carbon::now(),
        ])->get()->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('H');
        });


        $yesterdayOrders = Order::where('orderstatus_id', 5)->select('id', 'orderstatus_id', 'created_at')->whereBetween('created_at', [
            Carbon::now()->subDays(1)->startOfDay(),
            Carbon::now()->subDays(1),
        ])->get()->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('H');
        });


        $yesterdayOrdersOnly = [];
        foreach ($yesterdayOrders as $key => $value) {
            $yesterdayOrdersOnly[(int)$key] = count($value);
        }

        $todayOrderOnly = [];
        $todayOrderFullArr = [];

        foreach ($todayOrders as $key => $value) {
            $todayOrderOnly[(int)$key] = count($value);
        }

        for ($i = 1; $i <= 24; $i++) {
            if (!empty($todayOrderOnly[$i])) {
                $todayOrderFullArr[$i] = $todayOrderOnly[$i];
            } else {
                $todayOrderFullArr[$i] = 0;
            }
        }

        $todayOrderCount = array_sum($todayOrderOnly);
        $yesterdayOrderCount = array_sum($yesterdayOrdersOnly);


        $todoNotes = TodoNote::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();

        $walletTransactions =  Transaction::orderBy('id', 'DESC')->where('amount', '>', 0)->take(6)->get();

        $latestNews = FoodomaaNews::latest()->first();
        if ($latestNews) {
            if ($latestNews->is_read) {
                $latestNews = null;
            }
        } else {
            $latestNews = null;
        }

        return view('admin.dashboard', array(
            'orders' => $orders,
            'users' => $users,
            'todaysDate' => $todaysDate,
            'orderStatusesName' => $orderStatusesName,
            'orderStatusesData' => $orderStatusesData,
            'ifAnyOrders' => $ifAnyOrders,
            'reviews' => $reviews,
            'todayRevenue' => $todayRevenue,
            'todayOrderCount' => $todayOrderCount,
            'yesterdayOrderCount' => $yesterdayOrderCount,
            'todayOrderFullArr' => array_values($todayOrderFullArr),
            'todoNotes' => $todoNotes,
            'walletTransactions' => $walletTransactions,
            'latestNews' => $latestNews
        ));
    }

    public function manager()
    {
        return view('admin.manager');
    }

    public function users()
    {
        $roles = Role::all()->except(1);
        return view('admin.users', array(
            'roles' => $roles,
        ));
    }

    public function customers()
    {
        return view('admin.manageCustomers');
    }

    public function staffs()
    {
        return view('admin.manageStaffs');
    }

    /**
     * @param Request $request
     */
    public function saveNewUser(Request $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => \Hash::make($request->password),
            ]);

            if ($request->has('role')) {
                $user->assignRole($request->role);
            }

            if ($user->hasRole('Delivery Guy')) {

                $deliveryGuyDetails = new DeliveryGuyDetail();
                $deliveryGuyDetails->name = $request->delivery_name;
                $deliveryGuyDetails->age = $request->delivery_age;
                if ($request->hasFile('delivery_photo')) {
                    $photo = $request->file('delivery_photo');
                    $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                    Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                    $deliveryGuyDetails->photo = $filename;
                }
                $deliveryGuyDetails->description = $request->delivery_description;
                $deliveryGuyDetails->vehicle_number = $request->delivery_vehicle_number;
                if ($request->delivery_commission_rate != null) {
                    $deliveryGuyDetails->commission_rate = $request->delivery_commission_rate;
                }
                if ($request->tip_commission_rate != null) {
                    $deliveryGuyDetails->tip_commission_rate = $request->tip_commission_rate;
                }
                if ($request->cash_limit != null) {
                    $deliveryGuyDetails->cash_limit = $request->cash_limit;
                } else {
                    $deliveryGuyDetails->cash_limit = 0;
                }

                $deliveryGuyDetails->save();
                $user->delivery_guy_detail_id = $deliveryGuyDetails->id;
                $user->save();
            }

            return redirect()->back()->with(['success' => 'User Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }


    // Extend By Aya

    public function getEditUser($id)
    {
        $user = User::where('id', $id)
            ->with(['orders', 'addresses', 'delivery_guy_detail'])
            ->firstOrFail();
    
        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    
        $transactions->each(function ($transaction) {
            // تعيين القيم من meta
            $transaction->company_commission = isset($transaction->meta['company_commission']) ? (float) $transaction->meta['company_commission'] : null;
            $transaction->coupon_discount = isset($transaction->meta['coupon_discount']) ? (float) $transaction->meta['coupon_discount'] : null;
            $transaction->final_profit = isset($transaction->meta['final_profit']) ? (float) $transaction->meta['final_profit'] : null;
            $transaction->delivery_charge = isset($transaction->meta['delivery_charge']) ? (float) $transaction->meta['delivery_charge'] : null;
            $transaction->payment_type = isset($transaction->meta['payment_type']) ? $transaction->meta['payment_type'] : null;
            $transaction->paid_amount = isset($transaction->meta['paid_amount']) ? (float) $transaction->meta['paid_amount'] : null;
            $transaction->unique_order_id = null;
    
            if (isset($transaction->meta['description'])) {
                $patterns = [
                    "Driver commission and salary for COD order with: ",
                    "Driver commission and salary for order with: ",
                    "Deposit from user wallet for order: ",
                    "Delivery Tip Transaction Message : "
                ];
    
                foreach ($patterns as $pattern) {
                    if (strpos($transaction->meta['description'], $pattern) !== false) {
                        $remaining = trim(str_replace($pattern, '', $transaction->meta['description']));
                        $unique_order_id = explode(' ', $remaining)[0];
    
                        $order = Order::where('unique_order_id', $unique_order_id)
                            ->select('id', 'unique_order_id', 'final_profit', 'orderstatus_id', 'delivery_charge', 'actual_delivery_charge', 'coupon_amount', 'actual_payment_mode', 'wallet_amount', 'total', 'sub_total')
                            ->with(['accept_delivery.user.delivery_guy_detail' => function ($query) {
                                $query->select('id', 'commission_rate');
                            }])
                            ->first();
    
                        if ($order) {
                            $transaction->order = $order;
                            $transaction->unique_order_id = $order->unique_order_id;
                            $transaction->commission_rate = ($order && $order->accept_delivery && $order->accept_delivery->user && $order->accept_delivery->user->delivery_guy_detail)
                                ? $order->accept_delivery->user->delivery_guy_detail->commission_rate
                                : null;
    
                            if ($order->coupon_amount > 0 && ($transaction->coupon_discount === null || $transaction->coupon_discount != $order->coupon_amount)) {
                                $transaction->coupon_discount = (float) $order->coupon_amount;
                            }
    
                            if (!$transaction->payment_type) {
                                if ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount >= $order->sub_total) {
                                    $transaction->payment_type = 'WALLET';
                                } elseif ($order->actual_payment_mode == 'COD') {
                                    $transaction->payment_type = 'COD';
                                } elseif ($order->actual_payment_mode == 'WALLET' && $order->wallet_amount < $order->sub_total) {
                                    $transaction->payment_type = 'PARTIAL';
                                }
                            }
    
                            if ($transaction->paid_amount === null) {
                                $transaction->paid_amount = ($transaction->payment_type === 'WALLET')
                                    ? (float) $order->sub_total
                                    : ($transaction->payment_type === 'PARTIAL')
                                        ? (float) $order->wallet_amount
                                        : 0;
                            }
    
                            \Log::info('Transaction order details loaded', [
                                'transaction_id' => $transaction->id,
                                'order_id' => $order->id,
                                'unique_order_id' => $transaction->unique_order_id,
                                'delivery_charge' => $transaction->delivery_charge,
                                'company_commission' => $transaction->company_commission,
                                'coupon_discount' => $transaction->coupon_discount,
                                'final_profit' => $transaction->final_profit,
                                'payment_type' => $transaction->payment_type,
                                'paid_amount' => $transaction->paid_amount,
                                'amount' => $transaction->amount / 100
                            ]);
                        } else {
                            $transaction->order = null;
                            $transaction->commission_rate = null;
                            \Log::warning('Order not found for transaction', [
                                'transaction_id' => $transaction->id,
                                'unique_order_id' => $unique_order_id
                            ]);
                        }
                        break;
                    }
                }
            } else {
                $transaction->order = null;
                $transaction->commission_rate = null;
                \Log::info('Non-order transaction loaded', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount / 100,
                    'meta_description' => $transaction->meta['description'] ?? 'null'
                ]);
            }
        });
    
        $roles = Role::all()->except(1);
        $ratings = Rating::where('delivery_id', $user->id)->get();
        $zones = Zone::get(['id', 'name']);
    
        $arr = [
            'orders' => $user->orders,
            'user' => $user,
            'roles' => $roles,
            'rating' => deliveryAvgRating($ratings),
            'zones' => $zones,
            'transactions' => $transactions,
        ];
    
        if ($user->hasRole('Delivery Guy')) {
            $arr['fixed_salary_schedule_data'] = json_decode($user->delivery_guy_detail->fixed_salary_schedule_data ?? '{}');
        }
    
        \Log::info('User edit data prepared', [
            'user_id' => $id,
            'transaction_count' => $transactions->total()
        ]);
    
        return view('admin.editUser', $arr);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('admin.manageDeliveryGuys')->with('success', 'User deleted successfully');
    }
    /**
     * @param Request $request
     */
    // public function updateUser(Request $request)
    // {
    //     // dd($request->all());
    //     $user = User::where('id', $request->id)->with('delivery_collections', 'delivery_collections.delivery_collection_logs')->first();
    //     try {
    //         $setDeliveryNickName = false;

    //         if ($user->hasRole("Customer") && $request->roles == "Delivery Guy") {
    //             $setDeliveryNickName = true;
    //         }

    //         $user->name = $request->name;
    //         $user->email = $request->email;
    //         $user->phone = $request->phone;
    //         if ($request->has('password') && $request->password != null) {
    //             $user->password = \Hash::make($request->password);
    //             $user->session_token = Str::random(60);
    //         }

    //         if ($request->roles != null) {
    //             $user->syncRoles($request->roles);
    //         }

    //         if ($setDeliveryNickName) {
    //             $request->delivery_name = $request->name;
    //         }

    //         if ($request->zone_id != null) {
    //             $user->zone_id = $request->zone_id;
    //         }

    //         $user->save();

    //         if ($user->hasRole('Delivery Guy')) {

    //             if ($user->delivery_guy_detail == null) {

    //                 $deliveryGuyDetails = new DeliveryGuyDetail();
    //                 $deliveryGuyDetails->name = $request->delivery_name;
    //                 $deliveryGuyDetails->age = $request->delivery_age;
    //                 if ($request->hasFile('delivery_photo')) {
    //                     $photo = $request->file('delivery_photo');
    //                     $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
    //                     Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
    //                     $deliveryGuyDetails->photo = $filename;
    //                 }
    //                 $deliveryGuyDetails->description = $request->delivery_description;
    //                 $deliveryGuyDetails->vehicle_number = $request->delivery_vehicle_number;

    //                 if ($request->delivery_commission_rate != null) {
    //                     $deliveryGuyDetails->commission_rate = $request->delivery_commission_rate;
    //                 }

    //                 if ($request->tip_commission_rate != null) {
    //                     $deliveryGuyDetails->tip_commission_rate = $request->tip_commission_rate;
    //                 }

    //                 if ($request->is_notifiable == 'true') {
    //                     $deliveryGuyDetails->is_notifiable = true;
    //                 } else {
    //                     $deliveryGuyDetails->is_notifiable = false;
    //                 }

    //                 if ($request->max_accept_delivery_limit != null) {
    //                     $deliveryGuyDetails->max_accept_delivery_limit = $request->max_accept_delivery_limit;
    //                 }

    //                 if ($request->cash_limit != null) {
    //                     $deliveryGuyDetails->cash_limit = $request->cash_limit;
    //                 } else {
    //                     $deliveryGuyDetails->cash_limit = 0;
    //                 }

    //                 if ($request->fixed_salary != null) {
    //                     $deliveryGuyDetails->fixed_salary = $request->fixed_salary;
    //                 } else {
    //                     $deliveryGuyDetails->fixed_salary = 0;
    //                 }

    //                 $deliveryGuyDetails->save();
    //                 $user->delivery_guy_detail_id = $deliveryGuyDetails->id;

    //                 $user->save();
    //             } else {
    //                 $user->delivery_guy_detail->name = $request->delivery_name;
    //                 $user->delivery_guy_detail->age = $request->delivery_age;
    //                 if ($request->hasFile('delivery_photo')) {
    //                     $photo = $request->file('delivery_photo');
    //                     $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
    //                     Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
    //                     $user->delivery_guy_detail->photo = $filename;
    //                 }
    //                 $user->delivery_guy_detail->description = $request->delivery_description;
    //                 $user->delivery_guy_detail->vehicle_number = $request->delivery_vehicle_number;
    //                 if ($request->delivery_commission_rate != null) {
    //                     $user->delivery_guy_detail->commission_rate = $request->delivery_commission_rate;
    //                 }
    //                 if ($request->tip_commission_rate != null) {
    //                     $user->delivery_guy_detail->tip_commission_rate = $request->tip_commission_rate;
    //                 }
    //                 if ($request->is_notifiable == 'true') {
    //                     $user->delivery_guy_detail->is_notifiable = true;
    //                 } else {
    //                     $user->delivery_guy_detail->is_notifiable = false;
    //                 }

    //                 if ($request->max_accept_delivery_limit != null) {
    //                     $user->delivery_guy_detail->max_accept_delivery_limit = $request->max_accept_delivery_limit;
    //                 }

    //                 if ($request->cash_limit != null) {
    //                     $user->delivery_guy_detail->cash_limit = $request->cash_limit;
    //                 } else {
    //                     $user->delivery_guy_detail->cash_limit = 0;
    //                 }

    //                 if ($request->fixed_salary != null) {
    //                     $user->delivery_guy_detail->fixed_salary = $request->fixed_salary;
    //                 } else {
    //                     $user->delivery_guy_detail->fixed_salary = 0;
    //                 }

    //                 if (isset($request->fixed_salary_schedulable) && $request->fixed_salary_schedulable == true) {
    //                     $user->delivery_guy_detail->fixed_salary_schedulable = true;
    //                 } else {
    //                     $user->delivery_guy_detail->fixed_salary_schedulable = false;
    //                 }

    //                 $user->delivery_guy_detail->save();
    //             }

    //             //for delivery guy, save zone id it's delivery collection and collection logs if zone present.
    //             if ($request->zone_id != null) {
    //                 if (!empty($user->delivery_collections)) {
    //                     foreach ($user->delivery_collections as $deliveryCollection) {
    //                         $deliveryCollection->zone_id = $request->zone_id;
    //                         $deliveryCollection->save();
    //                         if (!empty($deliveryCollection->delivery_collection_logs)) {
    //                             foreach ($deliveryCollection->delivery_collection_logs as $deliveryCollectionLog) {
    //                                 $deliveryCollectionLog->zone_id = $request->zone_id;
    //                                 $deliveryCollectionLog->save();
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         return redirect(route('admin.get.editUser', $user->id) . $request->window_redirect_hash)->with(['success' => 'User Updated']);
    //     } catch (\Illuminate\Database\QueryException $qe) {
    //         return redirect()->back()->with(['message' => $qe->getMessage()]);
    //     } catch (Exception $e) {
    //         return redirect()->back()->with(['message' => $e->getMessage()]);
    //     } catch (\Throwable $th) {
    //         return redirect()->back()->with(['message' => $th->getMessage()]);
    //     }
    // }
    // Extend By Aya
    /**
     * Update user information and handle password change
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUser(Request $request)
    {
        $user = \App\User::where('id', $request->id)->with('delivery_collections', 'delivery_collections.delivery_collection_logs')->first();
    
        try {
            $setDeliveryNickName = false;
    
            if ($user->hasRole("Customer") && $request->roles == "Delivery Guy") {
                $setDeliveryNickName = true;
            }
    
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
    
            
            if ($request->has('password') && $request->password != null) {
                
                $user->password = \Hash::make($request->password);
    
              
                if ($user->auth_token) {
                    try {
                        
                        \Tymon\JWTAuth\Facades\JWTAuth::setToken($user->auth_token)->invalidate(true);
                        \Log::info('Token invalidated and blacklisted for user ID: ' . $user->id . ', token: ' . $user->auth_token);
                    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                        \Log::error('Failed to invalidate token for user ID ' . $user->id . ': ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('No auth_token found for user ID: ' . $user->id . ' during password change');
                }
    
                $user->auth_token = null;
                \Log::info('auth_token set to null for user ID: ' . $user->id . ' due to password change');
    
               
                $user->save();
    
              
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new \App\PushNotify();
                    $notify->sendPushNotification('logout', $user->id, null, 'تم تغيير كلمة السر الخاصة بك، يرجى تسجيل الدخول مرة أخرى.');
                    \Log::info('Logout notification sent to user ID: ' . $user->id);
                }
    
             
                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties(['type' => 'Password_Changed_Admin'])
                    ->log('Admin changed user password');
            }
    
            if ($request->roles != null) {
                $user->syncRoles($request->roles);
            }
    
            
            if ($setDeliveryNickName) {
                $request->delivery_name = $request->name;
            }
            
            if ($request->zone_id != null) {
                $user->zone_id = $request->zone_id;
            }
    
           
            $user->save();
    
          
            if ($user->hasRole('Delivery Guy')) {
                if ($user->delivery_guy_detail == null) {
                    $deliveryGuyDetails = new \App\DeliveryGuyDetail();
                    $deliveryGuyDetails->name = $request->delivery_name;
                    $deliveryGuyDetails->age = $request->delivery_age;
                    if ($request->hasFile('delivery_photo')) {
                        $photo = $request->file('delivery_photo');
                        $filename = time() . \Illuminate\Support\Str::random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                        \Intervention\Image\Facades\Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                        $deliveryGuyDetails->photo = $filename;
                    }
                    $deliveryGuyDetails->description = $request->delivery_description;
                    $deliveryGuyDetails->vehicle_number = $request->delivery_vehicle_number;
                    $deliveryGuyDetails->commission_rate = $request->delivery_commission_rate ?? null;
                    $deliveryGuyDetails->tip_commission_rate = $request->tip_commission_rate ?? null;
                    $deliveryGuyDetails->is_notifiable = $request->is_notifiable == 'true';
                    $deliveryGuyDetails->max_accept_delivery_limit = $request->max_accept_delivery_limit ?? null;
                    $deliveryGuyDetails->cash_limit = $request->cash_limit ?? 0;
                    $deliveryGuyDetails->fixed_salary = $request->fixed_salary ?? 0;
    
                    $deliveryGuyDetails->save();
                    $user->delivery_guy_detail_id = $deliveryGuyDetails->id;
                    $user->save();
                } else {
                    $user->delivery_guy_detail->name = $request->delivery_name;
                    $user->delivery_guy_detail->age = $request->delivery_age;
                    if ($request->hasFile('delivery_photo')) {
                        $photo = $request->file('delivery_photo');
                        $filename = time() . \Illuminate\Support\Str::random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                        \Intervention\Image\Facades\Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                        $user->delivery_guy_detail->photo = $filename;
                    }
                    $user->delivery_guy_detail->description = $request->delivery_description;
                    $user->delivery_guy_detail->vehicle_number = $request->delivery_vehicle_number;
                    $user->delivery_guy_detail->commission_rate = $request->delivery_commission_rate ?? null;
                    $user->delivery_guy_detail->tip_commission_rate = $request->tip_commission_rate ?? null;
                    $user->delivery_guy_detail->is_notifiable = $request->is_notifiable == 'true';
                    $user->delivery_guy_detail->max_accept_delivery_limit = $request->max_accept_delivery_limit ?? null;
                    $user->delivery_guy_detail->cash_limit = $request->cash_limit ?? 0;
                    $user->delivery_guy_detail->fixed_salary = $request->fixed_salary ?? 0;
                    $user->delivery_guy_detail->fixed_salary_schedulable = isset($request->fixed_salary_schedulable) && $request->fixed_salary_schedulable == true;
    
                    $user->delivery_guy_detail->save();
                }
    
                if ($request->zone_id != null) {
                    if (!empty($user->delivery_collections)) {
                        foreach ($user->delivery_collections as $deliveryCollection) {
                            $deliveryCollection->zone_id = $request->zone_id;
                            $deliveryCollection->save();
                            if (!empty($deliveryCollection->delivery_collection_logs)) {
                                foreach ($deliveryCollection->delivery_collection_logs as $deliveryCollectionLog) {
                                    $deliveryCollectionLog->zone_id = $request->zone_id;
                                    $deliveryCollectionLog->save();
                                }
                            }
                        }
                    }
                }
            }
    
            return redirect(route('admin.get.editUser', $user->id) . $request->window_redirect_hash)->with(['success' => 'User Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            \Log::error('Database error in updateUser: ' . $qe->getMessage());
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (\Exception $e) {
            \Log::error('General error in updateUser: ' . $e->getMessage());
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            \Log::error('Unexpected error in updateUser: ' . $th->getMessage());
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }
// ================

    public function updateDeliveryGuyFixedSalaryScheduleData($id, Request $request)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return redirect()->back()->with(['message' => 'Invalid User ID']);
        }
        if (!$user->hasRole('Delivery Guy')) {
            return redirect()->back()->with(['message' => 'Can only Schedule for Active delivery Guy']);
        }
        if ($user->delivery_guy_detail == null) {
            return redirect()->back()->with(['message' => 'Please Update Delivery Guy details first']);
        }
        $data = $request->except(['_token']);

        $i = 0;
        $str = '{';
        foreach ($data as $day => $times) {
            $str .= '"' . $day . '":[';
            if ($times) {
                foreach ($times as $key => $time) {
                    if ($key % 3 == 0) {
                        $t1 = $time;
                        $str .= '{"open" :' . '"' . $time . '"';
                    } elseif ($key % 3 == 1) {
                        $t2 = $time;
                        $str .= '"close" :' . '"' . $time . '"';
                    } elseif ($key % 3 == 2) {
                        $t3 = $time;
                        $str .= '"salary" :' . '"' . $time . '"}';
                    }

                    //check if last, if last then dont add comma,
                    if (count($times) != $key + 1) {
                        $str .= ',';
                    }
                }
                // dd($t1);
                if (Carbon::parse($t1) >= Carbon::parse($t2)) {
                    return redirect()->back()->with(['message' => 'Opening and Closing time is incorrect']);
                }
            } else {
                $str .= '}]';
            }

            if ($i != count($data) - 1) {
                $str .= '],';
            } else {
                $str .= ']';
            }
            $i++;
        }
        $str .= '}';
        // Enters The Data
        $user->delivery_guy_detail->fixed_salary_schedule_data = $str;
        // Saves the Data to Database
        $user->delivery_guy_detail->save();

        return redirect()->back()->with(['success' => 'Scheduling data saved successfully']);
    }

    /**
     * @param $id
     */
    public function banUser($id)
    {
        $user = User::where('id', $id)->firstOrFail();
        $user->toggleActive()->save();
        return redirect()->back()->with(['success' => 'Operation Successful']);
    }

    public function manageDeliveryGuys()
    {
        return view('admin.manageDeliveryGuys');
    }

    /**
     * @param $id
     */
    public function getManageDeliveryGuysRestaurants($id)
    {
        $user = User::where('id', $id)->first();
        if ($user->hasRole('Delivery Guy')) {
            $userRestaurants = $user->restaurants;
            $userRestaurantsIds = $user->restaurants->pluck('id')->toArray();

            $allRestaurants = Restaurant::get();

            return view('admin.manageDeliveryGuysRestaurants', array(
                'user' => $user,
                'userRestaurants' => $userRestaurants,
                'allRestaurants' => $allRestaurants,
                'userRestaurantsIds' => $userRestaurantsIds,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateDeliveryGuysRestaurants(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $user->restaurants()->sync($request->user_restaurants);
        $user->save();
        return redirect()->back()->with(['success' => 'Delivery Guy Updated']);
    }

    public function manageRestaurantOwners()
    {
        $users = User::role('Store Owner')->orderBy('id', 'DESC')->with('roles')->paginate(20);
        $count = $users->total();

        return view('admin.manageRestaurantOwners', array(
            'users' => $users,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function getManageRestaurantOwnersRestaurants($id)
    {
        $user = User::where('id', $id)->first();
        if ($user->hasRole('Store Owner')) {
            $userRestaurants = $user->restaurants;
            $userRestaurantsIds = $user->restaurants->pluck('id')->toArray();
            $allRestaurants = Restaurant::get();

            return view('admin.manageRestaurantOwnersRestaurants', array(
                'user' => $user,
                'userRestaurants' => $userRestaurants,
                'allRestaurants' => $allRestaurants,
                'userRestaurantsIds' => $userRestaurantsIds,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateManageRestaurantOwnersRestaurants(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $user->restaurants()->sync($request->user_restaurants);
        $user->save();
        return redirect()->back()->with(['success' => 'Store Owner Updated']);
    }

    public function orders()
    {
        return view('admin.orders');
    }

    /**
     * @param $order_id
     */
    public function viewOrder($order_id)
    {
        $user = auth()->user();
        if (config('setting.iHaveFoodomaaDeliveryApp') == "true") {
            $eagleView = new EagleView();
            $eagleViewData = $eagleView->getViewOrderSemiEagleViewData();
            if ($eagleViewData == null) {
                print_r("You have enabled <b>I Have Delivery App</b> in Admin Settings that requires delivery google services file to be correctly set on your server. <br><br><b>delivery-google-services.json</b> file is either missing or incorrect. <br><br> <b><u>Possible Solutions:</u> </b>
                <ul><li>Make sure the delivery-google-services.json is present on your server</li> <li>Or disable <b>I have Delivery App</b> from Admin Settings</li>");
                die();
            }
        } else {
            $eagleViewData = null;
        }

        $order = Order::where('unique_order_id', $order_id)->with('orderitems.order_item_addons', 'rating', 'razorpay_data')->first();
        // dd($order);
        $zone_id = session('selectedZone');
        if ($zone_id) {
            $users = User::role('Delivery Guy')->with('delivery_guy_detail')->where('zone_id', $zone_id)->get();
        } else {
            $users = User::role('Delivery Guy')->with('delivery_guy_detail')->get();
        }

        if ($order) {
            $activities = Activity::where('subject_id', $order->id)->with('causer', 'causer.roles')->orderBy('id', 'DESC')->get();

            $cancelReasons = [];

            if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6) {
                $cancelReasons = CancelReason::whereHas('role', function ($q) use ($user) {
                    $q->where('name', $user->roles[0]->name);
                })->get();
            }

            return view('admin.viewOrder', array(
                'order' => $order,
                'users' => $users,
                'activities' => $activities,
                'eagleViewData' => $eagleViewData,
                'cancelReasons' => $cancelReasons
            ));
        } else {
            return redirect()->route('admin.orders');
        }
    }

    public function getOrderDeliveryGuyInfo($order_id)
    {
        $order = Order::where('unique_order_id', $order_id)->with('accept_delivery')->first();
        if ($order && $order->accept_delivery && $order->accept_delivery->user->delivery_guy_detail->delivery_lat != null) {
            $response = [
                'success' => true,
                'lat' => $order->accept_delivery->user->delivery_guy_detail->delivery_lat,
                'lng' => $order->accept_delivery->user->delivery_guy_detail->delivery_long,
            ];
            return response()->json($response);
        }
        $response = ['success' => false];
        return response()->json($response);
    }
    /**
     * @param $order_id
     */
    public function printThermalBill($order_id)
    {
        $order = Order::where('unique_order_id', $order_id)->with('orderitems.order_item_addons')->first();
        $users = User::role('Delivery Guy')->get();
        if ($order) {
            return view('admin.printOrder', array(
                'order' => $order,
                'users' => $users,
            ));
        } else {
            return redirect()->route('admin.orders');
        }
    }

    public function sliders()
    {
        $sliders = PromoSlider::orderBy('id', 'DESC')->with('slides')->get();
        $count = count($sliders);
        return view('admin.sliders', array(
            'sliders' => $sliders,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function getEditSlider($id)
    {
        $restaurants = Restaurant::with('items')->get();
        $slider = PromoSlider::where('id', $id)->with('slides')->firstOrFail();
        $slides = $slider->slides;
        foreach ($slides as $slide) {
            if ($slide->model == null) {
                $link = 'Not Linked';
            }
            if ($slide->model == 1) {
                $slideRestaurant = $slide->restaurant;
                if ($slideRestaurant) {
                    $link = 'Linked to: ' . $slideRestaurant->name;
                } else {
                    $link = 'Not Linked';
                }
            }

            if ($slide->model == 2) {
                $slideItem = $slide->item;
                if ($slideItem) {
                    $link = 'Linked to item: ' . $slideItem->name . ' from Store: ' . $slideItem->restaurant->name;
                } else {
                    $link = 'Not Linked';
                }
            }

            if ($slide->model == 3) {
                if ($slide->url != null) {
                    $link = 'Linked to: ' . $slide->url;
                } else {
                    $link = 'Not Linked';
                }
            }

            $slide->link = $link;
        }
        if ($slider) {
            return view('admin.editSlider', array(
                'restaurants' => $restaurants,
                'slider' => $slider,
                'slides' => $slides,
            ));
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlider(Request $request)
    {
        $slider = PromoSlider::where('id', $request->id)->first();
        $slider->name = $request->name;
        $slider->position_id = $request->position_id;
        $slider->size = $request->size;

        $slider->save();

        return redirect()->back()->with(['success' => 'Slider Updated']);
    }

    /**
     * @param Request $request
     */
    public function createSlider(Request $request)
    {
        $sliderCount = PromoSlider::where('is_active', 1)->count();

        if ($sliderCount >= 2) {
            return redirect()->back()->with(['message' => 'Only two sliders can be created. Disbale or delete some Sliders to create more.']);
        }

        $slider = new PromoSlider();
        $slider->name = $request->name;
        $slider->location_id = '0';
        $slider->position_id = $request->position_id;
        $slider->size = $request->size;
        $slider->save();
        return redirect()->back()->with(['success' => 'New Slider Created']);
    }

    /**
     * @param $id
     */
    public function disableSlider($id)
    {
        $slider = PromoSlider::where('id', $id)->first();
        if ($slider) {
            $slider->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param $id
     */
    public function deleteSlider($id)
    {
        $slider = PromoSlider::where('id', $id)->first();
        if ($slider) {
            $slides = $slider->slides;
            foreach ($slides as $slide) {
                $slide->delete();
            }
            $slider->delete();
            return redirect()->route('admin.sliders')->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param Request $request
     */
    public function saveSlide(Request $request)
    {
        $url = url('/');
        $url = substr($url, 0, strrpos($url, '/')); //this will give url without " / "

        $slide = new Slide();
        $slide->promo_slider_id = $request->promo_slider_id;
        $slide->name = $request->name;
        $slide->url = $request->url;

        $image = $request->file('image');
        $rand_name = time() . str_random(10);
        $filename = $rand_name . '.' . $image->getClientOriginalExtension();

        Image::make($image)
            ->resize(384, 384)
            ->save(base_path('assets/img/slider/' . $filename));
        $slide->image = '/assets/img/slider/' . $filename;

        $slide->model = $request->model;
        $slide->restaurant_id = $request->restaurant_id;
        $slide->item_id = $request->item_id;
        $slide->url = $request->customUrl;

        if ($request->customUrl != null) {
            if ($request->is_locationset == 'true') {
                $slide->is_locationset = true;
            } else {
                $slide->is_locationset = false;
            }

            $slide->latitude = $request->latitude;
            $slide->longitude = $request->longitude;
            $slide->radius = $request->radius;
        }

        $slide->save();

        return redirect()->back()->with(['success' => 'New Slide Created']);
    }

    /**
     * @param $id
     */
    public function editSlide($id)
    {
        $slide = Slide::where('id', $id)->with('promo_slider')->first();

        if ($slide) {
            if ($slide->model == null) {
                $link = null;
            }
            if ($slide->model == 1) {
                $slideRestaurant = $slide->restaurant;
                if ($slideRestaurant) {
                    $link = '<b>Store - </b>' . $slideRestaurant->name;
                } else {
                    $link = null;
                }
            }

            if ($slide->model == 2) {
                $slideItem = $slide->item;
                if ($slideItem) {
                    $link = '<b>Item - </b>' . $slideItem->name . '<br><b> From Store - </b>' . $slideItem->restaurant->name;
                } else {
                    $link = null;
                }
            }

            if ($slide->model == 3) {
                if ($slide->url != null) {
                    $link = '<b>Custom URL - </b>' . $slide->url;
                } else {
                    $link = null;
                }
            }

            $restaurants = Restaurant::with('items')->get();
            return view('admin.editSlide', array(
                'slide' => $slide,
                'restaurants' => $restaurants,
                'link' => $link,
            ));
        } else {
            return redirect()->route('admin.sliders')->with(['message' => 'Slide Not Found']);
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlide(Request $request)
    {
        // dd($request->all());
        $slide = Slide::where('id', $request->id)->first();
        if ($slide) {
            $slide->name = $request->name;

            if ($request->hasFile('image')) {

                $image = $request->file('image');
                $rand_name = time() . str_random(10);
                $filename = $rand_name . '.' . $image->getClientOriginalExtension();
                Image::make($image)
                    ->resize(384, 384)
                    ->save(base_path('assets/img/slider/' . $filename));
                $slide->image = '/assets/img/slider/' . $filename;
            }

            if ($request->model != null) {
                $slide->model = $request->model;
                $slide->restaurant_id = $request->restaurant_id;
                $slide->item_id = $request->item_id;
                $slide->url = $request->customUrl;

                if ($request->customUrl != null) {
                    if ($request->is_locationset == 'true') {
                        $slide->is_locationset = true;
                    } else {
                        $slide->is_locationset = false;
                    }

                    $slide->latitude = $request->latitude;
                    $slide->longitude = $request->longitude;
                    $slide->radius = $request->radius;
                }
            }

            $slide->save();
            return redirect()->back()->with(['success' => 'Slide Updated']);
        } else {
            return redirect()->route('admin.sliders')->with(['message' => 'Slide Not Found']);
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlidePosition(Request $request)
    {
        Slide::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    /**
     * @param $id
     */
    public function deleteSlide($id)
    {
        $slide = Slide::where('id', $id)->first();
        if ($slide) {
            $slide->delete();
            return redirect()->back()->with(['success' => 'Deleted']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param $id
     */
    public function disableSlide($id)
    {
        $slide = Slide::where('id', $id)->first();
        if ($slide) {
            $slide->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    public function restaurants()
    {
        $dapCheck = false;
        if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        $pendingCount = Restaurant::orderBy('id', 'DESC')->where('is_accepted', '0')->count();
        $zones = Zone::get(['id', 'name']);
        return view('admin.restaurants', array(
            'pendingCount' => $pendingCount,
            'zones' => $zones,
            'dapCheck' => $dapCheck,
        ));
    }

    public function sortStores()
    {
        $restaurants = Restaurant::where('is_accepted', '1')->with('users.roles')->ordered()->get();
        $count = $restaurants->count();

        $dapCheck = false;
        if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        return view('admin.sortStores', array(
            'restaurants' => $restaurants,
            'count' => $count,
            'dapCheck' => $dapCheck,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateStorePosition(Request $request)
    {
        Restaurant::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    /**
     * @param $restaurant_id
     */
    public function sortMenusAndItems($restaurant_id)
    {

        $restaurant = Restaurant::where('id', $restaurant_id)->firstOrFail();

        $items = Item::where('restaurant_id', $restaurant_id)
            ->join('item_categories', function ($join) {
                $join->on('items.item_category_id', '=', 'item_categories.id');
            })
            ->orderBy('item_categories.order_column', 'asc')
            ->with('addon_categories')
            ->ordered()
            ->get(array('items.*', 'item_categories.name as category_name'));

        $itemsArr = [];
        foreach ($items as $item) {
            $itemsArr[$item['category_name']][] = $item;
        }

        // dd($itemsArr);
        $itemCategories = ItemCategory::whereHas('items', function ($query) use ($restaurant_id) {
            return $query->where('restaurant_id', $restaurant_id);
        })->ordered()->get();

        $count = 0;

        return view('admin.sortMenusAndItemsForStore', array(
            'restaurant' => $restaurant,
            'items' => $itemsArr,
            'itemCategories' => $itemCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateItemPositionForStore(Request $request)
    {
        Item::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    /**
     * @param Request $request
     */
    public function updateMenuCategoriesPositionForStore(Request $request)
    {
        ItemCategory::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    public function pendingAcceptance()
    {
        $dapCheck = false;
        if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        $pendingCount = Restaurant::orderBy('id', 'DESC')->where('is_accepted', '0')->count();
        $zones = Zone::get(['id', 'name']);

        return view('admin.restaurants', array(
            'dapCheck' => $dapCheck,
            'pendingCount' => $pendingCount,
            'zones' => $zones,
        ));
    }

    /**
     * @param $id
     */
    public function acceptRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $restaurant->toggleAcceptance()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param Request $request
     */
    public function searchRestaurants(Request $request)
    {
        $query = $request['query'];

        $restaurants = Restaurant::where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('sku', 'LIKE', '%' . $query . '%')->with('users.roles')->paginate(20);

        $count = $restaurants->total();

        $dapCheck = false;
        if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }
        $zones = Zone::get(['id', 'name']);
        return view('admin.restaurants', array(
            'restaurants' => $restaurants,
            'query' => $query,
            'count' => $count,
            'dapCheck' => $dapCheck,
            'zones' => $zones,
        ));
    }

    /**
     * @param $id
     */
    public function disableRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $restaurant->is_schedulable = false;
            $restaurant->toggleActive();
            $restaurant->save();
            if (\Illuminate\Support\Facades\Request::ajax()) {
                return response()->json(['success' => true], 200);
            }
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param $id
     */
    public function deleteRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $items = $restaurant->items;
            foreach ($items as $item) {
                $item->delete();
            }
            $restaurant->delete();
            return redirect()->route('admin.restaurants');
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param Request $request
     */
    public function saveNewRestaurant(Request $request)
    {
        $restaurant = new Restaurant();

        $restaurant->name = $request->name;
        $restaurant->description = $request->description;

        $image = $request->file('image');
        $rand_name = time() . str_random(10);
        $filename = $rand_name . '.jpg';
        Image::make($image)
            ->resize(160, 117)
            ->save(base_path('assets/img/restaurants/' . $filename), config('setting.uploadImageQuality '), 'jpg');
        $restaurant->image = '/assets/img/restaurants/' . $filename;

        $restaurant->rating = $request->rating;
        $restaurant->delivery_time = $request->delivery_time;
        $restaurant->price_range = $request->price_range;

        if ($request->is_pureveg == 'true') {
            $restaurant->is_pureveg = true;
        } else {
            $restaurant->is_pureveg = false;
        }

        if ($request->is_featured == 'true') {
            $restaurant->is_featured = true;
        } else {
            $restaurant->is_featured = false;
        }

        $restaurant->slug = str_slug($request->name) . '-' . str_random(15);
        $restaurant->certificate = $request->certificate;

        $restaurant->address = $request->address;
        $restaurant->pincode = $request->pincode;
        $restaurant->landmark = $request->landmark;
        $restaurant->latitude = $request->latitude;
        $restaurant->longitude = $request->longitude;

        $restaurant->restaurant_charges = $request->restaurant_charges;
        $restaurant->delivery_charges = $request->delivery_charges;
        $restaurant->commission_rate = $request->commission_rate;

        if ($request->has('delivery_type')) {
            $restaurant->delivery_type = $request->delivery_type;
        }

        if ($request->delivery_charge_type == 'FIXED') {
            $restaurant->delivery_charge_type = 'FIXED';
            $restaurant->delivery_charges = $request->delivery_charges;
        }
        if ($request->delivery_charge_type == 'DYNAMIC') {
            $restaurant->delivery_charge_type = 'DYNAMIC';
            $restaurant->base_delivery_charge = $request->base_delivery_charge;
            $restaurant->base_delivery_distance = $request->base_delivery_distance;
            $restaurant->extra_delivery_charge = $request->extra_delivery_charge;
            $restaurant->extra_delivery_distance = $request->extra_delivery_distance;
        }
        if ($request->delivery_radius != null) {
            $restaurant->delivery_radius = $request->delivery_radius;
        }

        $restaurant->sku = time() . str_random(10);
        $restaurant->is_active = 0;
        $restaurant->is_accepted = 1;

        $restaurant->min_order_price = $request->min_order_price;

        if ($request->zone_id != null) {
            $restaurant->zone_id = $request->zone_id;
        }

        try {
            $restaurant->save();
            return redirect()->back()->with(['success' => 'Restaurant Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function getRestaurantItems($id, Request $request)
    {
        if ($request->ajax()) {
            $search = $request->search;
            if ($search == '') {
                $items = Item::where('restaurant_id', $id)->limit(10)->get();
            } else {
                $items = Item::orderby('name', 'asc')
                    ->where('restaurant_id', $id)
                    ->select('id', 'name', 'price')
                    ->where('name', 'like', '%' . $search . '%')
                    ->limit(5)
                    ->get();
            }
            $response = array();
            foreach ($items as $item) {
                $response[] = array(
                    "id" => $item->id,
                    "text" => $item->id . " - " . $item->name,
                );
            }
            return response()->json($response, 200);
        } else {
            $items = Item::where('restaurant_id', $id)->orderBy('id', 'DESC')->with('item_category', 'restaurant')->paginate(20);
            $count = $items->total();

            $restaurants = Restaurant::all();
            $itemCategories = ItemCategory::where('is_enabled', '1')->get();
            $addonCategories = AddonCategory::all();

            return view('admin.items', array(
                'items' => $items,
                'count' => $count,
                'restaurants' => $restaurants,
                'itemCategories' => $itemCategories,
                'addonCategories' => $addonCategories,
                'restaurant_id' => $id,
            ));
        }
    }

    /**
     * @param $id
     */
    public function getEditRestaurant($id)
    {

        //Add Free Delivery Distance Column
        if (!Schema::hasColumn('restaurants', 'free_delivery_distance')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->decimal('free_delivery_distance', 5, 1)->default(5);
            });
        }

        $restaurant = Restaurant::where('id', $id)->with('users.roles', 'delivery_areas')->ordered()->firstOrFail();

        $restaurantCategories = RestaurantCategory::where('is_active', '1')->get();

        $dapCheck = false;
        if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        $adminPaymentGateways = PaymentGateway::where('is_active', '1')->get();

        $payoutData = StorePayoutDetail::where('restaurant_id', $id)->first();
        if ($payoutData) {
            $payoutData = json_decode($payoutData->data);
        } else {
            $payoutData = null;
        }

        $zones = Zone::get(['id', 'name']);

        $itemIds = $restaurant->free_items ? json_decode($restaurant->free_items) : null;
        if (!is_null($itemIds)) {
            $selectedItems = Item::whereIn('id', $itemIds)->select('id', 'name', 'price')->get();
        } else {
            $selectedItems = [];
        }

        return view('admin.editRestaurant', array(
            'restaurant' => $restaurant,
            'restaurantCategories' => $restaurantCategories,
            'schedule_data' => json_decode($restaurant->schedule_data),
            'dapCheck' => $dapCheck,
            'adminPaymentGateways' => $adminPaymentGateways,
            'payoutData' => $payoutData,
            'rating' => storeAvgRating($restaurant->ratings),
            'zones' => $zones,
            'selectedItems' => $selectedItems,
        ));
    }
    
    

    /**
     * @param Request $request
     */
    // public function updateRestaurant(Request $request)
    // {
    //   //  dd($request->all());

    //     $restaurant = Restaurant::where('id', $request->id)->with([
    //         'items' => function ($query) {
    //             $query->select('id', 'restaurant_id', 'zone_id');
    //         },
    //         'orders' => function ($query) {
    //             $query->select('id', 'restaurant_id', 'zone_id');
    //         },
    //     ])->first();

    //     if ($restaurant) {
    //         $restaurant->name = $request->name;
    //         $restaurant->description = $request->description;
            
    //         // added by qusay
    //         $restaurant->show_time_on_order_accept = isset($request->show_time_on_order_accept) ? true : false;
    //         $restaurant->is_order_need_approval_by_admin = isset($request->is_order_need_approval_by_admin) ? true : false;
    //         // added by qusay

    //         if ($request->image == null) {
    //             $restaurant->image = $request->old_image;
    //         } else {

    //             $image = $request->file('image');
    //             $rand_name = time() . str_random(10);
    //             $filename = $rand_name . '.jpg';
    //             Image::make($image)
    //                 ->resize(160, 117)
    //                 ->save(base_path('assets/img/restaurants/' . $filename), config('setting.uploadImageQuality '), 'jpg');
    //             $restaurant->image = '/assets/img/restaurants/' . $filename;
    //         }

    //         $restaurant->rating = $request->rating;
    //         $restaurant->delivery_time = $request->delivery_time;
    //         $restaurant->price_range = $request->price_range;

    //         if ($request->is_pureveg == 'true') {
    //             $restaurant->is_pureveg = true;
    //         } else {
    //             $restaurant->is_pureveg = false;
    //         }

    //         if ($request->is_featured == 'true') {
    //             $restaurant->is_featured = true;
    //         } else {
    //             $restaurant->is_featured = false;
    //         }

    //         $restaurant->certificate = $request->certificate;

    //         $restaurant->address = $request->address;
    //         $restaurant->pincode = $request->pincode;
    //         $restaurant->landmark = $request->landmark;
    //         $restaurant->latitude = $request->latitude;
    //         $restaurant->longitude = $request->longitude;

    //         $restaurant->restaurant_charges = $request->restaurant_charges;
    //         $restaurant->delivery_charges = $request->delivery_charges;
            
    //         $restaurant->commission_rate = $request->commission_rate;

    //         if ($request->has('delivery_type')) {
    //             $restaurant->delivery_type = $request->delivery_type;
    //         }

    //         if ($request->delivery_charge_type == 'FIXED') {
    //             $restaurant->delivery_charge_type = 'FIXED';
    //             $restaurant->delivery_charges = $request->delivery_charges;
    //         }
    //         if ($request->delivery_charge_type == 'DYNAMIC') {
    //             $restaurant->delivery_charge_type = 'DYNAMIC';
    //             $restaurant->base_delivery_charge = $request->base_delivery_charge;
    //             $restaurant->base_delivery_distance = $request->base_delivery_distance;
    //             $restaurant->extra_delivery_charge = $request->extra_delivery_charge;
    //             $restaurant->extra_delivery_distance = $request->extra_delivery_distance;
    //         }
    //         if ($request->delivery_radius != null) {
    //             $restaurant->delivery_radius = $request->delivery_radius;
    //         }

    //         $restaurant->min_order_price = $request->min_order_price;

    //         if ($request->is_schedulable == 'true') {
    //             $restaurant->is_schedulable = true;
    //         } else {
    //             $restaurant->is_schedulable = false;
    //         }

    //         if ($request->is_notifiable == 'true') {
    //             $restaurant->is_notifiable = true;
    //         } else {
    //             $restaurant->is_notifiable = false;
    //         }

    //         if ($request->auto_acceptable == 'true') {
    //             $restaurant->auto_acceptable = true;
    //         } else {
    //             $restaurant->auto_acceptable = false;
    //         }

    //         $restaurant->custom_message = $request->custom_message;

    //         $restaurant->custom_featured_name = $request->custom_featured_name;

    //         if ($request->accept_scheduled_orders == 'true') {
    //             $restaurant->accept_scheduled_orders = true;
    //         } else {
    //             $restaurant->accept_scheduled_orders = false;
    //         }

    //         if ($request->has('schedule_slot_buffer')) {
    //             if ($request->schedule_slot_buffer == null) {
    //                 $restaurant->schedule_slot_buffer = 30; //defaults to 30 mins
    //             } else {
    //                 $restaurant->schedule_slot_buffer = $request->schedule_slot_buffer;
    //             }
    //         } else {
    //             $restaurant->schedule_slot_buffer = $restaurant->schedule_slot_buffer ? $restaurant->schedule_slot_buffer : 0;
    //         }

    //         $restaurant->free_delivery_subtotal = $request->free_delivery_subtotal;
    //         $restaurant->free_delivery_distance = $request->free_delivery_distance;
    //         $restaurant->free_delivery_cost = $request->free_delivery_cost;
    //         $restaurant->free_delivery_comm = $request->free_delivery_comm;
    //         $restaurant->custom_message_on_list = $request->custom_message_on_list;

    //         $restaurantZone = $restaurant->zone_id;
    //         if ($restaurantZone != $request->zone_id) {
    //             //zone id has changed, so update all related tables with the new zone ID
    //             $restaurantItemIds = [];
    //             //restaurant items
    //             foreach ($restaurant->items as $restaurantItem) {
    //                 array_push($restaurantItemIds, $restaurantItem->id);
    //             }
    //             $restaurantOrderIds = [];
    //             //restaurant orders
    //             foreach ($restaurant->orders as $restaurantOrder) {
    //                 array_push($restaurantOrderIds, $restaurantOrder->id);
    //             }

    //             $restaurantEarningsIds = [];
    //             //restaurant earnings
    //             foreach ($restaurant->restaurant_earnings as $restaurantEarning) {
    //                 array_push($restaurantEarningsIds, $restaurantEarning->id);
    //             }

    //             $restaurantPayoutsIds = [];
    //             //restaurant payouts
    //             foreach ($restaurant->restaurant_payouts as $restaurantPayout) {
    //                 array_push($restaurantPayoutsIds, $restaurantPayout->id);
    //             }

    //             DB::table('items')->whereIn('id', $restaurantItemIds)->update(['zone_id' => $request->zone_id]);
    //             DB::table('orders')->whereIn('id', $restaurantOrderIds)->update(['zone_id' => $request->zone_id]);
    //             DB::table('restaurant_earnings')->whereIn('id', $restaurantEarningsIds)->update(['zone_id' => $request->zone_id]);
    //             DB::table('restaurant_payouts')->whereIn('id', $restaurantPayoutsIds)->update(['zone_id' => $request->zone_id]);

    //             $restaurant->zone_id = $request->zone_id;
    //         }

    //         //Offers
    //         if ($request->item_offers == 'true') {
    //             $restaurant->item_offers = true;
    //         } else {
    //             $restaurant->item_offers = false;
    //         }

    //         $restaurant->item_offer_min_subtotal = $request->item_offer_min_subtotal;
    //         $restaurant->free_items = json_encode($request->free_items);
    //         //End Offers

    //         try {
    //             if (isset($request->restaurant_category_restaurant)) {
    //                 $restaurant->restaurant_categories()->sync($request->restaurant_category_restaurant);
    //             }

    //             if ($request->store_payment_gateways == null) {
    //                 $restaurant->payment_gateways()->sync($request->store_payment_gateways);
    //             }

    //             if (isset($request->store_payment_gateways)) {
    //                 $restaurant->payment_gateways()->sync($request->store_payment_gateways);
    //             }

    //             $restaurant->save();

    //             try {

    //                 $restaurant->slug = Str::slug($request->store_url);
    //                 $restaurant->save();
    //             } catch (\Illuminate\Database\QueryException $qe) {
    //                 $errorCode = $qe->errorInfo[1];
    //                 if ($errorCode == 1062) {
    //                     return redirect()->back()->with(['message' => 'URL should be unique, it should not match with other store URLs']);
    //                 }
    //                 return redirect()->back()->with(['message' => $qe->getMessage()]);
    //             }
    //             // dd('here');
    //             // return redirect()->back()->with(['success' => 'Store Updated']);
    //             return redirect(route('admin.get.editRestaurant', $restaurant->id) . $request->window_redirect_hash)->with(['success' => 'Store Updated']);
    //         } catch (\Illuminate\Database\QueryException $qe) {
    //             return redirect()->back()->with(['message' => $qe->getMessage()]);
    //         } catch (Exception $e) {
    //             return redirect()->back()->with(['message' => $e->getMessage()]);
    //         } catch (\Throwable $th) {
    //             return redirect()->back()->with(['message' => $th->getMessage()]);
    //         }
    //     }
    // }
    public function  updateRestaurant   (Request $request)
{
    Log::channel('update-order')->info('Starting updateRestaurant for restaurant ID: ' . $request->id);
    Log::channel('update-order')->info('Received request data: ' . json_encode($request->all()));

    $restaurant = Restaurant::where('id', $request->id)->with([
        'items' => function ($query) {
            $query->select('id', 'restaurant_id', 'zone_id');
        },
        'orders' => function ($query) {
            $query->select('id', 'restaurant_id', 'zone_id');
        },
    ])->first();

    if (!$restaurant) {
        Log::channel('update-order')->error('Restaurant not found for ID: ' . $request->id);
        return redirect()->back()->with(['message' => 'Restaurant not found']);
    }

    Log::channel('update-order')->info('Restaurant found: ' . $restaurant->name);

    try {
    
        $restaurant->name = $request->input('name');
        $restaurant->description = $request->input('description');
        $restaurant->show_time_on_order_accept = $request->input('show_time_on_order_accept', 'false') === 'true' ? 1 : 0;
        $restaurant->is_order_need_approval_by_admin = $request->input('is_order_need_approval_by_admin', 'false') === 'true' ? 1 : 0;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $rand_name = time() . str_random(10);
            $filename = $rand_name . '.jpg';
            Image::make($image)
                ->resize(160, 117)
                ->save(base_path('assets/img/restaurants/' . $filename), config('setting.uploadImageQuality'), 'jpg');
            $restaurant->image = '/assets/img/restaurants/' . $filename;
            Log::channel('update-order')->info('Image updated: ' . $filename);
        } else {
            $restaurant->image = $request->input('old_image');
        }

        $restaurant->rating = $request->input('rating');
        $restaurant->delivery_time = $request->input('delivery_time');
        $restaurant->price_range = $request->input('price_range');
        $restaurant->is_pureveg = $request->input('is_pureveg', 'false') === 'true' ? 1 : 0;
        $restaurant->is_featured = $request->input('is_featured', 'false') === 'true' ? 1 : 0;
        $restaurant->certificate = $request->input('certificate');
        $restaurant->address = $request->input('address');
        $restaurant->pincode = $request->input('pincode');
        $restaurant->landmark = $request->input('landmark');
        $restaurant->latitude = $request->input('latitude');
        $restaurant->longitude = $request->input('longitude');
        $restaurant->restaurant_charges = $request->input('restaurant_charges');
        $restaurant->delivery_charges = $request->input('delivery_charges');
        $restaurant->commission_rate = $request->input('commission_rate');
        $restaurant->delivery_type = $request->input('delivery_type');
        $restaurant->delivery_charge_type = $request->input('delivery_charge_type');
        $restaurant->base_delivery_charge = $request->input('base_delivery_charge');
        $restaurant->base_delivery_distance = $request->input('base_delivery_distance');
        $restaurant->extra_delivery_charge = $request->input('extra_delivery_charge');
        $restaurant->extra_delivery_distance = $request->input('extra_delivery_distance');
        $restaurant->delivery_radius = $request->input('delivery_radius');
        $restaurant->min_order_price = $request->input('min_order_price');
        $restaurant->is_notifiable = $request->input('is_notifiable', 'false') === 'true' ? 1 : 0;
        $restaurant->auto_acceptable = $request->input('auto_acceptable', 'false') === 'true' ? 1 : 0;
        $restaurant->custom_message = $request->input('custom_message');
        $restaurant->custom_featured_name = $request->input('custom_featured_name');
        $restaurant->free_delivery_subtotal = $request->input('free_delivery_subtotal');
        $restaurant->free_delivery_distance = $request->input('free_delivery_distance');
        $restaurant->free_delivery_cost = $request->input('free_delivery_cost');
        $restaurant->free_delivery_comm = $request->input('free_delivery_comm');
        $restaurant->custom_message_on_list = $request->input('custom_message_on_list');
        $restaurant->item_offers = $request->input('item_offers', 'false') === 'true' ? 1 : 0;
        $restaurant->item_offer_min_subtotal = $request->input('item_offer_min_subtotal');
        $restaurant->free_items = json_encode($request->input('free_items', []));

        
        $isSchedulable = $request->input('is_schedulable', 0);
        Log::channel('update-order')->info('Received is_schedulable: ' . $isSchedulable);
        $restaurant->is_schedulable = $isSchedulable == '1' ? 1 : 0;
        Log::channel('update-order')->info('Set is_schedulable to: ' . $restaurant->is_schedulable);

        $restaurant->accept_scheduled_orders = $request->input('accept_scheduled_orders', 'false') === 'true' ? 1 : 0;
        $restaurant->schedule_slot_buffer = $request->input('schedule_slot_buffer', $restaurant->schedule_slot_buffer ?? 30);

        $restaurant->slug = Str::slug($request->input('store_url'));

        
        if ($restaurant->zone_id != $request->input('zone_id')) {
            $restaurantItemIds = $restaurant->items->pluck('id')->toArray();
            $restaurantOrderIds = $restaurant->orders->pluck('id')->toArray();
            $restaurantEarningsIds = $restaurant->restaurant_earnings->pluck('id')->toArray();
            $restaurantPayoutsIds = $restaurant->restaurant_payouts->pluck('id')->toArray();

            DB::table('items')->whereIn('id', $restaurantItemIds)->update(['zone_id' => $request->input('zone_id')]);
            DB::table('orders')->whereIn('id', $restaurantOrderIds)->update(['zone_id' => $request->input('zone_id')]);
            DB::table('restaurant_earnings')->whereIn('id', $restaurantEarningsIds)->update(['zone_id' => $request->input('zone_id')]);
            DB::table('restaurant_payouts')->whereIn('id', $restaurantPayoutsIds)->update(['zone_id' => $request->input('zone_id')]);

            $restaurant->zone_id = $request->input('zone_id');
            Log::channel('update-order')->info('Zone ID updated: ' . $request->input('zone_id'));
        }

        $paymentGateways = $request->input('store_payment_gateways', []);
        $albarakaGateway = \App\PaymentGateway::where('name', 'AlBaraka')->first();
        if ($albarakaGateway) {
            if ($request->input('enable_albaraka', 'false') === 'true') {
                if (!in_array($albarakaGateway->id, $paymentGateways)) {
                    $paymentGateways[] = $albarakaGateway->id;
                }
            } else {
                $paymentGateways = array_filter($paymentGateways, function ($id) use ($albarakaGateway) {
                    return $id != $albarakaGateway->id;
                });
            }
        }
        $restaurant->payment_gateways()->sync($paymentGateways);
        Log::channel('update-order')->info('Payment gateways synced: ' . json_encode($paymentGateways));

        
        if ($request->has('restaurant_category_restaurant')) {
            $restaurant->restaurant_categories()->sync($request->input('restaurant_category_restaurant'));
            Log::channel('update-order')->info('Restaurant categories synced');
        }

        $restaurant->save();
        Log::channel('update-order')->info('Restaurant saved successfully. is_schedulable: ' . $restaurant->is_schedulable);

        
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Log::channel('update-order')->info('Cache cleared');

        return redirect()->route('admin.get.editRestaurant', $restaurant->id . $request->input('window_redirect_hash', ''))->with(['success' => 'Store Updated']);
    } catch (\Illuminate\Database\QueryException $qe) {
        Log::channel('update-order')->error('Database error: ' . $qe->getMessage());
        if ($qe->errorInfo[1] == 1062) {
            return redirect()->back()->with(['message' => 'URL should be unique, it should not match with other store URLs']);
        }
        return redirect()->back()->with(['message' => 'Database error: ' . $qe->getMessage()]);
    } catch (\Exception $e) {
        Log::channel('update-order')->error('General error: ' . $e->getMessage());
        return redirect()->back()->with(['message' => 'General error: ' . $e->getMessage()]);
    } catch (\Throwable $th) {
        Log::channel('update-order')->error('Throwable error: ' . $th->getMessage());
        return redirect()->back()->with(['message' => 'Throwable error: ' . $th->getMessage()]);
    }
}

    /**
     * @param Request $request
     */
    public function updateSlug(Request $request)
    {
        $restaurant = Restaurant::where('id', $request->id)->firstOrFail();

        try {

            $restaurant->slug = Str::slug($request->store_url);
            $restaurant->save();
            return redirect()->back()->with(['success' => 'URL Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            $errorCode = $qe->errorInfo[1];
            if ($errorCode == 1062) {
                return redirect()->back()->with(['message' => 'URL should be unique, it should not match with other store URLs']);
            }
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    public function items(Request $request)
    {
        $restaurants = Restaurant::all();
        $itemCategories = ItemCategory::where('is_enabled', '1')->get();
        $addonCategories = AddonCategory::all();

        if ($request->has('store_id')) {
            $store_id = $request->store_id;
        } else {
            $store_id = null;
        }

        return view('admin.items', array(
            'restaurants' => $restaurants,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
            'store_id' => $store_id ? $store_id : null,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchItems(Request $request)
    {
        $query = $request['query'];

        if ($request->has('restaurant_id')) {
            $items = Item::where('restaurant_id', $request->restaurant_id)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->with('item_category', 'restaurant')
                ->paginate(20);
        } else {
            $items = Item::where('name', 'LIKE', '%' . $query . '%')
                ->with('item_category', 'restaurant')
                ->paginate(20);
        }

        $count = $items->total();

        $restaurants = Restaurant::get();
        $itemCategories = ItemCategory::where('is_enabled', '1')->get();
        $addonCategories = AddonCategory::all();

        return view('admin.items', array(
            'items' => $items,
            'count' => $count,
            'restaurants' => $restaurants,
            'query' => $query,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewItem(Request $request)
    {
        // dd($request->all());

        $item = new Item();

        $item->name = $request->name;
        $item->price = $request->price;
        $item->old_price = $request->old_price == null ? 0 : $request->old_price;
        $item->restaurant_id = $request->restaurant_id;
        $item->item_category_id = $request->item_category_id;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $rand_name = time() . str_random(10);
            $filename = $rand_name . '.jpg';
            Image::make($image)
                ->resize(486, 355)
                ->save(base_path('assets/img/items/' . $filename), config('setting.uploadImageQuality '), 'jpg');
            $item->image = '/assets/img/items/' . $filename;
        }

        if ($request->is_recommended == 'true') {
            $item->is_recommended = true;
        } else {
            $item->is_recommended = false;
        }

        if ($request->is_popular == 'true') {
            $item->is_popular = true;
        } else {
            $item->is_popular = false;
        }

        if ($request->is_new == 'true') {
            $item->is_new = true;
        } else {
            $item->is_new = false;
        }

        if ($request->is_veg == 'veg') {
            $item->is_veg = true;
        } elseif ($request->is_veg == 'nonveg') {
            $item->is_veg = false;
        } else {
            $item->is_veg = null;
        }

        $item->desc = $request->desc;

        try {
            $item->save();

            $item->zone_id = $item->restaurant->zone_id ? $item->restaurant->zone_id : null;
            $item->save();

            if (isset($request->addon_category_item)) {
                $item->addon_categories()->sync($request->addon_category_item);
            }
            return redirect()->back()->with(['success' => 'Item Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function getEditItem($id)
    {
        $item = Item::where('id', $id)->first();
        $restaurants = Restaurant::get();
        $itemCategories = ItemCategory::get();
        $addonCategories = AddonCategory::all();

        return view('admin.editItem', array(
            'item' => $item,
            'restaurants' => $restaurants,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param $id
     */
    public function disableItem($id)
    {
        $item = Item::where('id', $id)->first();
        if ($item) {
            $item->toggleActive()->save();
            if (\Illuminate\Support\Facades\Request::ajax()) {
                return response()->json(['success' => true, 'currentStatus' => $item->is_active]);
            }
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.items');
        }
    }

    /**
     * @param Request $request
     */
    public function updateItem(Request $request)
    {
        // dd($request->all());
        $item = Item::where('id', $request->id)->first();

        if ($item) {

            $item->name = $request->name;
            $item->restaurant_id = $request->restaurant_id;
            $item->item_category_id = $request->item_category_id;

            if ($request->image == null) {
                $item->image = $request->old_image;
            } else {
                $image = $request->file('image');
                $rand_name = time() . str_random(10);
                $filename = $rand_name . '.jpg';
                Image::make($image)
                    ->resize(486, 355)
                    ->save(base_path('assets/img/items/' . $filename), config('setting.uploadImageQuality '), 'jpg');
                $item->image = '/assets/img/items/' . $filename;
            }

            $item->price = $request->price;
            $item->old_price = $request->old_price == null ? 0 : $request->old_price;

            if ($request->is_recommended == 'true') {
                $item->is_recommended = true;
            } else {
                $item->is_recommended = false;
            }

            if ($request->is_popular == 'true') {
                $item->is_popular = true;
            } else {
                $item->is_popular = false;
            }

            if ($request->is_new == 'true') {
                $item->is_new = true;
            } else {
                $item->is_new = false;
            }

            if ($request->is_veg == 'veg') {
                $item->is_veg = true;
            } elseif ($request->is_veg == 'nonveg') {
                $item->is_veg = false;
            } else {
                $item->is_veg = null;
            }

            $item->desc = $request->desc;

            $item->zone_id = $item->restaurant->zone_id ? $item->restaurant->zone_id : null;

            try {
                $item->save();

                if ($request->addon_category_item == null) {
                    $item->addon_categories()->sync($request->addon_category_item);
                }

                if (isset($request->addon_category_item)) {
                    $item->addon_categories()->sync($request->addon_category_item);
                }

                return redirect()->back()->with(['success' => 'Item Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th->getMessage()]);
            }
        }
    }

    public function removeItemImage($id)
    {
        $item = Item::where('id', $id)->firstOrFail();

        $item->image = null;
        $item->save();
        return redirect()->back()->with(['success' => 'Item image removed']);
    }


 // aya
        public function removeItem($id)
        {
             $item  = Item::find($id);
              $item ->delete();

            return redirect()->back()->with(['success' => 'Item Deleted']);
            
            
        }
        // ==================

    public function addonCategories()
    {
        $addonCategories = AddonCategory::orderBy('id', 'DESC')->paginate(20);
        $addonCategories->loadCount('addons');

        $count = $addonCategories->total();

        return view('admin.addonCategories', array(
            'addonCategories' => $addonCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchAddonCategories(Request $request)
    {
        $query = $request['query'];

        $addonCategories = AddonCategory::where('name', 'LIKE', '%' . $query . '%')->paginate(20);
        $addonCategories->loadCount('addons');

        $count = $addonCategories->total();

        return view('admin.addonCategories', array(
            'addonCategories' => $addonCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewAddonCategory(Request $request)
    {
        $addonCategory = new AddonCategory();

        $addonCategory->name = $request->name;
        $addonCategory->type = $request->type;
        $addonCategory->description = $request->description;
        $addonCategory->user_id = Auth::user()->id;
        $addonCategory->addon_limit = $request->addon_limit ? $request->addon_limit : 0;

        try {
            $addonCategory->save();
            if ($request->has('addon_names')) {
                foreach ($request->addon_names as $key => $addon_name) {
                    $addon = new Addon();
                    $addon->name = $addon_name;
                    $addon->price = $request->addon_prices[$key];
                    $addon->user_id = Auth::user()->id;
                    $addon->addon_category_id = $addonCategory->id;
                    $addon->save();
                }
            }
            return redirect()->route('admin.editAddonCategory', $addonCategory->id)->with(['success' => 'Addon Category Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    public function newAddonCategory()
    {
        return view('admin.newAddonCategory');
    }

    /**
     * @param $id
     */
    public function deleteAddon($id)
    {
        $addon = Addon::find($id);
        $addon->delete();

        return redirect()->back()->with(['success' => 'Addon Deleted']);
    }

    /**
     * @param $id
     */
    public function getEditAddonCategory($id)
    {
        $addonCategory = AddonCategory::where('id', $id)->with('addons')->first();

        return view('admin.editAddonCategory', array(
            'addonCategory' => $addonCategory,
            'addons' => $addonCategory->addons,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateAddonCategory(Request $request)
    {
        // dd($request->all());
        $addonCategory = AddonCategory::where('id', $request->id)->first();

        if ($addonCategory) {

            $addonCategory->name = $request->name;
            $addonCategory->type = $request->type;
            $addonCategory->description = $request->description;
            $addonCategory->addon_limit = $request->addon_limit ? $request->addon_limit : 0;

            try {
                $addonCategory->save();
                $addons_old = $request->input('addon_old');
                if ($request->has('addon_old')) {
                    foreach ($addons_old as $ad) {
                        $addon_old_update = Addon::find($ad['id']);
                        $addon_old_update->name = $ad['name'];
                        $addon_old_update->price = $ad['price'];
                        $addon_old_update->user_id = Auth::user()->id;
                        $addon_old_update->save();
                    }
                }

                if ($request->addon_names) {
                    foreach ($request->addon_names as $key => $addon_name) {
                        $addon = new Addon();
                        $addon->name = $addon_name;
                        $addon->price = $request->addon_prices[$key];
                        $addon->addon_category_id = $addonCategory->id;
                        $addon->user_id = Auth::user()->id;
                        $addon->save();
                    }
                }

                return redirect()->back()->with(['success' => 'Addon Category Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th->getMessage()]);
            }
        }
    }

    public function addons()
    {

        $addons = Addon::orderBy('id', 'DESC')->with('addon_category')->paginate(20);
        $count = $addons->total();

        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchAddons(Request $request)
    {
        $query = $request['query'];

        $addons = Addon::where('name', 'LIKE', '%' . $query . '%')->with('addon_category')->paginate(20);

        $count = $addons->total();

        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewAddon(Request $request)
    {
        // dd($request->all());
        $addon = new Addon();

        $addon->name = $request->name;
        $addon->price = $request->price;
        $addon->user_id = Auth::user()->id;
        $addon->addon_category_id = $request->addon_category_id;

        try {
            $addon->save();
            return redirect()->back()->with(['success' => 'Addon Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function getEditAddon($id)
    {
        $addon = Addon::where('id', $id)->first();
        $addonCategories = AddonCategory::all();
        return view('admin.editAddon', array(
            'addon' => $addon,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateAddon(Request $request)
    {
        $addon = Addon::where('id', $request->id)->first();

        if ($addon) {

            $addon->name = $request->name;
            $addon->price = $request->price;
            $addon->addon_category_id = $request->addon_category_id;

            try {
                $addon->save();
                return redirect()->back()->with(['success' => 'Addon Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th->getMessage()]);
            }
        }
    }

    /**
     * @param $id
     */
    public function disableAddon($id)
    {
        $addon = Addon::where('id', $id)->firstOrFail();
        if ($addon) {
            $addon->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->back()->with(['message' => 'Something Went Wrong']);
        }
    }

    /**
     * @param $id
     */
    public function addonsOfAddonCategory($id)
    {
        $addons = Addon::orderBy('id', 'DESC')->where('addon_category_id', $id)->with('addon_category')->paginate(20);
        $count = $addons->total();
        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    public function itemcategories()
    {
        $itemCategories = ItemCategory::orderBy('id', 'DESC')->with('user')->paginate(20);
        $itemCategories->loadCount('items');

        $count = count($itemCategories);

        return view('admin.itemcategories', array(
            'itemCategories' => $itemCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function createItemCategory(Request $request)
    {
        $itemCategory = new ItemCategory();

        $itemCategory->name = $request->name;
        $itemCategory->user_id = Auth::user()->id;

        try {
            $itemCategory->save();
            return redirect()->back()->with(['success' => 'Category Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function disableCategory($id)
    {
        $itemCategory = ItemCategory::where('id', $id)->first();
        if ($itemCategory) {
            $itemCategory->toggleEnable()->save();
            if (\Illuminate\Support\Facades\Request::ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.itemcategories');
        }
    }

    /**
     * @param Request $request
     */
    public function updateItemCategory(Request $request)
    {
        $itemCategory = ItemCategory::where('id', $request->id)->firstOrFail();
        $itemCategory->name = $request->name;
        $itemCategory->save();
        return redirect()->back()->with(['success' => 'Operation Successful']);
    }

    public function pages()
    {
        $pages = Page::all();
        return view('admin.pages', array(
            'pages' => $pages,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewPage(Request $request)
    {
        $page = new Page();
        $page->name = $request->name;
        $page->slug = Str::slug($request->slug, '-');
        $page->body = $request->body;

        try {
            $page->save();
            return redirect()->back()->with(['success' => 'New Page Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function getEditPage($id)
    {
        $page = Page::where('id', $id)->first();

        if ($page) {
            return view('admin.editPage', array(
                'page' => $page,
            ));
        } else {
            return redirect()->route('admin.pages');
        }
    }

    /**
     * @param Request $request
     */
    public function updatePage(Request $request)
    {
        $page = Page::where('id', $request->id)->first();

        if ($page) {
            $page->name = $request->name;
            $page->slug = Str::slug($request->slug, '-');
            $page->body = $request->body;
            try {
                $page->save();
                return redirect()->back()->with(['success' => 'Page Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th->getMessage()]);
            }
        } else {
            return redirect()->route('admin.pages');
        }
    }

    /**
     * @param $id
     */
    public function deletePage($id)
    {
        $page = Page::where('id', $id)->first();
        if ($page) {
            $page->delete();
            return redirect()->back()->with(['success' => 'Deleted']);
        } else {
            return redirect()->route('admin.pages');
        }
    }

    public function restaurantpayouts()
    {
        $count = RestaurantPayout::count();

        $restaurantPayouts = RestaurantPayout::orderBy('id', 'DESC')->paginate(20);

        return view('admin.restaurantPayouts', array(
            'restaurantPayouts' => $restaurantPayouts,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function viewRestaurantPayout($id)
    {
        $restaurantPayout = RestaurantPayout::where('id', $id)->first();

        if ($restaurantPayout) {

            $payoutData = StorePayoutDetail::where('restaurant_id', $restaurantPayout->restaurant->id)->first();
            if ($payoutData) {
                $payoutData = json_decode($payoutData->data);
            } else {
                $payoutData = null;
            }

            return view('admin.viewRestaurantPayout', array(
                'restaurantPayout' => $restaurantPayout,
                'payoutData' => $payoutData,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateRestaurantPayout(Request $request)
    {
        $restaurantPayout = RestaurantPayout::where('id', $request->id)->first();

        if ($restaurantPayout) {
            $restaurantPayout->status = $request->status;
            $restaurantPayout->transaction_mode = $request->transaction_mode;
            $restaurantPayout->transaction_id = $request->transaction_id;
            $restaurantPayout->message = $request->message;
            try {
                $restaurantPayout->save();
                return redirect()->back()->with(['success' => 'Restaurant Payout Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th->getMessage()]);
            }
        }
    }
// Permisions Aya=============
    public function fixUpdateIssues()
    {
        try {

            $duplicates = AcceptDelivery::whereIn('order_id', function ($query) {
                $query->select('order_id')->from('accept_deliveries')->groupBy('order_id')->havingRaw('count(*) > 1');
            })->get();

            foreach ($duplicates as $duplicate) {

                if ($duplicate->is_completed == 0 && ($duplicate->order->orderstatus_id == 5 || $duplicate->order->orderstatus_id == 6)) {
                    //just delete
                    $duplicate->delete(); //delete the duplicate entry in db
                }

                if ($duplicate->is_completed == 0 && $duplicate->order->orderstatus_id == 3) {
                    //delete and change orderstatus to 2
                    $duplicate->order->orderstatus_id = 2; //change order status to not delivery assigned
                    $duplicate->order->save(); //save the order
                    $duplicate->delete(); //delete the duplicate entry in db
                }
            }

            // ** MIGRATE ** //
            //first migrate the db if any new db are avaliable...
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Artisan::call('module:migrate', [
                '--force' => true,
            ]);
            // ** MIGRATE END ** //

            // ** SETTINGS ** //
            $data = file_get_contents(storage_path('data/data.json'));
            $data = json_decode($data);
            $dbSet = [];
            foreach ($data as $s) {
                //check if the setting key already exists, if exists, do nothing..
                $settingAlreadyExists = Setting::where('key', $s->key)->first();
                //else create an array of settings which doesnt exists...
                if (!$settingAlreadyExists) {
                    $dbSet[] = [
                        'key' => $s->key,
                        'value' => $s->value,
                    ];
                }
            }
            //insert new settings keys into settings table.
            DB::table('settings')->insert($dbSet);
            // ** SETTINGS END ** //

            // ** PAYMENTGATEWAYS ** //
            // check if paystack is installed
            $hasPayStack = PaymentGateway::where('name', 'PayStack')->first();
            if (!$hasPayStack) {
                //if not, then install new payment gateway "PayStack"
                $payStackPaymentGateway = new PaymentGateway();
                $payStackPaymentGateway->name = 'PayStack';
                $payStackPaymentGateway->description = 'PayStack Payment Gateway';
                $payStackPaymentGateway->is_active = 0;
                $payStackPaymentGateway->save();
            }
            // check if razorpay is installed
            $hasRazorPay = PaymentGateway::where('name', 'Razorpay')->first();
            if (!$hasRazorPay) {
                //if not, then install new payment gateway "Razorpay"
                $razorPayPaymentGateway = new PaymentGateway();
                $razorPayPaymentGateway->name = 'Razorpay';
                $razorPayPaymentGateway->description = 'Razorpay Payment Gateway';
                $razorPayPaymentGateway->is_active = 0;
                $razorPayPaymentGateway->save();
            }
            // ** END PAYMENTGATEWAYS ** //

            $hasPayMongo = PaymentGateway::where('name', 'PayMongo')->first();
            if (!$hasPayMongo) {
                //if not, then install new payment gateway "PayMongo"
                $payMongoPaymentGateway = new PaymentGateway();
                $payMongoPaymentGateway->name = 'PayMongo';
                $payMongoPaymentGateway->description = 'PayMongo Payment Gateway';
                $payMongoPaymentGateway->is_active = 0;
                $payMongoPaymentGateway->save();
            }

            $hasMercadoPago = PaymentGateway::where('name', 'MercadoPago')->first();
            if (!$hasMercadoPago) {
                //if not, then install new payment gateway "MercadoPago"
                $mercadoPagoPaymentGateway = new PaymentGateway();
                $mercadoPagoPaymentGateway->name = 'MercadoPago';
                $mercadoPagoPaymentGateway->description = 'MercadoPago Payment Gateway';
                $mercadoPagoPaymentGateway->is_active = 0;
                $mercadoPagoPaymentGateway->save();
            }

            $hasPaytm = PaymentGateway::where('name', 'Paytm')->first();
            if (!$hasPaytm) {
                //if not, then install new payment gateway "MercadoPago"
                $paytmPaymentGateway = new PaymentGateway();
                $paytmPaymentGateway->name = 'Paytm';
                $paytmPaymentGateway->description = 'Paytm Payment Gateway';
                $paytmPaymentGateway->is_active = 0;
                $paytmPaymentGateway->save();
            }

            $hasFlutterwave = PaymentGateway::where('name', 'Flutterwave')->first();
            if (!$hasFlutterwave) {
                $flutterwavePaymentGateway = new PaymentGateway();
                $flutterwavePaymentGateway->name = 'Flutterwave';
                $flutterwavePaymentGateway->description = 'Flutterwave Payment Gateway';
                $flutterwavePaymentGateway->is_active = 0;
                $flutterwavePaymentGateway->save();
            }

            $hasKhalti = PaymentGateway::where('name', 'Khalti')->first();
            if (!$hasKhalti) {
                $khaltiPaymentGateway = new PaymentGateway();
                $khaltiPaymentGateway->name = 'Khalti';
                $khaltiPaymentGateway->description = 'Khalti Payment Gateway';
                $khaltiPaymentGateway->is_active = 0;
                $khaltiPaymentGateway->save();
            }

            $hasMsg91 = SmsGateway::where('gateway_name', 'MSG91')->first();
            if (!$hasMsg91) {
                //if not, then install new sms gateway gateway "MSG91"
                $msg91Gateway = new SmsGateway();
                $msg91Gateway->gateway_name = 'MSG91';
                $msg91Gateway->save();
            }

            $hasTwilio = SmsGateway::where('gateway_name', 'TWILIO')->first();
            if (!$hasTwilio) {
                //if not, then install new sms gateway gateway "TWILIO"
                $twilioGateway = new SmsGateway();
                $twilioGateway->gateway_name = 'TWILIO';
                $twilioGateway->save();
            }

            // ** ORDERSTATUS ** //
            DB::table('orderstatuses')->truncate();
            DB::statement("INSERT INTO `orderstatuses` (`id`, `name`) VALUES (1, 'Order Placed'), (2, 'Preparing Order'), (3, 'Delivery Guy Assigned'), (4, 'Order Picked Up'), (5, 'Delivered'), (6, 'Canceled'), (7, 'Ready For Pick Up'), (8, 'Awaiting Payment'), (9, 'Payment Failed'), (10, 'Scheduled Order'), (11, 'Confirmed Order')");

            /* Save new keys for translations languages */
            $langData = file_get_contents(storage_path('language/english.json'));
            $a1 = json_decode($langData, true);

            $translations = Translation::all();

            foreach ($translations as $translation) {
                //get the existing data of a translated language
                $a2 = json_decode($translation->data, true);

                //get the difference between the master file and the existing translation, and get the non-existing key
                $diff = array_diff_key($a1, $a2);

                //merge the non existing keys with the existing translation
                $merged = array_merge($a2, $diff);

                //save the translation
                $translation->data = json_encode($merged);
                $translation->save();
            }

            /* Create Permissions */
            Schema::disableForeignKeyConstraints();
            DB::table('permissions')->truncate();
            Schema::enableForeignKeyConstraints();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Permission::create(['name' => 'dashboard_view', 'readable_name' => 'View Admin Dashboard']);

            Permission::create(['name' => 'stores_view', 'readable_name' => 'View Stores']);
            Permission::create(['name' => 'stores_edit', 'readable_name' => 'Edit Stores']);
            Permission::create(['name' => 'stores_sort', 'readable_name' => 'Sort Stores']);
            Permission::create(['name' => 'approve_stores', 'readable_name' => 'Approve Pending Stores']);
            Permission::create(['name' => 'stores_add', 'readable_name' => 'Add Store']);
            Permission::create(['name' => 'login_as_store_owner', 'readable_name' => 'Login as Store Owner']);

            Permission::create(['name' => 'addon_categories_view', 'readable_name' => 'View Addon Categories']);
            Permission::create(['name' => 'addon_categories_edit', 'readable_name' => 'Edit Addon Categories']);
            Permission::create(['name' => 'addon_categories_add', 'readable_name' => 'Add Addon Category']);

            Permission::create(['name' => 'addons_view', 'readable_name' => 'View Addons']);
            Permission::create(['name' => 'addons_edit', 'readable_name' => 'Edit Addons']);
            Permission::create(['name' => 'addons_add', 'readable_name' => 'Add Addon']);
            Permission::create(['name' => 'addons_actions', 'readable_name' => 'Addon Actions']);

            Permission::create(['name' => 'menu_categories_view', 'readable_name' => 'View Menu Categories']);
            Permission::create(['name' => 'menu_categories_edit', 'readable_name' => 'Edit Menu Categories']);
            Permission::create(['name' => 'menu_categories_add', 'readable_name' => 'Add Menu Category']);
            Permission::create(['name' => 'menu_categories_actions', 'readable_name' => 'Menu Category Actions']);

            Permission::create(['name' => 'items_view', 'readable_name' => 'View Items']);
            Permission::create(['name' => 'items_edit', 'readable_name' => 'Edit Items']);
            Permission::create(['name' => 'items_add', 'readable_name' => 'Add Item']);
            Permission::create(['name' => 'items_actions', 'readable_name' => 'Item Actions']);

            Permission::create(['name' => 'all_users_view', 'readable_name' => 'View All Users']);
            Permission::create(['name' => 'all_users_edit', 'readable_name' => 'Edit All Users']);
            Permission::create(['name' => 'all_users_wallet', 'readable_name' => 'Users Wallet Transactions']);

            Permission::create(['name' => 'delivery_guys_view', 'readable_name' => 'View Delivery Guy Users']);
            Permission::create(['name' => 'delivery_guys_manage_stores', 'readable_name' => 'Manage Delivery Guy Stores']);

            Permission::create(['name' => 'store_owners_view', 'readable_name' => 'View Store Owner Users']);
            Permission::create(['name' => 'store_owners_manage_stores', 'readable_name' => 'Manage Store Owner Stores']);

            Permission::create(['name' => 'order_view', 'readable_name' => 'View Orders']);
            Permission::create(['name' => 'live_orders_view', 'readable_name' => 'View Live Orders']);
            Permission::create(['name' => 'order_actions', 'readable_name' => 'Order Actions']);

            Permission::create(['name' => 'promo_sliders_manage', 'readable_name' => 'Manage Promo Sliders']);
            Permission::create(['name' => 'store_category_sliders_manage', 'readable_name' => 'Manage Category Sliders']);
            Permission::create(['name' => 'coupons_manage', 'readable_name' => 'Manage Coupons']);
            Permission::create(['name' => 'pages_manage', 'readable_name' => 'Manage Pages']);
            Permission::create(['name' => 'popular_location_manage', 'readable_name' => 'Manage Popular Geo Locations']);
            Permission::create(['name' => 'send_notification_manage', 'readable_name' => 'Send Notifications']);
            Permission::create(['name' => 'store_payouts_manage', 'readable_name' => 'Manage Store Payouts']);
            Permission::create(['name' => 'translations_manage', 'readable_name' => 'Manage Translations']);
            Permission::create(['name' => 'delivery_collection_manage', 'readable_name' => 'Manage Delivery Collection']);
            Permission::create(['name' => 'delivery_collection_logs_view', 'readable_name' => 'View Delivery Collection Logs']);
            Permission::create(['name' => 'wallet_transactions_view', 'readable_name' => 'View Wallet Transactions']);
            Permission::create(['name' => 'reports_view', 'readable_name' => 'View Reports']);

            Permission::create(['name' => 'settings_manage', 'readable_name' => 'Manage Settings']);

            Permission::create(['name' => 'login_as_customer', 'readable_name' => 'Login as Customer']);
            //Permission::create(['name' => 'Assign_Delivery_Guy', 'readable_name' => 'Assign Delivery Guy']);
            // Extend By Aya
            Permission::create(['name' => 'assign_delivery_guy', 'readable_name' => 'Assigned Delivery Guy']);
            Permission::create(['name' => 'get_nearest_delivery_guy', 'readable_name' => 'Get Nearest Delivery Guy']);
            Permission::create(['name' => 'manage_order', 'readable_name' => 'Manage Order']);
            Permission::create(['name' => 'order_financials', 'readable_name' => 'Order Financials']);
            Permission::create([
    'name' => 'Showing_entries',
    'readable_name' => 'Showing Entries',
    'guard_name' => 'web',
]);
            $user = User::where('id', '1')->first();
            $user->givePermissionTo(Permission::all());
            /* END Create Permission and add all permissions to Admin */

            /*restaurant zone fixes */
            $restaurants = Restaurant::with('items', 'orders', 'restaurant_earnings', 'restaurant_payouts')->get();
            foreach ($restaurants as $restaurant) {
                $restaurantItemIds = [];
                //restaurant items
                foreach ($restaurant->items as $restaurantItem) {
                    array_push($restaurantItemIds, $restaurantItem->id);
                }
                $restaurantOrderIds = [];
                //restaurant orders
                foreach ($restaurant->orders as $restaurantOrder) {
                    array_push($restaurantOrderIds, $restaurantOrder->id);
                }

                $restaurantEarningsIds = [];
                //restaurant earnings
                foreach ($restaurant->restaurant_earnings as $restaurantEarning) {
                    array_push($restaurantEarningsIds, $restaurantEarning->id);
                }

                $restaurantPayoutsIds = [];
                //restaurant payouts
                foreach ($restaurant->restaurant_payouts as $restaurantPayout) {
                    array_push($restaurantPayoutsIds, $restaurantPayout->id);
                }
                
                DB::table('items')->whereIn('id', $restaurantItemIds)->update(['zone_id' => $restaurant->zone_id]);
                
                
                /* START qusay */
                $batchSize = 2000; // Adjust this number according to your database limits

                foreach (array_chunk($restaurantOrderIds, $batchSize) as $batch) {
                    DB::table('orders')
                        ->whereIn('id', $batch)
                        ->update(['zone_id' => $restaurant->zone_id]);
                }
                // DB::table('orders')->whereIn('id', $restaurantOrderIds)->update(['zone_id' => $restaurant->zone_id]); // this line
                /* END qusay */
                
                
                DB::table('restaurant_earnings')->whereIn('id', $restaurantEarningsIds)->update(['zone_id' => $restaurant->zone_id]);
                DB::table('restaurant_payouts')->whereIn('id', $restaurantPayoutsIds)->update(['zone_id' => $restaurant->zone_id]);
            }
            /* END */

            /* END Save new keys for translations languages */
            /** CLEAR LARAVEL CACHES **/
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            /** END CLEAR LARAVEL CACHES **/

            return redirect()->back()->with(['success' => 'Operation Successful']);
        } catch (\Illuminate\Database\QueryException $qe) {
    dd($qe->getMessage()); 
    return redirect()->back()->with(['message' => $qe->getMessage()]);
} catch (Exception $e) {
    dd($e->getMessage()); 
    return redirect()->back()->with(['message' => $e->getMessage()]);
} catch (\Throwable $th) {
    dd($th->getMessage()); 
    return redirect()->back()->with(['message' => $th->getMessage()]);
}
    }

    /**
     * @param Request $request
     */
    // public function addMoneyToWallet(Request $request)
    // {
    //     $user = User::where('id', $request->user_id)->first();

    //     if ($user) {
    //         try {
    //             $user->deposit($request->add_amount * 100, ['description' => $request->add_amount_description]);

    //             $alert = new PushNotify();
    //             $alert->sendWalletAlert($request->user_id, $request->add_amount, $request->add_amount_description, $type = 'deposit');

    //             return redirect()->back()->with(['success' => config('setting.walletName') . ' Updated']);
    //         } catch (\Illuminate\Database\QueryException $qe) {
    //             return redirect()->back()->with(['message' => $qe->getMessage()]);
    //         } catch (Exception $e) {
    //             return redirect()->back()->with(['message' => $e->getMessage()]);
    //         } catch (\Throwable $th) {
    //             return redirect()->back()->with(['message' => $th->getMessage()]);
    //         }
    //     }
    // }
 

    // /**
    //  * @param Request $request
    //  */
    // public function substractMoneyFromWallet(Request $request)
    // {
    //     $user = User::where('id', $request->user_id)->first();

    //     if ($user) {
    //         if ($user->balanceFloat * 100 >= $request->substract_amount * 100) {
    //             try {
    //                 $user->withdraw($request->substract_amount * 100, ['description' => $request->substract_amount_description]);

    //                 $alert = new PushNotify();
    //                 $alert->sendWalletAlert($request->user_id, $request->substract_amount, $request->substract_amount_description, $type = 'withdraw');

    //                 return redirect()->back()->with(['success' => config('setting.walletName') . ' Updated']);
    //             } catch (\Illuminate\Database\QueryException $qe) {
    //                 return redirect()->back()->with(['message' => $qe->getMessage()]);
    //             } catch (Exception $e) {
    //                 return redirect()->back()->with(['message' => $e->getMessage()]);
    //             } catch (\Throwable $th) {
    //                 return redirect()->back()->with(['message' => $th->getMessage()]);
    //             }
    //         } else {
    //             return redirect()->back()->with(['message' => 'Substract amount is less that the user balance amount.']);
    //         }
    //     }
    // }
    
 
        //  Extend By Aya 
         /**
         * Add money to a user's wallet and record the transaction
         *
         * @param Request $request
         * @return \Illuminate\Http\RedirectResponse
         */
        public function addMoneyToWallet(Request $request)
        {
            $user = User::where('id', $request->user_id)->first();
        
            if (!$user) {
                \Log::error('User not found for wallet deposit', ['user_id' => $request->user_id]);
                return redirect()->back()->with(['message' => 'User not found.']);
            }
        
            $amount = $request->add_amount * 100; // Convert to cents
            $description = $request->add_amount_description ?? 'Admin wallet deposit';
        
            DB::beginTransaction();
            try {
                $transaction = $user->deposit($amount, ['description' => $description]);
                \Log::info('Wallet deposit successful', [
                    'user_id' => $user->id,
                    'amount' => $request->add_amount,
                    'new_balance' => $user->balanceFloat,
                    'description' => $description,
                    'transaction_id' => $transaction->id
                ]);
        
                // Send notification if enabled
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $alert = new PushNotify();
                    $alert->sendWalletAlert($user->id, $request->add_amount, $description, 'deposit');
                    \Log::info('Wallet deposit notification sent', ['user_id' => $user->id]);
                }
        
                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'type' => 'Wallet_Deposit',
                        'amount' => $request->add_amount,
                        'new_balance' => $user->balanceFloat
                    ])
                    ->log('Wallet deposit by admin');
        
                DB::commit();
                return redirect()->back()->with(['success' => config('setting.walletName') . ' Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                DB::rollback();
                \Log::error('Database error in addMoneyToWallet', [
                    'user_id' => $user->id,
                    'error' => $qe->getMessage()
                ]);
                return redirect()->back()->with(['message' => 'A database error occurred.']);
            } catch (Exception $e) {
                DB::rollback();
                \Log::error('General error in addMoneyToWallet', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                DB::rollback();
                \Log::error('Unexpected error in addMoneyToWallet', [
                    'user_id' => $user->id,
                    'error' => $th->getMessage()
                ]);
                return redirect()->back()->with(['message' => 'An unexpected error occurred.']);
            }
        }
        /**
         * Subtract money from a user's wallet and record the transaction, allowing negative balance only for Delivery Guy
         *
         * @param Request $request
         * @return \Illuminate\Http\RedirectResponse
         */
        public function substractMoneyFromWallet(Request $request)
        {
            $user = User::where('id', $request->user_id)->first();
        
            if (!$user) {
                \Log::error('User not found for wallet withdrawal', ['user_id' => $request->user_id]);
                return redirect()->back()->with(['message' => 'User not found.']);
            }
        
            $amount = $request->substract_amount * 100; // Convert to cents
            $description = $request->substract_amount_description ?? 'Admin wallet withdrawal';
        
            DB::beginTransaction();
            try {
                // Check if the user is a Delivery Guy
                $isDeliveryGuy = $user->hasRole('Delivery Guy');
        
                // Perform withdrawal based on user role
                if ($isDeliveryGuy) {
                    // Allow negative balance for Delivery Guy using forceWithdraw
                    $transaction = $user->forceWithdraw($amount, ['description' => $description]);
                } else {
                    // For non-Delivery Guy, check balance and use regular withdraw
                    if ($user->balanceFloat * 100 < $amount) {
                        \Log::error('Insufficient funds for non-Delivery Guy wallet withdrawal', [
                            'user_id' => $user->id,
                            'amount' => $request->substract_amount,
                            'balance' => $user->balanceFloat
                        ]);
                        throw new \Bavix\Wallet\Exceptions\InsufficientFunds('Insufficient funds in wallet.');
                    }
                    $transaction = $user->withdraw($amount, ['description' => $description]);
                }
        
                \Log::info('Wallet withdrawal successful', [
                    'user_id' => $user->id,
                    'amount' => $request->substract_amount,
                    'new_balance' => $user->balanceFloat,
                    'description' => $description,
                    'transaction_id' => $transaction->id,
                    'is_delivery_guy' => $isDeliveryGuy
                ]);
        
                // Send notification if enabled
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $alert = new PushNotify();
                    $alert->sendWalletAlert($user->id, $request->substract_amount, $description, 'withdraw');
                    \Log::info('Wallet withdrawal notification sent', ['user_id' => $user->id]);
                }
        
                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'type' => 'Wallet_Withdrawal',
                        'amount' => $request->substract_amount,
                        'new_balance' => $user->balanceFloat,
                        'is_delivery_guy' => $isDeliveryGuy
                    ])
                    ->log('Wallet withdrawal by admin');
        
                DB::commit();
                return redirect()->back()->with(['success' => config('setting.walletName') . ' Updated']);
            } catch (\Bavix\Wallet\Exceptions\BalanceIsEmpty $e) {
                \Log::error('Balance is empty in substractMoneyFromWallet', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                DB::rollback();
                return redirect()->back()->with(['message' => 'Wallet is empty.']);
            } catch (\Bavix\Wallet\Exceptions\InsufficientFunds $e) {
                \Log::error('Insufficient funds in substractMoneyFromWallet', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                DB::rollback();
                return redirect()->back()->with(['message' => 'Insufficient funds in wallet.']);
            } catch (\Illuminate\Database\QueryException $qe) {
                \Log::error('Database error in substractMoneyFromWallet', [
                    'user_id' => $user->id,
                    'error' => $qe->getMessage()
                ]);
                DB::rollback();
                return redirect()->back()->with(['message' => 'A database error occurred.']);
            } catch (\Exception $e) {
                \Log::error('General error in substractMoneyFromWallet', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                DB::rollback();
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                \Log::error('Unexpected error in substractMoneyFromWallet', [
                    'user_id' => $user->id,
                    'error' => $th->getMessage()
                ]);
                DB::rollback();
                return redirect()->back()->with(['message' => 'An unexpected error occurred.']);
            }
        }
        // =====================================
    public function walletTransactions()
    {
        $count = $transactions = Transaction::count();

        $transactions = Transaction::orderBy('id', 'DESC')->paginate(20);

        return view('admin.viewAllWalletTransactions', array(
            'transactions' => $transactions,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchWalletTransactions(Request $request)
    {
        $query = $request['query'];

        $transactions = Transaction::where('uuid', 'LIKE', '%' . $query . '%')
            ->paginate(20);

        $count = $transactions->total();

        return view('admin.viewAllWalletTransactions', array(
            'transactions' => $transactions,
            'query' => $query,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     * @param TranslationHelper $translationHelper
     */
    // public function cancelOrderFromAdmin(Request $request, TranslationHelper $translationHelper)
    // {
    //     $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
    //     $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

    //     $order = Order::where('id', $request->order_id)->first();

    //     $user = User::where('id', $order->user_id)->first();
    //     $admin = Auth::user();

    //     try {
    //         if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6) {
    //             //5 = completed, 6 = canceled

    //             //check refund type

    //             switch ($request->refund_type) {
    //                 case 'NOREFUND':
    //                     if ($order->wallet_amount != null) {
    //                         $user->deposit($order->wallet_amount * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
    //                     }
    //                     activity()
    //                         ->performedOn($order)
    //                         ->causedBy($admin)
    //                         ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled');
    //                     break;

    //                 case 'FULL':
    //                     $user->deposit($order->total * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
    //                     activity()
    //                         ->performedOn($order)
    //                         ->causedBy($admin)
    //                         ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled with Full Refund');

    //                     break;

    //                 case 'HALF':
    //                     $user->deposit($order->total / 2 * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
    //                     activity()
    //                         ->performedOn($order)
    //                         ->causedBy($admin)
    //                         ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled with Half Refund');
    //                     break;
    //             }

    //             //cancel order
    //             $order->orderstatus_id = 6; //6 means canceled..
    //             $order->refund_type = $request->refund_type;
    //             $static_reason = 'تم إلغاء طلبك من قبل شركة التوصيل بسبب: ';  // added by qusay
    //             $order->cancel_reason = $static_reason . $request->cancel_reason; // added by qusay
    //             $order->save();

    //             //throw notification to user
    //             if (config('setting.enablePushNotificationOrders') == 'true') {
    //                 $notify = new PushNotify();
    //                 $notify->sendPushNotification('6', $order->user_id, $order->unique_order_id);
    //             }

    //             return redirect()->back()->with(['success' => 'Operation Successful']);
    //         }
    //     } catch (\Illuminate\Database\QueryException $qe) {
    //         return redirect()->back()->with(['message' => $qe->getMessage()]);
    //     } catch (Exception $e) {
    //         return redirect()->back()->with(['message' => $e->getMessage()]);
    //     } catch (\Throwable $th) {
    //         return redirect()->back()->with(['message' => $th->getMessage()]);
    //     }
    // }
    // Extend By Aya
    public function cancelOrderFromAdmin(Request $request, TranslationHelper $translationHelper)
    {
        $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
        $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);
    
        $order = Order::where('id', $request->order_id)->first();
        $user = User::where('id', $order->user_id)->first();
        $admin = Auth::user();
    
        try {
            if ($order->orderstatus_id != 5 && $order->orderstatus_id != 6) {
                // إعادة المبلغ المدفوع فقط بناءً على نوع الاسترداد
                switch ($request->refund_type) {
                    case 'NOREFUND':
                        if ($order->wallet_amount != null) {
                            $user->deposit($order->wallet_amount * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                        }
                        activity()
                            ->performedOn($order)
                            ->causedBy($admin)
                            ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled');
                        break;
    
                    case 'FULL':
                        // إعادة المبلغ المدفوع من المحفظة فقط
                        if ($order->wallet_amount != null) {
                            $user->deposit($order->wallet_amount * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                        }
                        activity()
                            ->performedOn($order)
                            ->causedBy($admin)
                            ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled with Full Refund');
                        break;
    
                    case 'HALF':
                        // إعادة نصف المبلغ المدفوع من المحفظة
                        if ($order->wallet_amount != null) {
                            $halfAmount = $order->wallet_amount / 2;
                            $user->deposit($halfAmount * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
                        }
                        activity()
                            ->performedOn($order)
                            ->causedBy($admin)
                            ->withProperties(['type' => 'Order_Canceled'])->log('Order canceled with Half Refund');
                        break;
                }
    
                // تحديث حالة الطلب إلى Canceled
                $order->orderstatus_id = 6;
                $order->refund_type = $request->refund_type;
                $static_reason = 'تم إلغاء طلبك من قبل شركة التوصيل بسبب: ';
                $order->cancel_reason = $static_reason . $request->cancel_reason;
                $order->save();
    
                // إرسال إشعار إلغاء
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('6', $order->user_id, $order->unique_order_id);
                }
    
                return redirect()->back()->with(['success' => 'Operation Successful']);
            }
        } catch (\Illuminate\Database\QueryException $qe) {
            \Log::error('Database error in cancelOrderFromAdmin: ' . $qe->getMessage());
            return redirect()->back()->with(['message' => 'A database error occurred. Please try again.']);
        } catch (Exception $e) {
            \Log::error('General error in cancelOrderFromAdmin: ' . $e->getMessage());
            return redirect()->back()->with(['message' => 'An error occurred. Please try again.']);
        } catch (\Throwable $th) {
            \Log::error('Unexpected error in cancelOrderFromAdmin: ' . $th->getMessage());
            return redirect()->back()->with(['message' => 'An unexpected error occurred. Please try again.']);
        }
    }
    public function deliverOrder(Request $request, TranslationHelper $translationHelper)
{
    $keys = ['deliveryCommissionMessage', 'deliveryTipTransactionMessage'];
    $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

    $deliveryUser = auth()->user();

    if ($deliveryUser->hasRole('Delivery Guy')) {
        DB::beginTransaction();
        try {
            $order = Order::where('id', $request->order_id)
                ->with(['restaurant' => function ($query) {
                    $query->select('id', 'name', 'description', 'address', 'pincode', 'latitude', 'longitude', 'commission_rate', 'zone_id');
                }])
                ->with(['orderitems' => function ($query) {
                    $query->with(['order_item_addons' => function ($subQuery) {
                        $subQuery->select('id', 'orderitem_id', 'addon_name', 'addon_price');
                    }])->select('id', 'order_id', 'name', 'price', 'quantity', 'item_id');
                }])
                ->with(['user' => function ($query) {
                    $query->select('id', 'name', 'phone', 'email', 'zone_id', 'referred_by');
                }])
                ->lockForUpdate()
                ->first();

            $adjusted_total = $order->total;
            $adjusted_delivery_charge = $order->delivery_charge;
            $discounted_delivery_charge = $order->delivery_charge;
            $remaining_amount = 0;
            $is_wallet_payment = false;
            $coupon_amount = null;

            if ($order->coupon_name) {
                $coupon = Coupon::where('code', $order->coupon_name)->first();
                if ($coupon) {
                    if ($coupon->is_used_for_delivery) {
                        $original_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                        if ($coupon->delivery_discount_percentage) {
                            $discount_percentage = $coupon->delivery_discount_percentage;
                            $discount_factor = $discount_percentage > 1 ? $discount_percentage / 100 : $discount_percentage;
                            $discounted_delivery_charge = $original_delivery_charge * (1 - $discount_factor);
                            $coupon_amount = $original_delivery_charge - $discounted_delivery_charge;
                        } else {
                            $coupon_amount = $coupon->amount ?? $order->coupon_amount;
                            $discounted_delivery_charge = max(0, $original_delivery_charge - $coupon_amount);
                        }
                        $adjusted_delivery_charge = $coupon->discount_type === 'FREE' && ($coupon->delivery_discount_percentage ?? 100) == 100 ? 0 : $discounted_delivery_charge;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $order->is_free_delivery = $adjusted_delivery_charge == 0;
                        $order->actual_delivery_charge = $original_delivery_charge;
                    } else {
                        $coupon_amount = $coupon->amount ?? $order->coupon_amount;
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                } else {
                    $coupon_amount = $order->coupon_amount ?? 0;
                    if ($order->is_free_delivery) {
                        $discounted_delivery_charge = 0;
                        $adjusted_delivery_charge = 0;
                        $order->delivery_charge = $adjusted_delivery_charge;
                        $order->actual_delivery_charge = $order->actual_delivery_charge ?? $order->delivery_charge;
                    } else {
                        $adjusted_total = max(0, $order->total - $coupon_amount);
                    }
                }
            }

            if ($order->actual_payment_mode == 'WALLET') {
                $is_wallet_payment = true;
                if ($order->wallet_amount >= $adjusted_total) {
                    $remaining_amount = 0;
                } else {
                    $remaining_amount = $adjusted_total - $order->wallet_amount;
                }
            } else {
                $is_wallet_payment = false;
                $remaining_amount = $adjusted_total;
            }

            if ($order && $this->canPerformAction($deliveryUser, $order)) {
                $deliveryGuyCommissionRate = $deliveryUser->delivery_guy_detail->commission_rate;
                $order->driver_order_commission_rate = $deliveryGuyCommissionRate;
                $commission = 0;

                // حساب نسبة السائق (ستذهب إلى محفظة الشركة)
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

                $order->driver_order_commission_amount = $commission;

                // حساب إكرامية السائق (ستذهب نقديًا إلى السائق)
                if (!empty($deliveryUser->delivery_guy_detail) && $deliveryUser->delivery_guy_detail->tip_commission_rate && !is_null($deliveryUser->delivery_guy_detail->tip_commission_rate)) {
                    $tip_amount = $deliveryUser->delivery_guy_detail->tip_commission_rate / 100 * $order->tip_amount;
                    $tip_amount = number_format((float) $tip_amount, 2, '.', '');
                    $order->driver_order_tip_amount = $tip_amount;
                } else {
                    $tip_amount = null;
                    $order->driver_order_tip_amount = 0;
                }

                // التحقق من رمز التسليم
                if (config('setting.enableDeliveryPin') == 'true') {
                    if ($order->delivery_pin != strtoupper($request->delivery_pin)) {
                        $singleOrder = $order;
                        $singleOrderData = (object) [];
                        $singleOrderData->delivery_pin_error = true;
                        $singleOrderData->commission = number_format((float) $commission, 2, '.', '');
                        $singleOrderData->tip_amount = $tip_amount;
                        $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                        $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                        $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                        $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                        $singleOrderData->is_wallet_payment = $is_wallet_payment;
                        $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');

                        $singleOrder->setAppends([]);
                        $singleOrderArray = $singleOrder->toArray();
                        $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);

                        DB::rollBack();
                        return response()->json($singleOrderArray);
                    }
                }

                // إدارة برنامج الإحالة
                if (config('setting.enableReferAndEarn') == "true" && config('setting.referralBonusType') == "order") {
                    if ($order->user->orders->where('orderstatus_id', 5)->count() == 0) {
                        $referredByUser = User::where('id', $order->user->referred_by)->with('wallet')->first();
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

                // تحديث حالة الطلب
                $order->orderstatus_id = '5';
                $order->commission_rate = $order->restaurant->commission_rate;
                $order->commission_amount = $order->sub_total * $order->commission_rate / 100;
                $order->driver_salary = $deliveryUser->delivery_guy_detail->fixed_salary;

                // إيداع نسبة السائق في محفظة الشركة (المستخدم الإداري id=1)
                $companyUser = User::where('id', 1)->firstOrFail();
                if ($commission > 0) {
                    $companyUser->deposit($commission * 100, [
                        'description' => "Driver commission for order {$order->unique_order_id}"
                    ]);
                }

                // حساب الربح النهائي للشركة
                $final_profit = $order->commission_amount; // الربح يعتمد فقط على عمولة المطعم
                if ($final_profit < 0) {
                    \Log::warning("Negative company profit for order {$order->id}: {$final_profit}");
                    $final_profit = 0;
                }
                $order->final_profit = $final_profit;

                $order->restaurant_net_amount = $order->sub_total + $order->restaurant_charge - $order->coupon_amount - $order->commission_amount;

                $order->save();

                // تحديث حالة التوصيل
                $completeDelivery = AcceptDelivery::where('order_id', $order->id)->first();
                $completeDelivery->is_complete = true;
                $completeDelivery->save();

                // جلب بيانات الطلب النهائية
                $singleOrder = Order::where('id', $order->id)
                    ->with(['restaurant' => function ($query) {
                        $query->select('id', 'name', 'description', 'address', 'pincode', 'latitude', 'longitude', 'commission_rate', 'zone_id');
                    }])
                    ->with(['orderitems' => function ($query) {
                        $query->with(['order_item_addons' => function ($subQuery) {
                            $subQuery->select('id', 'orderitem_id', 'addon_name', 'addon_price');
                        }])->select('id', 'order_id', 'name', 'price', 'quantity', 'item_id');
                    }])
                    ->with(['user' => function ($query) {
                        $query->select('id', 'name', 'phone', 'email', 'zone_id', 'referred_by');
                    }])
                    ->first();

                // إرسال إشعار التسليم للعميل
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('5', $order->user_id, $order->unique_order_id);
                }

                // تحديث أرباح المطعم
                $restaurant_earning = RestaurantEarning::where('restaurant_id', $order->restaurant->id)
                    ->where('is_requested', 0)
                    ->first();
                if (!$restaurant_earning) {
                    $restaurant_earning = new RestaurantEarning();
                    $restaurant_earning->restaurant_id = $order->restaurant->id;
                }
                $restaurant_earning->amount += $order->sub_total + $order->restaurant_charge + $order->tax_amount - $order->coupon_amount;
                $restaurant_earning->net_amount += $order->restaurant_net_amount;
                $restaurant_earning->zone_id = $order->restaurant->zone_id ? $order->restaurant->zone_id : null;
                $restaurant_earning->save();

                // تسجيل المبالغ النقدية التي يجمعها السائق (COD أو المتبقي من WALLET)
                if ($order->actual_payment_mode == 'COD' || ($order->actual_payment_mode == 'WALLET' && $remaining_amount > 0)) {
                    $delivery_collection = DeliveryCollection::where('user_id', $completeDelivery->user_id)->first();
                    if (!$delivery_collection) {
                        $delivery_collection = new DeliveryCollection();
                        $delivery_collection->user_id = $completeDelivery->user_id;
                    }
                    $delivery_collection->amount += $remaining_amount > 0 ? $remaining_amount : $order->payable;
                    $delivery_collection->zone_id = $completeDelivery->user->zone_id ? $completeDelivery->user->zone_id : null;
                    $delivery_collection->save();

                    // تسجيل إكرامية السائق كمبلغ نقدي مستحق
                    if ($tip_amount > 0) {
                        \Log::info("Driver {$deliveryUser->id} to receive cash tip: {$tip_amount} for order {$order->id}");
                    }
                }

                // إرسال فاتورة للعميل
                $this->sendInvoiceToCustomer($order);

                // تسجيل النشاط
                activity()
                    ->performedOn($order)
                    ->causedBy($deliveryUser)
                    ->withProperties(['type' => 'Order_Delivered'])
                    ->log('Order delivered');

                // إعداد الاستجابة
                $singleOrderData = (object) [];
                $singleOrderData->commission = number_format((float) ($commission ?? 0), 2, '.', '');
                $singleOrderData->tip_amount = $tip_amount;
                $singleOrderData->adjusted_total = number_format((float) $adjusted_total, 2, '.', '');
                $singleOrderData->delivery_charge = number_format((float) $adjusted_delivery_charge, 2, '.', '');
                $singleOrderData->discounted_delivery_charge = number_format((float) $discounted_delivery_charge, 2, '.', '');
                $singleOrderData->remaining_amount = number_format((float) $remaining_amount, 2, '.', '');
                $singleOrderData->is_wallet_payment = $is_wallet_payment;
                $singleOrderData->coupon_amount = number_format((float) $coupon_amount, 2, '.', '');

                $singleOrder->setAppends([]);
                $singleOrderArray = $singleOrder->toArray();
                $singleOrderArray = array_merge($singleOrderArray, (array) $singleOrderData);

                \Log::info('Deliver Order Response', [
                    'order_id' => $order->id,
                    'actual_payment_mode' => $order->actual_payment_mode,
                    'wallet_amount' => $order->wallet_amount,
                    'total' => $order->total,
                    'adjusted_total' => $adjusted_total,
                    'delivery_charge' => $adjusted_delivery_charge,
                    'discounted_delivery_charge' => $discounted_delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'commission' => $commission,
                    'commission_rate' => $deliveryGuyCommissionRate,
                    'remaining_amount' => $remaining_amount,
                    'is_wallet_payment' => $is_wallet_payment,
                    'coupon_amount' => $coupon_amount,
                    'orderitems_count' => count($singleOrderArray['orderitems'] ?? []),
                    'orderitems' => $singleOrderArray['orderitems'] ?? [],
                ]);

                DB::commit();
                return response()->json($singleOrderArray);
            } else {
                abort(401, 'Order cancelled/completed not found or cannot view order.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Deliver Order Error: ' . $e->getMessage());
            abort(500, $e->getMessage());
        }
    }
}
    
// ======================================================
    
    /*
    *  added by qusay 
    * this function to show the order for the Restaurant
    */
    public function acceptOrderAsAdmin(Request $request) {
        $order_id = $request->id;
        $order = Order::find($order_id);
        $order->is_accepted_by_admin = true;
        $order->save();
        
        return redirect()->back()->with(array('success' => 'Order Accepted as Admin'));
    }
    // added by qusay

    /**
     * @param Request $request
     */
    // public function acceptOrderFromAdmin(Request $request)
    // {
    //     $user = Auth::user();
    //     $order = Order::where('id', $request->id)->with('restaurant')->first();

    //     if ($order->orderstatus_id == '1' || $order->orderstatus_id == '11') {
    //         $order->orderstatus_id = 2;
            
    //         /* added by qusay */
    //         if ( $order->restaurant->show_time_on_order_accept ) {
    //             $delay_before_driver_visibility = $request->delay_before_driver_visibility;

    //             if ( $delay_before_driver_visibility == 'other' ) {
    //                 $delay_before_driver_visibility = (int)$request->custom_time;
    //             }
                
    //             $delay_before_driver_visibility -= 15;
                
    //             $wating_date = new \DateTime();
    //             $wating_date->modify("+$delay_before_driver_visibility minutes");
    //             $order->delay_before_driver_visibility = $wating_date->format('Y-m-d H:i:s');
    //         }
    //         /* added by qusay */
            
    //         $order->save();

            
    //         if (config('setting.enablePushNotificationOrders') == 'true') {
    //             //to user
    //             $notify = new PushNotify();
    //             $notify->sendPushNotification('2', $order->user_id, $order->unique_order_id);
    //         }


    //         //send notification and sms to delivery only when order type is Delivery...
    //         if ($order->delivery_type == '1') {
    //             if (config('setting.autoAssignNearestDeliveryGuy') == "true") {
    //                 if (config('setting.autoAssignDeliveryGuyDelay') != null || config('setting.autoAssignDeliveryGuyDelay') > 0) {
    //                     // sendPushNotificationToDelivery($order->restaurant->id, $order);
    //                     // sendSmsToDelivery($order->restaurant->id);
    //                 }
    //                 $prepTime = Carbon::parse($order->prep_time);
    //                 if (is_null($prepTime)) {
    //                     $delay = config('setting.autoAssignDeliveryGuyDelay') != null ? config('setting.autoAssignDeliveryGuyDelay') * 60 : 0;
    //                 } else {
    //                     $delay = $prepTime->subMinutes(1)->diffInSeconds(Carbon::now());
    //                 }
    //                 if ($delay < 0) $delay = 0;
    //                 AssignNearestDeliveryGuy::dispatch($order)->delay($delay);
    //             } else {
    //                 /* changed by qusay */
    //                 if ( $order->restaurant->show_time_on_order_accept == false ) {
    //                     sendPushNotificationToDelivery($order->restaurant->id, $order);
    //                     sendSmsToDelivery($order->restaurant->id);
    //                 }
    //                 /* changed by qusay */
    //             }
    //         }

    //         activity()
    //             ->performedOn($order)
    //             ->causedBy($user)
    //             ->withProperties(['type' => 'Order_Accepted'])->log('Order accepted');

    //         return redirect()->back()->with(array('success' => 'Order Accepted'));
    //     } else {
    //         return redirect()->back()->with(array('message' => 'Something went wrong.'));
    //     }
    // }
    // extend by aya 
    /**
     * Accept an order from admin and optionally schedule visibility to delivery drivers.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acceptOrderFromAdmin(Request $request)
    {
        $user = Auth::user();
        $order = Order::where('id', $request->id)->with('restaurant')->first();

        if (!$order) {
            Log::error('Order not found for ID: ' . $request->id, ['user_id' => $user->id]);
            return redirect()->back()->with(['message' => 'Order not found.']);
        }

        if ($order->orderstatus_id != 1 && $order->orderstatus_id != 11) {
            Log::warning('Invalid order status for acceptance', [
                'order_id' => $order->id,
                'status' => $order->orderstatus_id,
                'user_id' => $user->id
            ]);
            return redirect()->back()->with(['message' => 'Order cannot be accepted due to its current status.']);
        }

        DB::beginTransaction();
        try {
            $order->orderstatus_id = 2;

            // Handle scheduling if restaurant supports delayed visibility
            if ($order->restaurant->show_time_on_order_accept) {
                $delay_before_driver_visibility = $request->delay_before_driver_visibility;

                // Handle custom time if 'other' is selected
                if ($delay_before_driver_visibility === 'other') {
                    $custom_time = (int) $request->custom_time;
                    if ($custom_time < 15) {
                        Log::error('Invalid custom time provided', [
                            'order_id' => $order->id,
                            'custom_time' => $request->custom_time,
                            'user_id' => $user->id
                        ]);
                        return redirect()->back()->with(['message' => 'Custom time must be at least 15 minutes.']);
                    }
                    $delay_before_driver_visibility = $custom_time;
                } else {
                    $delay_before_driver_visibility = (int) $delay_before_driver_visibility;
                }

                // Ensure delay is not negative after subtracting 15 minutes
                $adjusted_delay = max(0, $delay_before_driver_visibility - 15);

                $waiting_date = new \DateTime();
                $waiting_date->modify("+$adjusted_delay minutes");
                $order->delay_before_driver_visibility = $waiting_date->format('Y-m-d H:i:s');

                \Log::info('Order scheduled for driver visibility', [
                    'order_id' => $order->id,
                    'delay_minutes' => $adjusted_delay,
                    'visibility_time' => $order->delay_before_driver_visibility,
                    'user_id' => $user->id
                ]);
            }

            $order->save();

            // Send push notification to customer
            if (config('setting.enablePushNotificationOrders') == 'true') {
                $notify = new PushNotify();
                $notify->sendPushNotification('2', $order->user_id, $order->unique_order_id);
                \Log::info('Push notification sent to customer', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id
                ]);
            }

            // Handle delivery notifications for Delivery orders
            if ($order->delivery_type == '1') {
                if (config('setting.autoAssignNearestDeliveryGuy') == 'true') {
                    $prepTime = Carbon::parse($order->prep_time);
                    $delay = is_null($prepTime)
                        ? (config('setting.autoAssignDeliveryGuyDelay') ?? 0) * 60
                        : $prepTime->subMinutes(1)->diffInSeconds(Carbon::now());
                    $delay = max(0, $delay);

                    AssignNearestDeliveryGuy::dispatch($order)->delay($delay);
                    \Log::info('Auto-assign delivery job dispatched', [
                        'order_id' => $order->id,
                        'delay_seconds' => $delay
                    ]);
                } else {
                    if (!$order->restaurant->show_time_on_order_accept) {
                        sendPushNotificationToDelivery($order->restaurant->id, $order);
                        sendSmsToDelivery($order->restaurant->id);
                        \Log::info('Delivery notifications sent', [
                            'order_id' => $order->id,
                            'restaurant_id' => $order->restaurant->id
                        ]);
                    } else {
                        \Log::info('Delivery notifications skipped due to delayed visibility', [
                            'order_id' => $order->id,
                            'visibility_time' => $order->delay_before_driver_visibility
                        ]);
                    }
                }
            }

            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Order_Accepted'])
                ->log('Order accepted');

            \Log::info('Order accepted successfully', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'new_status' => $order->orderstatus_id
            ]);

            DB::commit();
            return redirect()->back()->with(['success' => 'Order accepted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to accept order', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with(['message' => 'An error occurred while accepting the order.']);
        }
    }

    /**
     * @param Request $request
     */
// public function assignDeliveryFromAdmin(Request $request)
//     {
//         // Validate request inputs
//         $request->validate([
//             'order_id' => 'required|exists:orders,id',
//             'user_id' => 'required|exists:users,id',
//             'customer_id' => 'required|exists:users,id',
//         ]);

//         $user = Auth::user();
//         $deliveryUser = User::where('id', $request->user_id)->first();
//         if (!$deliveryUser) {
//             Log::error("Delivery Guy not found for user_id: {$request->user_id}");
//             return redirect()->back()->with(['message' => 'Delivery Guy not found']);
//         }

//         DB::beginTransaction();
//         try {
//             $order = Order::where('id', $request->order_id)
//                 ->with('restaurant')
//                 ->lockForUpdate()
//                 ->firstOrFail();

//             // Check if delivery is already assigned
//             $existingDelivery = AcceptDelivery::where('order_id', $order->id)->first();
//             if ($existingDelivery) {
//                 Log::warning("Delivery already assigned for order {$order->id}. Updating assignment.");
//                 $existingDelivery->user_id = $deliveryUser->id;
//                 $existingDelivery->customer_id = $request->customer_id;
//                 $existingDelivery->updated_at = Carbon::now();
//             } else {
//                 $existingDelivery = new AcceptDelivery;
//                 $existingDelivery->order_id = $order->id;
//                 $existingDelivery->user_id = $deliveryUser->id;
//                 $existingDelivery->customer_id = $request->customer_id;
//                 $existingDelivery->is_complete = 0;
//                 $existingDelivery->created_at = Carbon::now();
//                 $existingDelivery->updated_at = Carbon::now();
//             }

//             // Update location data if enabled
//             if (config('setting.enGDMA') == "true") {
//                 $location_data = (new DeliveryLiveLocation)->getDeliveryLiveLocation($order)->getContent();
//                 $customer_location = json_decode($order->location);
//                 $existingDelivery->location_data = json_encode([
//                     'lat' => json_decode($location_data)->delivery_lat,
//                     'long' => json_decode($location_data)->delivery_long,
//                     'heading' => json_decode($location_data)->heading,
//                     'store_distance' => getOsmDistance(
//                         $order->restaurant->latitude,
//                         $order->restaurant->longitude,
//                         json_decode($location_data)->delivery_lat,
//                         json_decode($location_data)->delivery_long
//                     ),
//                     'customer_distance' => getOsmDistance(
//                         json_decode($location_data)->delivery_lat,
//                         json_decode($location_data)->delivery_long,
//                         $customer_location->lat,
//                         $customer_location->lng
//                     ),
//                 ]);
//             }

//             $existingDelivery->save();

//             // Update order status
//             $order->orderstatus_id = 3;
//             $order->save();

//             // Log activity
//             activity()
//                 ->performedOn($order)
//                 ->causedBy($user)
//                 ->withProperties(['type' => 'Order_Assigned'])
//                 ->log("Order {$order->unique_order_id} assigned to Delivery Guy ID: {$deliveryUser->id}");

//             DB::commit();

//             // Send push notification to customer
//             if (config('setting.enablePushNotificationOrders') == 'true') {
//                 $notify = new PushNotify();
//                 $notify->sendPushNotification('3', $order->user_id, $order->unique_order_id);
//             }

//             // Send SMS notification to delivery guy
//             if (config('setting.smsDeliveryNotify') == 'true') {
//                 $message = config('setting.defaultSmsDeliveryMsg');
//                 $smsnotify = new Sms();
//                 $smsnotify->processSmsAction('OD_NOTIFY', $deliveryUser->phone, null, $message);
//             }

//             // Send push notification to delivery guy
//             if (config('setting.enablePushNotificationOrders') == 'true') {
//                 if (config('setting.hasSocketPush') != 'true') {
//                     $notify = new PushNotify();
//                     $notify->sendPushNotification('TO_DELIVERY', $deliveryUser->id, $order->unique_order_id);
//                 } else {
//                     if (config('setting.iHaveFoodomaaDeliveryApp') == 'true') {
//                         stopPlayingNotificationSoundDeliveryAppHelper($order);
//                         $deliveryGuyIds = [$deliveryUser->id];
//                         $notify = new SocketPush();
//                         $notify->pushNewOrder($order->unique_order_id, $deliveryGuyIds);
//                     }
//                 }
//             }

//             Log::info("Order {$order->unique_order_id} successfully assigned to Delivery Guy ID: {$deliveryUser->id}");
//             return redirect()->route('admin.viewOrder', $order->unique_order_id)->with(['success' => 'Order Assigned']);
//         } catch (\Illuminate\Database\QueryException $e) {
//             DB::rollback();
//             $errorCode = $e->errorInfo[1];
//             Log::error("QueryException in assignDeliveryFromAdmin: {$e->getMessage()}, Error Code: {$errorCode}");
//             if ($errorCode == 1062) {
//                 return redirect()->back()->with(['message' => 'Delivery already accepted']);
//             }
//             return redirect()->back()->with(['message' => 'Failed to assign delivery due to database error']);
//         } catch (\Exception $e) {
//             DB::rollback();
//             Log::error("Exception in assignDeliveryFromAdmin: {$e->getMessage()}");
//             return redirect()->back()->with(['message' => 'Failed to assign delivery']);
//         }
//     }
    
    // Extend By aya
   public function assignDeliveryFromAdmin(Request $request)
{
    // استخدام قناة التسجيل المخصصة
    $logger = Log::channel('assign_delivery');

    // تسجيل بداية تنفيذ الدالة
    $logger->info('Starting assignDeliveryFromAdmin', [
        'request_data' => $request->all(),
        'user_id' => Auth::id()
    ]);

    // التحقق من صحة المدخلات
    try {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'customer_id' => 'required|exists:users,id',
        ]);
        $logger->info('Request validation passed', ['inputs' => $request->only(['order_id', 'user_id', 'customer_id'])]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $logger->error('Validation failed', ['errors' => $e->errors()]);
        return redirect()->back()->with(['message' => 'Invalid input data']);
    }

    $user = Auth::user();
    $deliveryUser = User::where('id', $request->user_id)->first();
    if (!$deliveryUser) {
        $logger->error('Delivery Guy not found', ['user_id' => $request->user_id]);
        return redirect()->back()->with(['message' => 'Delivery Guy not found']);
    }
    $logger->info('Delivery Guy found', ['delivery_user_id' => $deliveryUser->id]);

    DB::beginTransaction();
    try {
        $order = Order::where('id', $request->order_id)
            ->with('restaurant')
            ->lockForUpdate()
            ->firstOrFail();
        $logger->info('Order retrieved', ['order_id' => $order->id, 'unique_order_id' => $order->unique_order_id]);

        // التحقق من تعيين التوصيل
        $existingDelivery = AcceptDelivery::where('order_id', $order->id)->first();
        if ($existingDelivery) {
            $logger->warning('Delivery already assigned. Updating assignment', [
                'order_id' => $order->id,
                'existing_delivery_id' => $existingDelivery->id
            ]);
            $existingDelivery->user_id = $deliveryUser->id;
            $existingDelivery->customer_id = $request->customer_id;
            $existingDelivery->updated_at = Carbon::now();
        } else {
            $logger->info('Creating new delivery assignment', ['order_id' => $order->id]);
            $existingDelivery = new AcceptDelivery;
            $existingDelivery->order_id = $order->id;
            $existingDelivery->user_id = $deliveryUser->id;
            $existingDelivery->customer_id = $request->customer_id;
            $existingDelivery->is_complete = 0;
            $existingDelivery->created_at = Carbon::now();
            $existingDelivery->updated_at = Carbon::now();
        }

        // تحديث بيانات الموقع إذا كان مفعلاً
   if (config('setting.enGDMA') == "true") {
    $logger->info('Processing location data', ['order_id' => $order->id]);
    try {
        $location_data = (new DeliveryLiveLocation)->getDeliveryLiveLocation($order)->getContent();
        $decoded_location = json_decode($location_data);
        if (!$decoded_location || !isset($decoded_location->delivery_lat) || !isset($decoded_location->delivery_long)) {
            throw new \Exception('Location data missing delivery_lat or delivery_long');
        }
        $customer_location = json_decode($order->location);
        if (!$customer_location || !isset($customer_location->lat) || !isset($customer_location->lng)) {
            throw new \Exception('Customer location data missing lat or lng');
        }
        $existingDelivery->location_data = json_encode([
            'lat' => $decoded_location->delivery_lat,
            'long' => $decoded_location->delivery_long,
            'heading' => $decoded_location->heading ?? null,
            'store_distance' => getOsmDistance(
                $order->restaurant->latitude,
                $order->restaurant->longitude,
                $decoded_location->delivery_lat,
                $decoded_location->delivery_long
            ),
            'customer_distance' => getOsmDistance(
                $decoded_location->delivery_lat,
                $decoded_location->delivery_long,
                $customer_location->lat,
                $customer_location->lng
            ),
        ]);
        $logger->info('Location data processed successfully', ['location_data' => $existingDelivery->location_data]);
    } catch (\Exception $e) {
        $logger->warning('Failed to process location data, continuing without location data', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $existingDelivery->location_data = null;
    }
}

        $logger->info('Saving delivery assignment', ['order_id' => $order->id]);
        $existingDelivery->save();
        $logger->info('Delivery assignment saved', ['delivery_id' => $existingDelivery->id]);

        // تحديث حالة الطلب
        $order->orderstatus_id = 3;
        $order->save();
        $logger->info('Order status updated', ['order_id' => $order->id, 'orderstatus_id' => 3]);

        // تسجيل النشاط
        activity()
            ->performedOn($order)
            ->causedBy($user)
            ->withProperties(['type' => 'Order_Assigned'])
            ->log("Order {$order->unique_order_id} assigned to Delivery Guy ID: {$deliveryUser->id}");
        $logger->info('Activity logged', [
            'order_id' => $order->id,
            'delivery_user_id' => $deliveryUser->id
        ]);

        DB::commit();
        $logger->info('Transaction committed', ['order_id' => $order->id]);

        // إرسال إشعار Push للعميل
        if (config('setting.enablePushNotificationOrders') == 'true') {
            $logger->info('Sending push notification to customer', ['customer_id' => $order->user_id]);
            try {
                $notify = new PushNotify();
                $notify->sendPushNotification('3', $order->user_id, $order->unique_order_id);
                $logger->info('Push notification sent to customer', ['customer_id' => $order->user_id]);
            } catch (\Exception $e) {
                $logger->error('Failed to send push notification to customer', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // إرسال إشعار SMS لشخص التوصيل
        if (config('setting.smsDeliveryNotify') == 'true') {
            $logger->info('Sending SMS notification to delivery guy', ['delivery_user_id' => $deliveryUser->id]);
            try {
                $message = config('setting.defaultSmsDeliveryMsg');
                $smsnotify = new Sms();
                $smsnotify->processSmsAction('OD_NOTIFY', $deliveryUser->phone, null, $message);
                $logger->info('SMS notification sent to delivery guy', ['delivery_user_id' => $deliveryUser->id]);
            } catch (\Exception $e) {
                $logger->error('Failed to send SMS notification', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // إرسال إشعار Push لشخص التوصيل
        if (config('setting.enablePushNotificationOrders') == 'true') {
            $logger->info('Sending push notification to delivery guy', ['delivery_user_id' => $deliveryUser->id]);
            try {
                if (config('setting.hasSocketPush') != 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('TO_DELIVERY', $deliveryUser->id, $order->unique_order_id);
                    $logger->info('Push notification sent to delivery guy', ['delivery_user_id' => $deliveryUser->id]);
                } else {
                    if (config('setting.iHaveFoodomaaDeliveryApp') == 'true') {
                        stopPlayingNotificationSoundDeliveryAppHelper($order);
                        $deliveryGuyIds = [$deliveryUser->id];
                        $notify = new SocketPush();
                        $notify->pushNewOrder($order->unique_order_id, $deliveryGuyIds);
                        $logger->info('Socket push notification sent', ['delivery_user_id' => $deliveryUser->id]);
                    }
                }
            } catch (\Exception $e) {
                $logger->error('Failed to send push notification to delivery guy', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $logger->info('Order successfully assigned', [
            'order_id' => $order->id,
            'unique_order_id' => $order->unique_order_id,
            'delivery_user_id' => $deliveryUser->id
        ]);
        return redirect()->route('admin.viewOrder', $order->unique_order_id)->with(['success' => 'Order Assigned']);
    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollback();
        $errorCode = $e->errorInfo[1];
        $logger->error('Database query error', [
            'error' => $e->getMessage(),
            'error_code' => $errorCode,
            'trace' => $e->getTraceAsString()
        ]);
        if ($errorCode == 1062) {
            return redirect()->back()->with(['message' => 'Delivery already accepted']);
        }
        return redirect()->back()->with(['message' => 'Failed to assign delivery due to database error']);
    } catch (\Exception $e) {
        DB::rollback();
        $logger->error('Unexpected error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with(['message' => 'Failed to assign delivery: ' . $e->getMessage()]);
    }
}
    // ========================================
    /**
     * @param Request $request
     */
    public function reAssignDeliveryFromAdmin(Request $request)
    {
        $user = Auth::user();

        $deliveryUser = User::where('id', $request->user_id)->first();
        if (!$deliveryUser) {
            abort(404, 'Delivery Guy not found');
        }

        $order = Order::where('id', $request->order_id)->firstOrFail();

        switch ($order->orderstatus_id) {
            case '5':
                return redirect()->back()->with(array('message' => 'Cannot assign delivery guy to a completed order.'));
            case '6':
                return redirect()->back()->with(array('message' => 'Cannot assign delivery guy to a cancelled order.'));
        }

        $assignment = AcceptDelivery::where('order_id', $request->order_id)->first();
        $assignment->user_id = $deliveryUser->id;
        $assignment->is_complete = 0;
        $assignment->updated_at = Carbon::now();
        $assignment->save();

        if (config('setting.enGDMA') == "true") {
            $location_data = (new DeliveryLiveLocation)->getDeliveryLiveLocation($order)->getContent();
            $customer_location = json_decode($order->location);

            $assignment->location_data = json_encode(
                [
                    'lat' => json_decode($location_data)->delivery_lat,
                    'long' => json_decode($location_data)->delivery_long,
                    'heading' => json_decode($location_data)->heading,
                    'store_distance' => getOsmDistance($order->restaurant->latitude, $order->restaurant->longitude, json_decode($location_data)->delivery_lat, json_decode($location_data)->delivery_long),
                    'customer_distance' => getOsmDistance(json_decode($location_data)->delivery_lat, json_decode($location_data)->delivery_long, $customer_location->lat, $customer_location->lng),
                ]
            );
            $assignment->save();
        }

        // Send SMS Notification to Delivery Guy
        if (config('setting.smsDeliveryNotify') == 'true') {
            $message = config('setting.defaultSmsDeliveryMsg');
            $otp = null;
            $smsnotify = new Sms();
            $smsnotify->processSmsAction('OD_NOTIFY', $deliveryUser->phone, $otp, $message);
        }
        // Send Push Notification to Delivery Guy
        if (config('setting.enablePushNotificationOrders') == 'true') {
            if (config('setting.hasSocketPush') != 'true') {
                $notify = new PushNotify();
                $notify->sendPushNotification('TO_DELIVERY', $deliveryUser->id, $order->unique_order_id);
            } else {
                if (config('setting.iHaveFoodomaaDeliveryApp') == "true") {
                    stopPlayingNotificationSoundDeliveryAppHelper($order);
                    $deliveryGuyIds = [$deliveryUser->id];
                    $notify = new SocketPush();
                    $notify->pushNewOrder($order->unique_order_id, $deliveryGuyIds);
                }
            }
        }

        activity()
            ->performedOn($order)
            ->causedBy($user)
            ->withProperties(['type' => 'Order_Reassigned'])->log('Order re-assigned to Delivery Guy');

        return redirect()->back()->with(array('success' => 'Order reassigned successfully'));
    }

    public function popularGeoLocations(Request $request)
    {
        $locations = PopularGeoPlace::orderBy('id', 'DESC')->paginate(20);
        $count = $locations->total();

        $primaryLocation = PopularGeoPlace::where('is_default', '1')->first();
        if (!$primaryLocation) {
            if ($count > 0) {
                $message = "Create atleast one primary business location or set one as primary location (click the check mark button)";
                $request->session()->flash('message', $message);
            } else {
                $message = "Create atleast one primary business location";
                $request->session()->flash('message', $message);
            }
        }

        return view('admin.popularGeoLocations', array(
            'locations' => $locations,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewPopularGeoLocation(Request $request)
    {
        $existing = PopularGeoPlace::count();
        if ($existing == 0) {
            $setPrimary = true;
        } else {
            $setPrimary = false;
        }

        $location = new PopularGeoPlace();

        $location->name = $request->name;

        $location->latitude = $request->latitude;
        $location->longitude = $request->longitude;

        if ($request->is_active == 'true') {
            $location->is_active = true;
        } else {
            $location->is_active = false;
        }

        $location->is_default = $setPrimary;

        try {
            $location->save();
            return redirect()->back()->with(['success' => 'Location Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function disablePopularGeoLocation($id)
    {
        $location = PopularGeoPlace::where('id', $id)->first();

        if ($location) {

            if ($location->is_default) {
                return redirect()->back()->with(['message' => 'Primary location cannot be disabled.']);
            }

            $location->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Location Disabled']);
        } else {
            return redirect()->route('admin.popularGeoLocations');
        }
    }

    /**
     * @param $id
     */
    public function deletePopularGeoLocation($id)
    {
        $location = PopularGeoPlace::where('id', $id)->first();

        if ($location) {
            if ($location->is_default) {
                return redirect()->back()->with(['message' => 'Primary location cannot be deleted.']);
            }

            $location->delete();

            return redirect()->route('admin.popularGeoLocations')->with(['success' => 'Location Deleted']);
        } else {
            return redirect()->route('admin.popularGeoLocations');
        }
    }

    public function makeDefaultLocation($id)
    {
        $location = PopularGeoPlace::where('id', $id)->firstOrFail();

        //remove default of other
        $currentDefaults = PopularGeoPlace::where('is_default', '1')->get();
        if (!empty($currentDefaults)) {
            foreach ($currentDefaults as $currentDefault) {
                $currentDefault->is_default = 0;
                $currentDefault->save();
            }
        }

        $location->is_active = 1;
        $location->is_default = 1;
        $location->save();

        return redirect()->back()->with(['success' => 'Primary location updated successfully.']);
    }

    public function translations()
    {
        $translations = Translation::orderBy('id', 'DESC')->get();
        $count = count($translations);

        return view('admin.translations', array(
            'translations' => $translations,
            'count' => $count,
        ));
    }

    public function newTranslation()
    {
        return view('admin.newTranslation');
    }

    /**
     * @param Request $request
     */
    public function saveNewTranslation(Request $request)
    {
        // dd($request->all());
        // dd(json_encode($request->except(['language_name'])));

        $translation = new Translation();

        $translation->language_name = $request->language_name;
        $translation->data = json_encode($request->except(['language_name', '_token']));

        try {
            $translation->save();
            return redirect()->route('admin.translations')->with(['success' => 'Translation Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function editTranslation($id)
    {

        $translation = Translation::where('id', $id)->first();
        // dd(json_decode($translation->data));

        if ($translation) {
            return view('admin.editTranslation', array(
                'translation_id' => $translation->id,
                'language_name' => $translation->language_name,
                'data' => json_decode($translation->data),
            ));
        } else {
            return redirect()->route('admin.translations')->with(['message' => 'Translation Not Found']);
        }
    }

    /**
     * @param Request $request
     */
    public function updateTranslation(Request $request)
    {
        $translation = Translation::where('id', $request->translation_id)->first();

        $translation->language_name = $request->language_name;
        $translation->data = json_encode($request->except(['translation_id', 'language_name', '_token']));

        try {
            $translation->save();
            return redirect()->back()->with(['success' => 'Translation Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function disableTranslation($id)
    {
        $translation = Translation::where('id', $id)->first();
        if ($translation) {
            $translation->toggleEnable()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.translations');
        }
    }

    /**
     * @param $id
     */
    public function deleteTranslation($id)
    {
        $translation = Translation::where('id', $id)->first();
        if ($translation) {
            $translation->delete();
            return redirect()->route('admin.translations')->with(['success' => 'Translation Deleted']);
        } else {
            return redirect()->route('admin.translations');
        }
    }

    /**
     * @param $id
     */
    public function makeDefaultLanguage($id)
    {
        $translation = Translation::where('id', $id)->firstOrFail();

        //remove default of other
        $currentDefaults = Translation::where('is_default', '1')->get();
        // dd($currentDefault);
        if (!empty($currentDefaults)) {
            foreach ($currentDefaults as $currentDefault) {
                $currentDefault->is_default = 0;
                $currentDefault->save();
            }
        }

        //make this default
        $translation->is_default = 1;
        $translation->is_active = 1;
        $translation->save();
        return redirect()->back()->with(['success' => 'Operation Successful']);
    }

    /**
     * @param Request $request
     */
    public function updateRestaurantScheduleData(Request $request)
    {
        $data = $request->except(['_token', 'restaurant_id']);

        $i = 0;
        $str = '{';
        foreach ($data as $day => $times) {
            $str .= '"' . $day . '":[';
            if ($times) {
                foreach ($times as $key => $time) {

                    if ($key % 2 == 0) {
                        $t1 = $time;
                        $str .= '{"open" :' . '"' . $time . '"';
                    } else {
                        $t2 = $time;
                        $str .= '"close" :' . '"' . $time . '"}';
                    }

                    //check if last, if last then dont add comma,
                    if (count($times) != $key + 1) {
                        $str .= ',';
                    }
                }
                // dd($t1);
                if (Carbon::parse($t1) >= Carbon::parse($t2)) {

                    return redirect()->back()->with(['message' => 'Opening and Closing time is incorrect']);
                }
            } else {
                $str .= '}]';
            }

            if ($i != count($data) - 1) {
                $str .= '],';
            } else {
                $str .= ']';
            }
            $i++;
        }
        $str .= '}';

        // Fetches The Restaurant
        $restaurant = Restaurant::where('id', $request->restaurant_id)->first();
        // Enters The Data
        $restaurant->schedule_data = $str;
        // Saves the Data to Database
        $restaurant->save();

        return redirect()->back()->with(['success' => 'Scheduling data saved successfully']);
    }

    /**
     * @param $id
     */
    public function impersonate($id)
    {
        $user = User::where('id', $id)->first();
        if ($user && $user->hasRole('Store Owner')) {
            Auth::user()->impersonate($user);
            return redirect()->route('get.login');
        } else {
            return redirect()->route('admin.dashboard')->with(['message' => 'User not found']);
        }
    }

    /**
     * @param $order_id
     */
    public function approvePaymentOfOrder($order_id)
    {
        $user = Auth::user();

        $order = Order::where('id', $order_id)->with('restaurant')->firstOrFail();

        if ($order->orderstatus_id == '8') {

            if ($order->restaurant->auto_acceptable) {
                $orderstatus_id = '2';
                if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled() && $order->schedule_date != null && $order->schedule_slot != null) {
                    $orderstatus_id = '10';
                }
            } else {
                $orderstatus_id = '1';
                if (Module::find('OrderSchedule') && Module::find('OrderSchedule')->isEnabled()) {
                    if ($order->schedule_date != null && $order->schedule_slot != null) {
                        $orderstatus_id = '10';
                    }
                }
            }

            $order->orderstatus_id = $orderstatus_id;
            $order->save();

            if ($order->restaurant->auto_acceptable) {
                if ($orderstatus_id == '2') {
                    //to user
                    $notify = new PushNotify();
                    $notify->sendPushNotification('2', $order->user_id, $order->unique_order_id);
                    //to delivery
                    sendSmsToDelivery($order->restaurant_id);
                    sendPushNotificationToDelivery($order->restaurant_id, $order);
                }

                sendPushNotificationToStoreOwner($order->restaurant_id, $order->unique_order_id);
            } else {
                sendSmsToStoreOwner($order->restaurant_id, $order->total);
                sendPushNotificationToStoreOwner($order->restaurant_id, $order->unique_order_id);
            }

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Payment_Approved'])->log('Order payment approved');

            if ($order->orderstatus_id == "2") {
                activity()
                    ->performedOn($order)
                    ->causedBy(User::find(1))
                    ->withProperties(['type' => 'Order_Accepted_Auto'])->log('Order auto accepted');
            }

            $approveRecord = new ApprovePaymentHistory();
            $approveRecord->order_id = $order->id;
            $approveRecord->user_id = $user->id;
            if ($user->zone_id != null) {
                $approveRecord->zone_id = $user->zone_id;
            }
            $approveRecord->save();

            return redirect()->back()->with(['success' => 'Payment Approved']);
        } else {
            return 'Error! Payment already approved.';
        }
    }


    /**
     * @param Request $request
     */
    public function updateStorePayoutDetails(Request $request)
    {
        $storePayoutDetail = StorePayoutDetail::where('restaurant_id', $request->restaurant_id)->first();
        if ($storePayoutDetail) {
            $storePayoutDetail->data = json_encode($request->except(['restaurant_id', '_token']));
        } else {
            $storePayoutDetail = new StorePayoutDetail();
            $storePayoutDetail->restaurant_id = $request->restaurant_id;
            $storePayoutDetail->data = json_encode($request->except(['restaurant_id', '_token']));
        }
        try {
            $storePayoutDetail->save();
            return redirect()->back()->with(['success' => 'Payout Data Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th->getMessage()]);
        }
    }

    /**
     * @param $id
     */
    public function confirmScheduledOrder($id)
    {
        $user = Auth::user();

        $order = Order::where('id', $id)->firstOrFail();

        if ($order->orderstatus_id == '10') {
            $order->orderstatus_id = 11;
            $order->save();

            activity()
                ->performedOn($order)
                ->causedBy($user)
                ->withProperties(['type' => 'Confirm_Scheduled_Order'])->log('Scheduled order confirmed');

            return redirect()->back()->with(array('success' => 'Scheduled order confirmed.'));
        } else {
            return redirect()->back()->with(array('message' => 'Something went wrong'));
        }
    }

    public function acceptNotice()
    {
        $setting = Setting::where('key', 'moduleRedownloadNotice')->first();
        $setting->value = "true";
        $setting->save();
        Artisan::call('cache:clear');

        $response = [
            'success' => true,
        ];
        return response()->json($response, 200);
    }

    public function deleteUserAddress(Request $request)
    {
        $user = User::where('id', $request->user_id)->firstOrFail();

        $defaultAddressId = $user->default_address_id;
        if ($defaultAddressId != $request->address_id) {
            $address = Address::where('id', $request->address_id)->first();
            if ($address) {
                $address->delete();
                return redirect(route('admin.get.editUser', $request->user_id) . $request->window_redirect_hash)->with(['success' => 'Address deleted']);
            } else {
                return redirect(route('admin.get.editUser', $request->user_id) . $request->window_redirect_hash)->with(['message' => 'Address not found']);
            }
        } else {
            return redirect(route('admin.get.editUser', $request->user_id) . $request->window_redirect_hash)->with(['message' => 'Primary address cannot be deleted']);
        }
    }

    public function getNearestDeliveryGuys($order_id, $method = null)
    {
        $order = Order::where('id', $order_id)->with('restaurant.zone')->first();
        $zone = $order->restaurant->zone;
        $deliveryGuys = $order->restaurant->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Delivery Guy');
            })
            ->whereHas('delivery_guy_detail', function ($query) {
                $query->where('status', true);
            })
            ->with('delivery_guy_detail')
            ->get();

        if (!$deliveryGuys) {
            if ($zone) {
                $deliveryGuys = User::role('Delivery Guy')
                    ->whereHas('delivery_guy_detail', function ($query) {
                        $query->where('status', true);
                    })->where('zone_id', $zone->id)
                      ->with('delivery_guy_detail')
                    ->get();
            } else {
                $deliveryGuys = User::role('Delivery Guy')
                    ->whereHas('delivery_guy_detail', function ($query) {
                        $query->where('status', true);
                    })
                     ->with('delivery_guy_detail')
                    ->get();
            }
        }

        (new DeliveryLiveLocation)->updateDeliveryGuysLocation($deliveryGuys, $zone->id);

        $deliveryGuyData = [];
        foreach ($deliveryGuys as $deliveryGuy) {
            $nonCompleteOrders = json_decode($this->getDeliveryGuyCurrentOrders($deliveryGuy->id)->getContent());
         //   $distance = getDistance($deliveryGuy->delivery_guy_detail->delivery_lat, $deliveryGuy->delivery_guy_detail->delivery_long, $order->restaurant->latitude, $order->restaurant->longitude);
             $distance = getOsmDistance($deliveryGuy->delivery_guy_detail->delivery_lat, $deliveryGuy->delivery_guy_detail->delivery_long, $order->restaurant->latitude, $order->restaurant->longitude);
            $deliveryGuyData[$deliveryGuy->id] = [
                'delivery_guy_id' => $deliveryGuy->id,
                'distance' => number_format($distance, 2),
                'name' => $deliveryGuy->name,
                'current_orders_count' => count($nonCompleteOrders),
                'current_orders' => $nonCompleteOrders,
                'max_delivery_distance' => $deliveryGuy->delivery_guy_detail->max_delivery_distance,
                'max_order_limit' => $deliveryGuy->delivery_guy_detail->max_accept_delivery_limit,
            ];
        }
        usort($deliveryGuyData, function ($a, $b) {
            $distanceComparison = $a['distance'] <=> $b['distance'];

            // If distance is the same, compare based on current_orders_count
            return $distanceComparison !== 0 ? $distanceComparison : $a['current_orders_count'] <=> $b['current_orders_count'];
        });

        if ($method == "job") {
            return $deliveryGuyData;
        } else {
            return response()->json($deliveryGuyData);
        }
    }

    public function getDeliveryGuyCurrentOrders($deliveryGuyId)
    {
        $nonCompleteOrder = AcceptDelivery::whereHas("order", function ($query) {
            $query->whereIn("orderstatus_id", ["3", "4"]);
        })->where("user_id", $deliveryGuyId)
            ->where("is_complete", 0)
            ->select('id', 'order_id')
            ->with('order.user', 'order.restaurant')
            ->get();
        $nonCompleteOrder = $nonCompleteOrder->pluck('order')->toArray();

        return response()->json($nonCompleteOrder);
    }

    public function cancelReasons()
    {
        $reasons = CancelReason::with('role')->get();
        $roles = Role::get();

        return view('admin.cancelReasons', compact('reasons', 'roles'));
    }

    public function createCancelReason(Request $request)
    {
        $reason = new CancelReason();
        $reason->cancel_reason = $request->reason;
        $reason->role_id = $request->role_id;
        $reason->save();

        return redirect()->back()->with(['success' => 'Reason created successfully']);
    }

    public function deleteCancelReason($id)
    {
        $reason = CancelReason::where('id', $id)->delete();

        return redirect()->back()->with(['success' => 'Reason deleted successfully']);
    }
};
