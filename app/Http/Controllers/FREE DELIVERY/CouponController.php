<?php

namespace App\Http\Controllers;

use Auth;
use App\Order;
use Exception;
use App\Coupon;
use Carbon\Carbon;
use App\Restaurant;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * @param Request $request
     */
    public function applyCoupon(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $response = [
                'success' => false,
                'type' => 'NOTLOGGEDIN',
            ];
            return response()->json($response);
        }

        $coupon = Coupon::where('code', $request->coupon)->first();

        if ($coupon && $coupon->is_active) {

            //check if coupon belongs to the restaurant
            if (in_array($request->restaurant_id, $coupon->restaurants()->pluck('restaurant_id')->toArray())) {
                //check if expirty date is correct
                if ($coupon->expiry_date->gt(Carbon::now()) && $coupon->count < $coupon->max_count) {
                    //check if min-subtotal is proper
                    if ($request->subtotal >= $coupon->min_subtotal) {
                        //get user orders
                        $userOrderCount = count($user->orders);

                        if ($coupon->user_type == 'ONCE') {
                            $orderAlreadyPlacedWithCoupon = Order::where('user_id', $user->id)->where('coupon_name', $coupon->code)->first();
                            if ($orderAlreadyPlacedWithCoupon) {
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
                                $response = [
                                    'success' => false,
                                    'type' => 'MAXLIMITREACHEDPERUSER',
                                    'message' => 'Max limit reached for this coupon',
                                ];
                                return response()->json($response);
                            }
                        }
                        $coupon->success = true;
                        return response()->json($coupon);
                    } else {
                        $response = [
                            'success' => false,
                            'type' => 'MINSUBTOTAL',
                            'message' => $coupon->subtotal_message,
                        ];
                        return response()->json($response);
                    }
                } else {
                    $response = [
                        'success' => false,
                    ];
                    return response()->json($response);
                }
            } else {
                $response = [
                    'success' => false,
                ];
                return response()->json($response);
            }
        } else {
            $response = [
                'success' => false,
            ];
            return response()->json($response);
        }
    }

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
    public function saveNewCoupon(Request $request)
    {
        // dd($request->all());
        
        /* START qusay */
        // Verify the existence of the code
        $existingCoupon = Coupon::where('code', $request->code)->first();
        if ($existingCoupon) {
            return redirect()->back()->with(['message' => 'The coupon code has already been taken.']);
        }
        /* END qusay */
        
        $coupon = new Coupon();

        $coupon->name = $request->name;
        $coupon->description = $request->description;
        $coupon->code = $request->code;
        $coupon->discount_type = $request->discount_type;
        $coupon->discount = $request->discount;
        $coupon->expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d H:i:s');
        // $coupon->restaurant_id = $request->restaurant_id;

        $coupon->max_count = $request->max_count;

        $coupon->min_subtotal = $request->min_subtotal == null ? 0 : $request->min_subtotal;
        if ($request->discount_type == 'PERCENTAGE') {
            $coupon->max_discount = $request->max_discount;
        } else {
            $coupon->max_discount = null;
        }
        $coupon->subtotal_message = $request->subtotal_message;

        if ($request->is_active == 'true') {
            $coupon->is_active = true;
        } else {
            $coupon->is_active = false;
        }
        if ($request->show_in_restaurant == 'true') {
            $coupon->show_in_restaurant = true;
        } else {
            $coupon->show_in_restaurant = false;
        }
        if ($request->show_in_cart == 'true') {
            $coupon->show_in_cart = true;
        } else {
            $coupon->show_in_cart = false;
        }
        if ($request->show_in_home == 'true') {
            $coupon->show_in_home = true;
        } else {
            $coupon->show_in_home = false;
        }

        $coupon->user_type = $request->user_type;
        if ($request->user_type == 'CUSTOM') {
            $coupon->max_count_per_user = $request->max_count_per_user;
        }

        try {
            $coupon->save();
            $coupon->restaurants()->sync($request->restaurant_id);
            return redirect()->back()->with(['success' => 'Coupon Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
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
    public function updateCoupon(Request $request)
    {
        
        
        
        $coupon = Coupon::where('id', $request->id)->first();
        
        /* START qusay */
        // Verify the existence of the code
        $existingCoupon = Coupon::where('code', $request->code)->where('id', '!=', $request->id)->first();
        if ($existingCoupon) {
            return redirect()->back()->with(['message' => 'The coupon code has already been taken.']);
        }
        /* END qusay */

        if ($coupon) {

            $coupon->name = $request->name;
            $coupon->description = $request->description;
            $coupon->code = $request->code;
            $coupon->discount_type = $request->discount_type;
            $coupon->discount = $request->discount;
            $coupon->expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d H:i:s');
            // $coupon->restaurant_id = $request->restaurant_id;
            $coupon->max_count = $request->max_count;

            $coupon->min_subtotal = $request->min_subtotal == null ? 0 : $request->min_subtotal;

            if ($request->discount_type == 'PERCENTAGE') {
                $coupon->max_discount = $request->max_discount;
            } else {
                $coupon->max_discount = null;
            }
            $coupon->subtotal_message = $request->subtotal_message;

            if ($request->is_active == 'true') {
                $coupon->is_active = true;
            } else {
                $coupon->is_active = false;
            }

            if ($request->show_in_restaurant == 'true') {
                $coupon->show_in_restaurant = true;
            } else {
                $coupon->show_in_restaurant = false;
            }
            if ($request->show_in_cart == 'true') {
                $coupon->show_in_cart = true;
            } else {
                $coupon->show_in_cart = false;
            }
            if ($request->show_in_home == 'true') {
                $coupon->show_in_home = true;
            } else {
                $coupon->show_in_home = false;
            }

            $coupon->user_type = $request->user_type;
            if ($request->user_type == 'CUSTOM') {
                $coupon->max_count_per_user = $request->max_count_per_user;
            }

            try {
                $coupon->save();
                $coupon->restaurants()->sync($request->restaurant_id);
                return redirect()->back()->with(['success' => 'Coupon Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

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
