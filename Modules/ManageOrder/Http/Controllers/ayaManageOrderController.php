<?php

namespace Modules\ManageOrder\Http\Controllers;

use Auth;
use App\User;
use App\Order;
use Exception;
use App\EagleView;
use App\Orderitem;
use App\Restaurant;
use App\CancelReason;
use App\OrderItemAddon;
use App\AcceptDelivery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ManageOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('manageorder::index');
    }

    /**
     * Display the specified order.
     *
     * @param string $order_id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function viewOrder($order_id)
    {
        $user = auth()->user();
        if (config('setting.iHaveFoodomaaDeliveryApp') == "true") {
            $eagleView = new EagleView();
            $eagleViewData = $eagleView->getViewOrderSemiEagleViewData();
            if ($eagleViewData == null) {
                print_r("You have enabled I Have Delivery App in Admin Settings that requires delivery google services file to be correctly set on your server. delivery-google-services.json file is either missing or incorrect.Possible Solutions:Make sure the delivery-google-services.json is present on your server Or disable I Have Delivery App from Admin Settings");
                die();
            }
        } else {
            $eagleViewData = null;
        }
        $order = Order::where('unique_order_id', $order_id)->with('orderitems.order_item_addons', 'rating', 'razorpay_data')->first();
        $zone_id = session('selectedZone');
        if ($zone_id) {
            $users = User::role('Delivery Guy')->with('delivery_guy_detail')->where('zone_id', $zone_id)->get();
        } else {
            $users = User::role('Delivery Guy')->with('delivery_guy_detail')->get();
        }
        if ($order) {
            $cancelReasons = [];
            if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6) {
                $cancelReasons = CancelReason::whereHas('role', function ($q) use ($user) {
                    $q->where('name', $user->roles[0]->name);
                })->get();
            }
            $activities = Activity::where('subject_id', $order->id)->with('causer', 'causer.roles')->orderBy('id', 'DESC')->get();
            return view('manageorder::admin.viewOrder', array('order' => $order, 'users' => $users, 'activities' => $activities, 'eagleViewData' => $eagleViewData, 'cancelReasons' => $cancelReasons));
        } else {
            return redirect()->route('admin.orders');
        }
    }

    /**
     * Update the specified order.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request, $id)
    {
        $author = Auth::user();
        $orderTotal = 0;
        $order = Order::where('id', $id)->with('orderitems.order_item_addons')->lockForUpdate()->first();

        if (!$order) {
            Log::error('Order not found in updateOrder: Order ID ' . $id);
            return redirect()->back()->with(['message' => 'Order not found.']);
        }

        DB::beginTransaction();

        try {
            // تحديث نوع التوصيل
            $originalDeliveryType = $order->delivery_type;
            if ($request->delivery_type && $order->delivery_type != $request->delivery_type) {
                $order->delivery_type = $request->delivery_type;
                Log::info('Delivery type updated', [
                    'order_id' => $order->id,
                    'original_delivery_type' => $originalDeliveryType,
                    'new_delivery_type' => $request->delivery_type
                ]);
            }

            // إعادة المبلغ المدفوع سابقًا إلى المحفظة
            $user = User::where('id', $order->user_id)->first();
            if (!$user) {
                Log::error('User not found for order: Order ID ' . $id . ', User ID ' . $order->user_id);
                throw new Exception('User not found for this order.');
            }
            if ($order->wallet_amount > 0) {
                Log::info('Refunding original wallet amount', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'wallet_amount' => $order->wallet_amount
                ]);
                $user->deposit($order->wallet_amount * 100, ['description' => 'Refund original payment for manage order: ' . $order->unique_order_id]);
                $order->wallet_amount = 0;
            }

            // حساب الإجمالي الفرعي قبل التعديل
            $subTotalBeforeUpdate = 0;
            foreach ($order->orderitems as $item) {
                $itemTotal = ($item->price + $this->calculateAddonTotal($item->order_item_addons)) * $item->quantity;
                $subTotalBeforeUpdate += $itemTotal;
            }

            // تحديث رسوم التوصيل
            $originalDeliveryCharge = $order->delivery_charge;
            $originalActualDeliveryCharge = $order->actual_delivery_charge;
            if ($request->has('free_delivery') && $request->free_delivery == 'true') {
                $distance = $order->distance ?? 0;
                if ($order->restaurant && $order->restaurant->delivery_charge_type == 'DYNAMIC') {
                    if ($distance > $order->restaurant->base_delivery_distance) {
                        $extraDistance = $distance - $order->restaurant->base_delivery_distance;
                        $extraCharge = ($extraDistance / $order->restaurant->extra_delivery_distance) * $order->restaurant->extra_delivery_charge;
                        $dynamicDeliveryCharge = $order->restaurant->base_delivery_charge + $extraCharge;
                        if (config('setting.enDelChrRnd') == 'true') {
                            $dynamicDeliveryCharge = ceil($dynamicDeliveryCharge);
                        }
                        $order->actual_delivery_charge = $dynamicDeliveryCharge;
                        $order->delivery_charge = 0;
                    } else {
                        $order->actual_delivery_charge = $order->restaurant->base_delivery_charge ?? 0;
                        $order->delivery_charge = 0;
                    }
                } else {
                    $order->actual_delivery_charge = $order->restaurant->delivery_charges ?? 0;
                    $order->delivery_charge = 0;
                }
                Log::info('Delivery charge set to 0 due to free delivery selected', [
                    'order_id' => $order->id,
                    'actual_delivery_charge' => $order->actual_delivery_charge
                ]);
            } elseif ($request->has('delivery_charge') && $request->filled('editDeliveryCharge')) {
                $order->delivery_charge = $request->delivery_charge ?? 0;
                $order->actual_delivery_charge = $request->delivery_charge ?? 0;
                Log::info('Delivery charge updated from request', [
                    'order_id' => $order->id,
                    'delivery_charge' => $order->delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge
                ]);
            } else {
                // الاحتفاظ بالقيم الحالية إذا لم يتم تحديد تغيير
                $order->delivery_charge = $originalDeliveryCharge;
                $order->actual_delivery_charge = $originalActualDeliveryCharge;
                Log::info('Delivery charge retained', [
                    'order_id' => $order->id,
                    'delivery_charge' => $order->delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge
                ]);
            }

            // تحديث رسوم المطعم
            if ($request->has('restaurant_charge')) {
                $order->restaurant_charge = $request->restaurant_charge ?? 0;
            }

            // إلغاء ارتباط السائق إذا تغير إلى استلام شخصي فقط
            if ($originalDeliveryType == '1' && $request->delivery_type == '2' && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                $acceptDelivery = AcceptDelivery::where('order_id', $order->id)->first();
                if ($acceptDelivery) {
                    try {
                        $deliveryGuyId = $acceptDelivery->user_id;
                        $acceptDelivery->delete();
                        activity()
                            ->performedOn($order)
                            ->causedBy($author)
                            ->withProperties(['type' => 'Delivery_Canceled_Due_To_Self_Pickup', 'delivery_guy_id' => $deliveryGuyId])
                            ->log('Delivery canceled because order changed to Self-Pickup');
                        Log::info('Delivery assignment removed due to change to Self-Pickup', [
                            'order_id' => $order->id,
                            'delivery_guy_id' => $deliveryGuyId
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete AcceptDelivery for order: Order ID ' . $id . ', Error: ' . $e->getMessage());
                        throw new Exception('Failed to delete delivery assignment: ' . $e->getMessage());
                    }
                } else {
                    Log::info('No delivery assignment found to remove for Self-Pickup', [
                        'order_id' => $order->id
                    ]);
                }
            } else {
                // الاحتفاظ بالسائق الحالي إذا لم يتغير نوع التوصيل
                $acceptDelivery = AcceptDelivery::where('order_id', $order->id)->first();
                if ($acceptDelivery) {
                    Log::info('Delivery assignment retained', [
                        'order_id' => $order->id,
                        'delivery_guy_id' => $acceptDelivery->user_id
                    ]);
                }
            }

            $order->save();

            // حذف العناصر
            $item_ids = $request->item_ids;
            if (!is_null($item_ids)) {
                $item_ids = explode(',', $item_ids);
                if (count($item_ids) > 0) {
                    foreach ($item_ids as $item_id) {
                        $order_item = Orderitem::find($item_id);
                        if ($order_item) {
                            Log::info('Deleting order item', ['item_id' => $item_id]);
                            $order_item_addons = OrderItemAddon::where('orderitem_id', $item_id)->get();
                            foreach ($order_item_addons as $order_item_addon) {
                                $order_item_addon->delete();
                            }
                            $order_item->delete();
                        }
                    }
                }
            }

            // تحديث العناصر الحالية
            if ($request->has('existing_items')) {
                foreach ($request->existing_items as $itemId => $itemData) {
                    $item = Orderitem::find($itemId);
                    if ($item && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                        // لا يتم تحديث الكمية أو السعر لأن الحقول معطلة
                        $item->name = $itemData['name'] ?? $item->name;
                        Log::info('Updating existing item', [
                            'item_id' => $itemId,
                            'name' => $item->name
                        ]);
                        $item->save();
                    }
                }
            }

            // إضافة عناصر جديدة
            $name = $request->name;
            $quantity = $request->quantity;
            $price = $request->price;
            if (!empty($name)) {
                foreach ($name as $key => $na_me) {
                    if (!is_null($name[$key]) && !is_null($quantity[$key]) && !is_null($price[$key]) && $quantity[$key] > 0 && $price[$key] >= 0) {
                        $item = new Orderitem();
                        $item->order_id = $id;
                        $item->item_id = 0;
                        $item->name = $name[$key];
                        $item->quantity = $quantity[$key];
                        $item->price = $price[$key];
                        if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                            Log::info('Adding new item', [
                                'order_id' => $id,
                                'name' => $name[$key],
                                'quantity' => $quantity[$key],
                                'price' => $price[$key]
                            ]);
                            $item->save();
                        }
                    }
                }
            }

            // التحقق من حالة الطلب
            if ($order->orderstatus_id == 5) {
                DB::rollBack();
                return redirect()->back()->with(['message' => 'You can\'t manage this order as the order already completed!']);
            } elseif ($order->orderstatus_id == 6) {
                DB::rollBack();
                return redirect()->back()->with(['message' => 'You can\'t manage this order as the order already cancelled!']);
            }

            // إعادة تحميل الطلب لحساب الإجمالي
            $order = Order::with('orderitems.order_item_addons')->findOrFail($id);
            foreach ($order->orderitems as $item) {
                $itemTotal = ($item->price + $this->calculateAddonTotal($item->order_item_addons)) * $item->quantity;
                $orderTotal += $itemTotal;
            }
            $order->sub_total = $orderTotal;

            // معالجة الكوبون
            if ($order->coupon_name && $order->delivery_charge == 0) {
                $coupon = Coupon::where('code', $order->coupon_name)->first();
                if ($coupon && $coupon->is_used_for_delivery) {
                    Log::info('Coupon ignored: Delivery charge is zero', [
                        'order_id' => $order->id,
                        'coupon_code' => $order->coupon_name,
                        'delivery_charge' => $order->delivery_charge
                    ]);
                    $order->coupon_amount = 0;
                }
            }

            // حساب العمولة
            $order->commission_rate = $order->restaurant->commission_rate ?? 0;
            $order->commission_amount = $order->commission_rate * $order->sub_total / 100;
            $order->restaurant_net_amount = $order->sub_total + ($order->restaurant_charge ?? 0) - ($order->coupon_amount ?? 0) - $order->commission_amount;

            // حساب الضرائب
            if (config('setting.taxApplicable') == 'true') {
                $order->tax = config('setting.taxPercentage') ?? 0;
                $taxAmount = (float) (((float) config('setting.taxPercentage') / 100) * $orderTotal);
            } else {
                $taxAmount = 0;
            }
            $order->tax_amount = $taxAmount;
            $order->restaurant_net_amount += $order->tax_amount;

            // حساب الإجمالي النهائي
            $orderTotal += $taxAmount;
            $orderTotal -= $order->coupon_amount ?? 0;
            $orderTotal += $order->delivery_charge ?? 0;
            $orderTotal += $order->restaurant_charge ?? 0;
            $orderTotal += $order->tip_amount ?? 0;
            $order->total = max(0, $orderTotal);

            // حساب الربح النهائي
            $order->final_profit = $order->commission_amount + ($order->delivery_charge ?? 0);
            if ($order->delivery_charge == 0) {
                $order->final_profit -= $order->actual_delivery_charge ?? 0;
            }

            // تحديث الدفع
            if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                $newTotal = $orderTotal;
                $availableBalance = $user->balanceFloat ?? 0;
                Log::info('Updating payment', [
                    'order_id' => $order->id,
                    'new_total' => $newTotal,
                    'available_balance' => $availableBalance,
                    'original_payment_mode' => $order->payment_mode
                ]);

                if ($newTotal <= 0) {
                    $order->wallet_amount = 0;
                    $order->payable = 0;
                    $order->payment_mode = 'WALLET';
                } elseif ($order->payment_mode == 'WALLET') {
                    if ($newTotal > $availableBalance) {
                        $order->wallet_amount = $availableBalance;
                        $order->payable = $newTotal - $availableBalance;
                        $order->payment_mode = 'PARTIAL';
                        $order->restaurant_net_amount += $order->payable;
                        if ($availableBalance > 0) {
                            $user->withdraw($availableBalance * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                            Log::info('Withdrawing from wallet', [
                                'order_id' => $order->id,
                                'amount' => $availableBalance
                            ]);
                        }
                    } else {
                        $order->wallet_amount = $newTotal;
                        $order->payable = 0;
                        $order->payment_mode = 'WALLET';
                        $user->withdraw($newTotal * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                        Log::info('Withdrawing from wallet', [
                            'order_id' => $order->id,
                            'amount' => $newTotal
                        ]);
                    }
                } elseif ($order->payment_mode == 'COD') {
                    if ($availableBalance >= $newTotal) {
                        $order->wallet_amount = $newTotal;
                        $order->payable = 0;
                        $order->payment_mode = 'WALLET';
                        $user->withdraw($newTotal * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                        Log::info('Withdrawing from wallet', [
                            'order_id' => $order->id,
                            'amount' => $newTotal
                        ]);
                    } else {
                        $order->wallet_amount = $availableBalance;
                        $order->payable = $newTotal - $availableBalance;
                        $order->payment_mode = 'PARTIAL';
                        $order->restaurant_net_amount += $order->payable;
                        if ($availableBalance > 0) {
                            $user->withdraw($availableBalance * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                            Log::info('Withdrawing from wallet', [
                                'order_id' => $order->id,
                                'amount' => $availableBalance
                            ]);
                        }
                    }
                } else {
                    // لطرق الدفع الأخرى (مثل RAZORPAY)
                    if ($newTotal > $availableBalance) {
                        $order->wallet_amount = $availableBalance;
                        $order->payable = $newTotal - $availableBalance;
                        $order->payment_mode = 'PARTIAL';
                        $order->restaurant_net_amount += $order->payable;
                        if ($availableBalance > 0) {
                            $user->withdraw($availableBalance * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                            Log::info('Withdrawing from wallet', [
                                'order_id' => $order->id,
                                'amount' => $availableBalance
                            ]);
                        }
                    } else {
                        $order->wallet_amount = $newTotal;
                        $order->payable = 0;
                        $order->payment_mode = 'WALLET';
                        $user->withdraw($newTotal * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                        Log::info('Withdrawing from wallet', [
                            'order_id' => $order->id,
                            'amount' => $newTotal
                        ]);
                    }
                }
            }

            $order->save();
            DB::commit();
            Log::info('Order updated successfully', [
                'order_id' => $order->id,
                'total' => $order->total,
                'wallet_amount' => $order->wallet_amount,
                'payable' => $order->payable,
                'payment_mode' => $order->payment_mode,
                'delivery_charge' => $order->delivery_charge,
                'actual_delivery_charge' => $order->actual_delivery_charge
            ]);
            activity()->performedOn($order)->causedBy($author)->withProperties(['type' => 'Order_Modified'])->log('Order modified');
            return redirect()->back()->with(['success' => 'Order updated!']);
        } catch (\Illuminate\Database\QueryException $qe) {
            DB::rollBack();
            Log::error('Database error in updateOrder: Order ID ' . $id . ', Error: ' . $qe->getMessage());
            return redirect()->back()->with(['message' => 'A database error occurred. Please try again.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('General error in updateOrder: Order ID ' . $id . ', Error: ' . $e->getMessage());
            return redirect()->back()->with(['message' => 'An error occurred: ' . $e->getMessage()]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Unexpected error in updateOrder: Order ID ' . $id . ', Error: ' . $th->getMessage());
            return redirect()->back()->with(['message' => 'An unexpected error occurred: ' . $th->getMessage()]);
        }
    }

    /**
     * Calculate the total price of addons.
     *
     * @param \Illuminate\Database\Eloquent\Collection $addons
     * @return float
     */
    public function calculateAddonTotal($addons)
    {
        $total = 0;
        foreach ($addons as $addon) {
            $total += $addon->addon_price;
        }
        return $total;
    }
}