<?php

namespace App\Http\Controllers;

use Auth;
use App\Order;
use Exception;
use App\Coupon;
use Carbon\Carbon;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    /**
     * @param Request $request
     */

// public function applyCoupon(Request $request)
// {
//     $user = Auth::user();
//     if (!$user) {
//         Log::warning("Coupon apply failed: User not logged in", [
//             'coupon_code' => $request->coupon,
//             'restaurant_id' => $request->restaurant_id,
//         ]);
//         $response = [
//             'success' => false,
//             'type' => 'NOTLOGGEDIN',
//             'message' => 'User not logged in.',
//         ];
//         return response()->json($response);
//     }

//     Log::info("Coupon apply request", [
//         'coupon_code' => $request->coupon,
//         'restaurant_id' => $request->restaurant_id,
//         'subtotal' => $request->subtotal,
//         'delivery_fee' => $request->delivery_fee,
//         'user_id' => $user->id,
//     ]);

//     $coupon = Coupon::where('code', $request->coupon)->first();

//     if ($coupon && $coupon->is_active) {
//         $requestRestaurantId = $request->restaurant_id ? (int)$request->restaurant_id : null;
//         $restaurantIds = $coupon->restaurants()->pluck('restaurant_id')->toArray();

//         if (empty($restaurantIds) || ($requestRestaurantId && in_array($requestRestaurantId, array_map('intval', $restaurantIds)))) {
//             if ($coupon->expiry_date->gt(Carbon::now()) && $coupon->count < $coupon->max_count) {
//                 if ($request->subtotal >= $coupon->min_subtotal) {
//                     $userOrderCount = count($user->orders);

//                     if ($coupon->user_type == 'ONCE') {
//                         $orderAlreadyPlacedWithCoupon = Order::where('user_id', $user->id)->where('coupon_name', $coupon->code)->first();
//                         if ($orderAlreadyPlacedWithCoupon) {
//                             Log::warning("Coupon apply failed: Already used once", [
//                                 'coupon_code' => $coupon->code,
//                                 'user_id' => $user->id,
//                             ]);
//                             $response = [
//                                 'success' => false,
//                                 'type' => 'ALREADYUSEDONCE',
//                                 'message' => 'This coupon can only be used once per one user',
//                             ];
//                             return response()->json($response);
//                         }
//                     }
//                     if ($coupon->user_type == 'ONCENEW') {
//                         if ($userOrderCount != 0) {
//                             Log::warning("Coupon apply failed: Only for new users", [
//                                 'coupon_code' => $coupon->code,
//                                 'user_id' => $user->id,
//                                 'order_count' => $userOrderCount,
//                             ]);
//                             $response = [
//                                 'success' => false,
//                                 'type' => 'FORNEWUSER',
//                                 'message' => 'This coupon can only be used for first order',
//                             ];
//                             return response()->json($response);
//                         }
//                     }
//                     if ($coupon->user_type == 'CUSTOM') {
//                         $orderAlreadyPlacedWithCoupon = Order::where('user_id', $user->id)->where('coupon_name', $coupon->code)->get()->count();
//                         if ($orderAlreadyPlacedWithCoupon >= $coupon->max_count_per_user) {
//                             Log::warning("Coupon apply failed: Max limit reached per user", [
//                                 'coupon_code' => $coupon->code,
//                                 'user_id' => $user->id,
//                                 'order_count' => $orderAlreadyPlacedWithCoupon,
//                             ]);
//                             $response = [
//                                 'success' => false,
//                                 'type' => 'MAXLIMITREACHEDPERUSER',
//                                 'message' => 'Max limit reached for this coupon',
//                             ];
//                             return response()->json($response);
//                         }
//                     }

//                     // Calculate discount based on coupon type
//                     $discount = 0;
//                     $total = floatval($request->subtotal) + floatval($request->delivery_fee ?? 0);

//                     Log::info("Calculating discount", [
//                         'coupon_code' => $coupon->code,
//                         'discount_type' => $coupon->discount_type,
//                         'subtotal' => $request->subtotal,
//                         'delivery_fee' => $request->delivery_fee,
//                         'total' => $total,
//                     ]);

//                     if ($coupon->discount_type == 'PERCENTAGE') {
//                         $discount = ($coupon->discount / 100) * $request->subtotal;
//                         if ($coupon->max_discount && $discount > $coupon->max_discount) {
//                             $discount = $coupon->max_discount;
//                             Log::info("Applied max discount limit", [
//                                 'coupon_code' => $coupon->code,
//                                 'original_discount' => ($coupon->discount / 100) * $request->subtotal,
//                                 'max_discount' => $coupon->max_discount,
//                             ]);
//                         }
//                     } elseif ($coupon->discount_type == 'AMOUNT' || $coupon->discount_type == 'FIXED') {
//                         $discount = $coupon->discount ?? 0;
//                         if ($discount > $total) {
//                             $discount = $total;
//                             Log::warning("Discount capped to total", [
//                                 'coupon_code' => $coupon->code,
//                                 'original_discount' => $coupon->discount,
//                                 'capped_discount' => $total,
//                             ]);
//                         }
//                     } elseif ($coupon->discount_type == 'FREE') {
//                         $deliveryFee = floatval($request->delivery_fee ?? 0);
//                         $discount = $deliveryFee * ($coupon->delivery_discount_percentage / 100);
//                         Log::info("Applied free delivery discount", [
//                             'coupon_code' => $coupon->code,
//                             'delivery_fee' => $deliveryFee,
//                             'discount_percentage' => $coupon->delivery_discount_percentage,
//                             'discount' => $discount,
//                         ]);
//                     }

//                     Log::info("Coupon applied", [
//                         'coupon_code' => $coupon->code,
//                         'discount_type' => $coupon->discount_type,
//                         'discount' => $discount,
//                         'amount' => $coupon->discount,
//                         'subtotal' => $request->subtotal,
//                         'delivery_fee' => $request->delivery_fee,
//                         'total' => $total,
//                     ]);

//                     $coupon->success = true;
//                     $coupon->discount = $discount;
//                     $coupon->amount = $discount;

//                     return response()->json($coupon);
//                 } else {
//                     Log::warning("Coupon apply failed: Subtotal below minimum", [
//                         'coupon_code' => $coupon->code,
//                         'subtotal' => $request->subtotal,
//                         'min_subtotal' => $coupon->min_subtotal,
//                     ]);
//                     $response = [
//                         'success' => false,
//                         'type' => 'MINSUBTOTAL',
//                         'message' => $coupon->subtotal_message,
//                     ];
//                     return response()->json($response);
//                 }
//             } else {
//                 Log::warning("Coupon apply failed: Expired or max count reached", [
//                     'coupon_code' => $coupon->code,
//                     'expiry_date' => $coupon->expiry_date,
//                     'count' => $coupon->count,
//                     'max_count' => $coupon->max_count,
//                 ]);
//                 $response = [
//                     'success' => false,
//                     'type' => 'EXPIRED_OR_MAXCOUNT',
//                     'message' => 'Coupon has expired or reached maximum usage.',
//                 ];
//                 return response()->json($response);
//             }
//         } else {
//             Log::warning("Coupon not valid for restaurant", [
//                 'coupon_code' => $coupon->code,
//                 'restaurant_id' => $request->restaurant_id,
//                 'associated_restaurants' => $restaurantIds,
//             ]);
//             $response = [
//                 'success' => false,
//                 'type' => 'INVALID_RESTAURANT',
//                 'message' => 'Coupon is not valid for this restaurant.',
//             ];
//             return response()->json($response);
//         }
//     } else {
//         Log::warning("Coupon apply failed: Invalid or inactive coupon", [
//             'coupon_code' => $request->coupon,
//             'is_active' => $coupon ? $coupon->is_active : null,
//         ]);
//         $response = [
//             'success' => false,
//             'type' => 'INVALID_COUPON',
//             'message' => 'Coupon is invalid or inactive.',
//         ];
//         return response()->json($response);
//     }
// }
public function applyCoupon(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        Log::warning("Coupon apply failed: User not logged in", [
            'coupon_code' => $request->coupon,
            'restaurant_id' => $request->restaurant_id,
        ]);
        $response = [
            'success' => false,
            'type' => 'NOTLOGGEDIN',
            'message' => 'User not logged in.',
        ];
        return response()->json($response);
    }

    Log::info("Coupon apply request", [
        'coupon_code' => $request->coupon,
        'restaurant_id' => $request->restaurant_id,
        'subtotal' => $request->subtotal,
        'delivery_fee' => $request->delivery_fee,
        'user_id' => $user->id,
    ]);

    $coupon = Coupon::where('code', $request->coupon)->first();

    if ($coupon && $coupon->is_active) {
        $requestRestaurantId = $request->restaurant_id ? (int)$request->restaurant_id : null;
        $restaurantIds = $coupon->restaurants()->pluck('restaurant_id')->toArray();

        if (empty($restaurantIds) || ($requestRestaurantId && in_array($requestRestaurantId, array_map('intval', $restaurantIds)))) {
            if ($coupon->expiry_date->gt(Carbon::now()) && $coupon->count < $coupon->max_count) {
                if ($request->subtotal >= $coupon->min_subtotal) {
                    $userOrderCount = count($user->orders);

                    if ($coupon->user_type == 'ONCE') {
                        $orderAlreadyPlacedWithCoupon = Order::where('user_id', $user->id)->where('coupon_name', $coupon->code)->first();
                        if ($orderAlreadyPlacedWithCoupon) {
                            Log::warning("Coupon apply failed: Already used once", [
                                'coupon_code' => $coupon->code,
                                'user_id' => $user->id,
                            ]);
                            $response = [
                                'success' => false,
                                'type' => 'ALREADYUSEDONCE',
                                'message' => 'This coupon can only be used once per one user',
                            ];
                            return response()->json($response);
                        }
                    }
                    if ($coupon->user_type == 'ONCENEW') {
                        if ($userOrderCount != 0) {
                            Log::warning("Coupon apply failed: Only for new users", [
                                'coupon_code' => $coupon->code,
                                'user_id' => $user->id,
                                'order_count' => $userOrderCount,
                            ]);
                            $response = [
                                'success' => false,
                                'type' => 'FORNEWUSER',
                                'message' => 'This coupon can only be used for first order',
                            ];
                            return response()->json($response);
                        }
                    }
                    if ($coupon->user_type == 'CUSTOM') {
                        $orderAlreadyPlacedWithCoupon = Order::where('user_id', $user->id)->where('coupon_name', $coupon->code)->get()->count();
                        if ($orderAlreadyPlacedWithCoupon >= $coupon->max_count_per_user) {
                            Log::warning("Coupon apply failed: Max limit reached per user", [
                                'coupon_code' => $coupon->code,
                                'user_id' => $user->id,
                                'order_count' => $orderAlreadyPlacedWithCoupon,
                            ]);
                            $response = [
                                'success' => false,
                                'type' => 'MAXLIMITREACHEDPERUSER',
                                'message' => 'Max limit reached for this coupon',
                            ];
                            return response()->json($response);
                        }
                    }

                    // Calculate discount based on coupon type
                    $discount = 0;
                    $total = floatval($request->subtotal) + floatval($request->delivery_fee ?? 0);

                    Log::info("Calculating discount", [
                        'coupon_code' => $coupon->code,
                        'discount_type' => $coupon->discount_type,
                        'subtotal' => $request->subtotal,
                        'delivery_fee' => $request->delivery_fee,
                        'total' => $total,
                    ]);

                    if ($coupon->discount_type == 'PERCENTAGE') {
                        $discount = ($coupon->discount / 100) * $request->subtotal;
                        if ($coupon->max_discount && $discount > $coupon->max_discount) {
                            $discount = $coupon->max_discount;
                            Log::info("Applied max discount limit", [
                                'coupon_code' => $coupon->code,
                                'original_discount' => ($coupon->discount / 100) * $request->subtotal,
                                'max_discount' => $coupon->max_discount,
                            ]);
                        }
                    } elseif ($coupon->discount_type == 'AMOUNT' || $coupon->discount_type == 'FIXED') {
                        $discount = $coupon->discount ?? 0;
                        if ($discount > $total) {
                            $discount = $total;
                            Log::warning("Discount capped to total", [
                                'coupon_code' => $coupon->code,
                                'original_discount' => $coupon->discount,
                                'capped_discount' => $total,
                            ]);
                        }
                    } elseif ($coupon->discount_type == 'FREE') {
                        $deliveryFee = floatval($request->delivery_fee ?? 0);
                        $discount = $deliveryFee * ($coupon->delivery_discount_percentage / 100);
                        Log::info("Applied free delivery discount", [
                            'coupon_code' => $coupon->code,
                            'delivery_fee' => $deliveryFee,
                            'discount_percentage' => $coupon->delivery_discount_percentage,
                            'discount' => $discount,
                        ]);
                    }

                    Log::info("Coupon applied", [
                        'coupon_code' => $coupon->code,
                        'discount_type' => $coupon->discount_type,
                        'discount' => $discount,
                        'amount' => $coupon->discount,
                        'subtotal' => $request->subtotal,
                        'delivery_fee' => $request->delivery_fee,
                        'total' => $total,
                    ]);

                    $coupon->success = true;
                    $coupon->applied_amount = $discount; // Store the applied discount
                    // Do NOT override coupon->discount, keep it as the original value (100,000 SYP)
                    // $coupon->amount remains the original discount value

                    return response()->json($coupon);
                } else {
                    Log::warning("Coupon apply failed: Subtotal below minimum", [
                        'coupon_code' => $coupon->code,
                        'subtotal' => $request->subtotal,
                        'min_subtotal' => $coupon->min_subtotal,
                    ]);
                    $response = [
                        'success' => false,
                        'type' => 'MINSUBTOTAL',
                        'message' => $coupon->subtotal_message,
                    ];
                    return response()->json($response);
                }
            } else {
                Log::warning("Coupon apply failed: Expired or max count reached", [
                    'coupon_code' => $coupon->code,
                    'expiry_date' => $coupon->expiry_date,
                    'count' => $coupon->count,
                    'max_count' => $coupon->max_count,
                ]);
                $response = [
                    'success' => false,
                    'type' => 'EXPIRED_OR_MAXCOUNT',
                    'message' => 'Coupon has expired or reached maximum usage.',
                ];
                return response()->json($response);
            }
        } else {
            Log::warning("Coupon not valid for restaurant", [
                'coupon_code' => $coupon->code,
                'restaurant_id' => $request->restaurant_id,
                'associated_restaurants' => $restaurantIds,
            ]);
            $response = [
                'success' => false,
                'type' => 'INVALID_RESTAURANT',
                'message' => 'Coupon is not valid for this restaurant.',
            ];
            return response()->json($response);
        }
    } else {
        Log::warning("Coupon apply failed: Invalid or inactive coupon", [
            'coupon_code' => $request->coupon,
            'is_active' => $coupon ? $coupon->is_active : null,
        ]);
        $response = [
            'success' => false,
            'type' => 'INVALID_COUPON',
            'message' => 'Coupon is invalid or inactive.',
        ];
        return response()->json($response);
    }
}
    // ========================

    public function coupons(Request $request)
    {
        /* START qusay */
        $coupons = Coupon::orderBy('id', 'DESC')->with('restaurants');
        if ( $request->get('action_search') ) {
            if ( $request->get('search_by_code') ) {
                $coupons->where('code', 'like', '%' . $request->get('search_by_code') . '%');
            }
        }
        
        $coupons = $coupons->paginate(20);
        /* END qusay */
        
        $couponTotal = $coupons->total();

        $restaurants = Restaurant::get(['id', 'name']);
        $todaysDate = Carbon::now()->format('m-d-Y');
        
    
        return view('admin.coupons', array(
            'coupons' => $coupons->appends(request()->query()), // added by qusay
            'couponTotal' => $couponTotal,
            'restaurants' => $restaurants,
            'todaysDate' => $todaysDate,
        ));
    }

    /* START qusay */
    public function checkExistingCoupon(Request $request) {
        $code = trim($request->code);
        
        $existingCoupon = Coupon::where('code', $code)->first();
        if ($existingCoupon) {
            return json_encode([
                'status' => true,
                'message' => 'The coupon code has already been taken.'
            ]);
        }
        return json_encode([
            'status' => false
        ]);
    }
    /* END qusay */

    /**
     * @param Request $request
     */
    //  Extend By Aya 
    public function saveNewCoupon(Request $request)
    {
        $existingCoupon = Coupon::where('code', $request->code)->first();
        if ($existingCoupon) {
            return redirect()->back()->with(['message' => 'The coupon code has already been taken.']);
        }
    
        $coupon = new Coupon();
    
        $coupon->name = $request->name;
        $coupon->description = $request->description;
        $coupon->code = $request->code;
        $coupon->is_active = $request->is_active == 'true';
        $coupon->show_in_restaurant = $request->show_in_restaurant == 'true';
        $coupon->show_in_cart = $request->show_in_cart == 'true';
        $coupon->show_in_home = $request->show_in_home == 'true';
        $coupon->user_type = $request->user_type;
        $coupon->max_count_per_user = $request->user_type == 'CUSTOM' ? $request->max_count_per_user : null;
        $coupon->is_used_for_delivery = $request->is_used_for_delivery == 'true' || $request->discount_type == 'FREE';
        $coupon->expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d H:i:s');
        $coupon->max_count = $request->max_count;
        $coupon->min_subtotal = $request->min_subtotal ?? 0;
        $coupon->subtotal_message = $request->subtotal_message;
        $coupon->delivery_discount_percentage = $request->delivery_discount_percentage ?? 0;
    
        if ($coupon->is_used_for_delivery || $request->discount_type == 'FREE') {
            $coupon->discount_type = 'FREE';
            $coupon->discount = 0;
            $coupon->max_discount = null;
            $coupon->is_used_for_delivery = true;
        } else {
            $coupon->discount_type = $request->discount_type;
            $coupon->discount = $request->discount;
            $coupon->max_discount = $request->discount_type == 'PERCENTAGE' ? $request->max_discount : null;
        }
    
        try {
            $coupon->save();
            $coupon->restaurants()->sync($request->restaurant_id);
            return redirect()->back()->with(['success' => 'Coupon Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        }
    }
    /**
     * @param $id
     */
    public function getEditCoupon($id)
    {
        $coupon = Coupon::where('id', $id)->with('restaurant')->first();
        $couponAssignedRestaurants = $coupon->restaurants()->pluck('restaurant_id')->toArray();

        $restaurants = Restaurant::get();
        if ($coupon) {
            return view('admin.editCoupon', array(
                'coupon' => $coupon,
                'restaurants' => $restaurants,
                'couponAssignedRestaurants' => $couponAssignedRestaurants,
            ));
        }
        return redirect()->route('admin.coupons');
    }

    /**
     * @param Request $request
     */
    // public function updateCoupon(Request $request)
    // {
        
        
        
    //     $coupon = Coupon::where('id', $request->id)->first();
        
    //     /* START qusay */
    //     // Verify the existence of the code
    //     $existingCoupon = Coupon::where('code', $request->code)->where('id', '!=', $request->id)->first();
    //     if ($existingCoupon) {
    //         return redirect()->back()->with(['message' => 'The coupon code has already been taken.']);
    //     }
    //     /* END qusay */

    //     if ($coupon) {

    //         $coupon->name = $request->name;
    //         $coupon->description = $request->description;
    //         $coupon->code = $request->code;
    //         $coupon->discount_type = $request->discount_type;
    //         $coupon->discount = $request->discount;
    //         $coupon->expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d H:i:s');
    //         // $coupon->restaurant_id = $request->restaurant_id;
    //         $coupon->max_count = $request->max_count;

    //         $coupon->min_subtotal = $request->min_subtotal == null ? 0 : $request->min_subtotal;

    //         if ($request->discount_type == 'PERCENTAGE') {
    //             $coupon->max_discount = $request->max_discount;
    //         } else {
    //             $coupon->max_discount = null;
    //         }
    //         $coupon->subtotal_message = $request->subtotal_message;

    //         if ($request->is_active == 'true') {
    //             $coupon->is_active = true;
    //         } else {
    //             $coupon->is_active = false;
    //         }

    //         if ($request->show_in_restaurant == 'true') {
    //             $coupon->show_in_restaurant = true;
    //         } else {
    //             $coupon->show_in_restaurant = false;
    //         }
    //         if ($request->show_in_cart == 'true') {
    //             $coupon->show_in_cart = true;
    //         } else {
    //             $coupon->show_in_cart = false;
    //         }
    //         if ($request->show_in_home == 'true') {
    //             $coupon->show_in_home = true;
    //         } else {
    //             $coupon->show_in_home = false;
    //         }

    //         $coupon->user_type = $request->user_type;
    //         if ($request->user_type == 'CUSTOM') {
    //             $coupon->max_count_per_user = $request->max_count_per_user;
    //         }

    //         try {
    //             $coupon->save();
    //             $coupon->restaurants()->sync($request->restaurant_id);
    //             return redirect()->back()->with(['success' => 'Coupon Updated']);
    //         } catch (\Illuminate\Database\QueryException $qe) {
    //             return redirect()->back()->with(['message' => $qe->getMessage()]);
    //         } catch (Exception $e) {
    //             return redirect()->back()->with(['message' => $e->getMessage()]);
    //         } catch (\Throwable $th) {
    //             return redirect()->back()->with(['message' => $th]);
    //         }
    //     }
    // }
    // Extend By Aya ====
    public function updateCoupon(Request $request)
    {
        $coupon = Coupon::where('id', $request->id)->first();
    
        $existingCoupon = Coupon::where('code', $request->code)->where('id', '!=', $request->id)->first();
        if ($existingCoupon) {
            return redirect()->back()->with(['message' => 'The coupon code has already been taken.']);
        }
    
        if ($coupon) {
            $coupon->name = $request->name;
            $coupon->description = $request->description;
            $coupon->code = $request->code;
            $coupon->is_active = $request->is_active == 'true';
            $coupon->show_in_restaurant = $request->show_in_restaurant == 'true';
            $coupon->show_in_cart = $request->show_in_cart == 'true';
            $coupon->show_in_home = $request->show_in_home == 'true';
            $coupon->user_type = $request->user_type;
            $coupon->max_count_per_user = $request->user_type == 'CUSTOM' ? $request->max_count_per_user : null;
            $coupon->is_used_for_delivery = $request->is_used_for_delivery == 'true' || $request->discount_type == 'FREE';
            $coupon->expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d H:i:s');
            $coupon->max_count = $request->max_count;
            $coupon->min_subtotal = $request->min_subtotal ?? 0;
            $coupon->subtotal_message = $request->subtotal_message;
            $coupon->delivery_discount_percentage = $request->delivery_discount_percentage ?? 0;
    
            if ($coupon->is_used_for_delivery) {
                $coupon->discount_type = 'FREE';
                $coupon->discount = 0;
                $coupon->max_discount = null;
            } else {
                $coupon->discount_type = $request->discount_type;
                $coupon->discount = $request->discount;
                $coupon->max_discount = $request->discount_type == 'PERCENTAGE' ? $request->max_discount : null;
            }
    
            try {
                $coupon->save();
                $coupon->restaurants()->sync($request->restaurant_id);
                return redirect()->back()->with(['success' => 'Coupon Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            }
        }
    }
    // ===========================
    /**
     * @param $id
     */
    public function deleteCoupon($id)
    {
        $coupon = Coupon::where('id', $id)->first();

        if ($coupon) {
            $coupon->delete();
            return redirect()->route('admin.coupons');
        }
        return redirect()->route('admin.coupons');
    }

    public function getRestaurantCoupons(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            $restaurant = Restaurant::where('id', $request->restaurant_id)
                ->with(['coupons' => function ($query) {
                    $query->where('is_active', 1)
                        ->where('show_in_cart', 1)
                        ->where('expiry_date', '>', Carbon::now())
                        ->whereColumn('count', '<', 'max_count');
                }, 'zone'])
                ->first();
            $subtotal = floatval($request->subtotal);
            $filteredCoupons = $restaurant->coupons->filter(function ($coupon) use ($subtotal) {
                // If the min_subtotal filter parameter is null or 0, return true to include the coupon in the collection
                if (is_null($coupon->min_subtotal) || $coupon->min_subtotal == 0) {
                    return true;
                }
                // If the coupon's subtotal is greater than or equal to the min_subtotal filter parameter, return true to include the coupon in the collection
                if ($coupon->min_subtotal <= $subtotal) {
                    return true;
                }
            })->values()->toArray(); // ->values() to reset keys in the array

            if (!empty($filteredCoupons)) {
                $response = ['success' => true, 'coupons' => $filteredCoupons];
                return response()->json($response);
            } else {
                $response = ['success' => false, 'message' => "No coupons available", "key" => "NOCOUPONS"];
                return response()->json($response);
            }
        }
    }
}