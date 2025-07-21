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
use App\Coupon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Schema;
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


    //   public function updateOrder(Request $request, $id)
    // {
    //     $author = Auth::user();
    //     $orderTotal = 0;
    //     $logger = Log::channel('update_order');
    //     $order = Order::where('id', $id)->with(['orderitems.order_item_addons', 'restaurant', 'accept_delivery.user.delivery_guy_detail'])->lockForUpdate()->first();
    
    //     // Log incoming request data
    //     Log::channel('update_order')->info('Received updateOrder request', [
    //         'order_id' => $id,
    //         'request_data' => $request->all(),
    //         'delivery_type' => $request->delivery_type,
    //         'has_delivery_type' => $request->has('delivery_type')
    //     ]);
    
    //     if (!$order) {
    //         Log::channel('update_order')->error('Order not found in updateOrder: Order ID ' . $id);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Order not found.'
    //         ], 404);
    //     }
    
    //     DB::beginTransaction();
    
    //     try {
    //         // Check if user is a guest based on email
    //         $isGuestUser = false;
    //         $user = User::where('id', $order->user_id)->first();
    //         if ($user && strpos($user->email, '@app.motoboxapp.com') !== false) {
    //             $isGuestUser = true;
    //             Log::channel('update_order')->info('Guest user detected', [
    //                 'order_id' => $order->id,
    //                 'user_id' => $user->id,
    //                 'email' => $user->email
    //             ]);
    //         }
    
    //         // Refund previous wallet payment
    //         if (!$user) {
    //             Log::channel('update_order')->error('User not found for order: Order ID ' . $id . ', User ID ' . $order->user_id);
    //             throw new Exception('User not found for this order.');
    //         }
    //         if ($order->wallet_amount > 0) {
    //             Log::channel('update_order')->info('Refunding original wallet amount before update', [
    //                 'order_id' => $order->id,
    //                 'user_id' => $user->id,
    //                 'wallet_amount' => $order->wallet_amount
    //             ]);
    //             $user->deposit($order->wallet_amount * 100, ['description' => 'Refund original payment for manage order: ' . $order->unique_order_id]);
    //             $order->wallet_amount = 0;
    //         }
    
    //         // Store original coupon and delivery charge details
    //         $original_coupon_name = $order->coupon_name;
    //         $original_coupon_amount = $order->coupon_amount;
    //         $original_is_free_delivery = $order->is_free_delivery;
    //         $original_coupon = $original_coupon_name ? Coupon::where('code', $original_coupon_name)->first() : null;
    //         $is_coupon_valid = true;
    
    //         // Preserve coupon unless explicitly requested to remove or invalid for non-FREE types
    //         if ($original_coupon && $original_coupon->discount_type !== 'FREE' && $original_coupon->discount_type === 'FIXED') {
    //             if ($order->sub_total < ($original_coupon->min_subtotal ?? 0)) {
    //                 $is_coupon_valid = false;
    //                 Log::channel('update_order')->info('Fixed coupon removed due to insufficient subtotal', [
    //                     'order_id' => $order->id,
    //                     'sub_total' => $order->sub_total,
    //                     'min_subtotal' => $original_coupon->min_subtotal
    //                 ]);
    //             }
    //         }
    
    //         // Update delivery type
    //         $originalDeliveryType = $order->delivery_type;
    //         Log::channel('update_order')->info('Before updating delivery type', [
    //             'order_id' => $order->id,
    //             'original_delivery_type' => $originalDeliveryType,
    //             'request_delivery_type' => $request->delivery_type
    //         ]);
    //         if ($request->delivery_type && $order->delivery_type != $request->delivery_type) {
    //             $order->delivery_type = $request->delivery_type;
    //             Log::channel('update_order')->info('Delivery type updated', [
    //                 'order_id' => $order->id,
    //                 'original_delivery_type' => $originalDeliveryType,
    //                 'new_delivery_type' => $order->delivery_type
    //             ]);
    //         } else {
    //             Log::channel('update_order')->info('No delivery type update needed', [
    //                 'order_id' => $order->id,
    //                 'original_delivery_type' => $originalDeliveryType,
    //                 'request_delivery_type' => $request->delivery_type
    //             ]);
    //         }
    
    //         // Update restaurant charge
    //         if ($request->has('restaurant_charge')) {
    //             $order->restaurant_charge = (float) ($request->restaurant_charge ?? 0);
    //         }
    
    //         // Delete AcceptDelivery when switching to Self-Pickup
    //         if ($originalDeliveryType == '1' && $request->delivery_type == '2' && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
    //             $acceptDeliveries = AcceptDelivery::where('order_id', $order->id)->get();
    //             if ($acceptDeliveries->isNotEmpty()) {
    //                 try {
    //                     foreach ($acceptDeliveries as $acceptDelivery) {
    //                         $deliveryGuyId = $acceptDelivery->user_id;
    //                         $acceptDelivery->delete();
    //                         Log::channel('update_order')->info('AcceptDelivery deleted successfully', [
    //                             'order_id' => $order->id,
    //                             'delivery_guy_id' => $deliveryGuyId
    //                         ]);
    //                         activity()
    //                             ->performedOn($order)
    //                             ->causedBy($author)
    //                             ->withProperties(['type' => 'Delivery_Canceled_Due_To_Self_Pickup', 'delivery_guy_id' => $deliveryGuyId])
    //                             ->log('Delivery canceled because order changed to Self-Pickup');
    //                     }
    //                 } catch (\Exception $e) {
    //                     Log::channel('update_order')->error('Failed to delete AcceptDelivery', [
    //                         'order_id' => $order->id,
    //                         'error' => $e->getMessage()
    //                     ]);
    //                     throw new Exception('Failed to delete delivery assignment: ' . $e->getMessage());
    //                 }
    //             } else {
    //                 Log::channel('update_order')->info('No delivery assignment found to remove for Self-Pickup', [
    //                     'order_id' => $order->id
    //                 ]);
    //             }
    //         }
    
    //         // Delete items
    //         $item_ids = $request->item_ids;
    //         if (!is_null($item_ids)) {
    //             $item_ids = explode(',', $item_ids);
    //             if (count($item_ids) > 0) {
    //                 foreach ($item_ids as $item_id) {
    //                     $order_item = Orderitem::find($item_id);
    //                     if ($order_item) {
    //                         Log::channel('update_order')->info('Deleting order item', ['item_id' => $item_id]);
    //                         $order_item_addons = OrderItemAddon::where('orderitem_id', $item_id)->get();
    //                         foreach ($order_item_addons as $order_item_addon) {
    //                             $order_item_addon->delete();
    //                         }
    //                         $order_item->delete();
    //                     }
    //                 }
    //             }
    //         }
    
    //         // Update existing items
    //         if ($request->has('existing_items')) {
    //             foreach ($request->existing_items as $itemId => $itemData) {
    //                 $item = Orderitem::find($itemId);
    //                 if ($item && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
    //                     if (isset($itemData['quantity']) && $itemData['quantity'] <= 0) {
    //                         Log::channel('update_order')->error('Invalid quantity for algorithmic item', [
    //                             'item_id' => $itemId,
    //                             'quantity' => $itemData['quantity']
    //                         ]);
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => 'Quantity for item ID ' . $itemId . ' must be greater than 0.'
    //                         ], 422);
    //                     }
    //                     if (isset($itemData['price']) && $itemData['price'] < 0) {
    //                         Log::channel('update_order')->error('Invalid price for algorithmic item', [
    //                             'item_id' => $itemId,
    //                             'price' => $itemData['price']
    //                         ]);
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => 'Price for item ID ' . $itemId . ' must be non-negative.'
    //                         ], 422);
    //                     }
    //                     $item->name = $itemData['name'] ?? $item->name;
    //                     $item->quantity = isset($itemData['quantity']) && $itemData['quantity'] > 0 ? (int)$itemData['quantity'] : $item->quantity;
    //                     $item->price = isset($itemData['price']) && $itemData['price'] >= 0 ? (float)$itemData['price'] : $item->price;
    //                     Log::channel('update_order')->info('Updating algorithmic item', [
    //                         'item_id' => $itemId,
    //                         'name' => $item->name,
    //                         'quantity' => $item->quantity,
    //                         'price' => $item->price
    //                     ]);
    //                     $item->save();
    //                 }
    //             }
    //         }
    
    //         // Add new items with validation
    //         $validNewItems = 0;
    //         $name = $request->name;
    //         $quantity = $request->quantity;
    //         $price = $request->price;
    //         if (!empty($name)) {
    //             foreach ($name as $key => $na_me) {
    //                 if (!is_null($name[$key]) && !empty(trim($name[$key])) && !is_null($quantity[$key]) && !is_null($price[$key]) && $quantity[$key] > 0) {
    //                     $item = new Orderitem();
    //                     $item->order_id = $id;
    //                     $item->item_id = 0;
    //                     $item->name = trim($name[$key]);
    //                     $item->quantity = $quantity[$key];
    //                     $item->price = (float) $price[$key];
    //                     if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
    //                         Log::channel('update_order')->info('Adding new item', [
    //                             'order_id' => $id,
    //                             'name' => $name[$key],
    //                             'quantity' => $quantity[$key],
    //                             'price' => $price[$key]
    //                         ]);
    //                         $item->save();
    
    //                         // Add addons
    //                         $addons = $request->input('addons', [])[$key] ?? [];
    //                         $addonTotal = 0;
    //                         foreach ($addons as $addonData) {
    //                             $orderItemAddon = new OrderItemAddon();
    //                             $orderItemAddon->orderitem_id = $item->id;
    //                             $orderItemAddon->name = $addonData['name'];
    //                             $orderItemAddon->addon_price = (float) ($addonData['price'] ?? 0);
    //                             $orderItemAddon->save();
    //                             $addonTotal += $orderItemAddon->addon_price;
    //                             Log::channel('update_order')->info('Adding addon for new item', [
    //                                 'order_id' => $id,
    //                                 'item_id' => $item->id,
    //                                 'addon_name' => $addonData['name'],
    //                                 'addon_price' => $orderItemAddon->addon_price
    //                             ]);
    //                         }
    
    //                         // Validate item
    //                         if (empty(trim($item->name)) || $item->quantity <= 0) {
    //                             $item->delete();
    //                             Log::channel('update_order')->warning('Invalid item with empty name or zero quantity', [
    //                                 'order_id' => $id,
    //                                 'item_id' => $item->id
    //                             ]);
    //                             continue;
    //                         }
    //                         $validNewItems++;
    //                     }
    //                 }
    //             }
    //         }
    
    //         $order->load('orderitems.order_item_addons');
    //         // Check if there are valid items
    //         if ($order->orderitems->isEmpty() && $validNewItems == 0) {
    //             DB::rollBack();
    //             Log::channel('update_order')->error('No valid items in order after processing', [
    //                 'order_id' => $id,
    //                 'valid_new_items' => $validNewItems
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'The order must contain at least one valid item (non-empty name, quantity greater than 0, and non-negative price).'
    //             ], 422);
    //         }
    
    //         $order->load('orderitems.order_item_addons');
    //         // Calculate subtotal
    //         $orderTotal = 0;
    //         foreach ($order->orderitems as $item) {
    //             $itemTotal = ($item->price + $this->calculateAddonTotal($item->order_item_addons)) * $item->quantity;
    //             $orderTotal += $itemTotal;
    //         }
    //         $order->sub_total = $orderTotal;
    //         $adjusted_total = $order->sub_total;
    
    //         // Re-validate coupon after calculating new subtotal, but preserve FREE coupon
    //         if ($original_coupon && $original_coupon->discount_type !== 'FREE' && $original_coupon->discount_type === 'FIXED') {
    //             if ($order->sub_total < ($original_coupon->min_subtotal ?? 0)) {
    //                 $is_coupon_valid = false;
    //                 Log::channel('update_order')->info('Fixed coupon removed due to insufficient subtotal after recalculation', [
    //                     'order_id' => $order->id,
    //                     'sub_total' => $order->sub_total,
    //                     'min_subtotal' => $original_coupon->min_subtotal
    //                 ]);
    //             }
    //         } elseif ($original_coupon && $original_coupon->discount_type === 'FREE') {
    //             Log::channel('update_order')->info('Preserving FREE coupon regardless of item changes', [
    //                 'order_id' => $order->id,
    //                 'coupon_name' => $original_coupon_name,
    //                 'coupon_amount' => $original_coupon_amount
    //             ]);
    //         }
    
    //         // Remove coupon if invalid (except for FREE coupon)
    //         if (!$is_coupon_valid && $original_coupon_name && $original_coupon && $original_coupon->discount_type !== 'FREE') {
    //             $order->coupon_name = null;
    //             $order->coupon_amount = 0;
    //             Log::channel('update_order')->info('Non-FREE coupon removed due to invalid conditions', [
    //                 'order_id' => $order->id,
    //                 'coupon_name' => $original_coupon_name,
    //                 'sub_total' => $order->sub_total
    //             ]);
    //         }
    
    //         // Restaurant settings
    //         $min_order_price = (float) ($order->restaurant->min_order_price ?? 0);
    //         $free_delivery_subtotal = (float) ($order->restaurant->free_delivery_subtotal ?? 120000);
    //         $free_delivery_distance = (float) ($order->restaurant->free_delivery_distance ?? 4);
    //         $free_delivery_cost = (float) ($order->restaurant->free_delivery_cost ?? 10000);
    //         $free_delivery_comm = (float) ($order->restaurant->free_delivery_comm ?? 40);
    //         $delivery_distance = (float) ($order->distance ?? 0);
    //         if ($request->has('distance') && $request->distance != $order->distance) {
    //             $delivery_distance = (float) $request->distance;
    //             $order->distance = $delivery_distance;
    //         }
    
    //         // Ensure free_delivery_cost and free_delivery_comm are set
    //         if ($free_delivery_cost <= 0) {
    //             $free_delivery_cost = 10000; // Default value
    //             $order->restaurant->free_delivery_cost = $free_delivery_cost;
    //             $order->restaurant->save();
    //             Log::channel('update_order')->info('Set default free_delivery_cost for restaurant', [
    //                 'restaurant_id' => $order->restaurant->id,
    //                 'free_delivery_cost' => $free_delivery_cost
    //             ]);
    //         }
    //         if ($free_delivery_comm <= 0) {
    //             $free_delivery_comm = 40; // Default value
    //             $order->restaurant->free_delivery_comm = $free_delivery_comm;
    //             $order->restaurant->save();
    //             Log::channel('update_order')->info('Set default free_delivery_comm for restaurant', [
    //                 'restaurant_id' => $order->restaurant->id,
    //                 'free_delivery_comm' => $free_delivery_comm
    //             ]);
    //         }
    
    //         // Calculate delivery charges based on distance
    //         $base_delivery_charge = (float) ($order->restaurant->base_delivery_charge ?? 14000);
    //         $base_delivery_distance = (float) ($order->restaurant->base_delivery_distance ?? 4);
    //         $extra_delivery_charge = (float) ($order->restaurant->extra_delivery_charge ?? 4000);
    //         $extra_delivery_distance = (float) ($order->restaurant->extra_delivery_distance ?? 1);
    
    //         // Keep current delivery_charge by default
    //         $adjusted_delivery_charge = $order->delivery_charge;
    //         $recalculate_delivery_charge = false;
    
    //         // Check minimum order before and after update
    //         $was_below_min_order = $order->sub_total < $min_order_price;
    //         $is_below_min_order = $adjusted_total < $min_order_price;
    
    //         // Check if delivery charge is manually updated from frontend
    //         $is_manual_delivery_charge_update = $request->has('delivery_charge') && $request->filled('delivery_charge') && is_numeric($request->delivery_charge);
    //         $is_manual_free_delivery = $request->has('free_delivery') && $request->free_delivery == 'true' && $request->delivery_type != '2';
    
    //         // Bypass min_order_price check for manual delivery charge update or manual free delivery
    //         if ($is_manual_delivery_charge_update || $is_manual_free_delivery) {
    //             $is_below_min_order = false;
    //             Log::channel('update_order')->info('Bypassing min_order_price check due to manual delivery charge update or manual free delivery', [
    //                 'order_id' => $order->id,
    //                 'sub_total' => $adjusted_total,
    //                 'min_order_price' => $min_order_price,
    //                 'is_manual_delivery_charge_update' => $is_manual_delivery_charge_update,
    //                 'is_manual_free_delivery' => $is_manual_free_delivery,
    //                 'delivery_charge' => $request->delivery_charge
    //             ]);
    //         }
    
    //         // Check free delivery eligibility based on restaurant settings or new sub_total
    //         $was_free_delivery_eligible = $order->is_free_delivery;
    //         $is_free_delivery_eligible_by_settings = (
    //             $free_delivery_subtotal > 0 &&
    //             $delivery_distance <= $free_delivery_distance &&
    //             $adjusted_total >= $free_delivery_subtotal &&
    //             !$is_below_min_order &&
    //             !$is_manual_delivery_charge_update &&
    //             !$is_manual_free_delivery &&
    //             (!$original_coupon || $original_coupon->discount_type !== 'FREE')
    //         );
    
    //         // Handle free delivery and delivery charges
    //         $coupon_delivery_discount = 0;
    //         $is_free_delivery_eligible = false;
    
    //         // Check if delivery charge needs recalculation
    //         if (
    //             ($request->has('distance') && $request->distance != $order->distance) ||
    //             ($request->has('delivery_type') && $request->delivery_type == '1' && $originalDeliveryType == '2') ||
    //             ($was_free_delivery_eligible && !$is_free_delivery_eligible_by_settings && $order->delivery_type == '1' && (!$original_coupon || $original_coupon->discount_type !== 'FREE'))
    //         ) {
    //             $recalculate_delivery_charge = true;
    //             Log::channel('update_order')->info('Triggering delivery charge recalculation', [
    //                 'order_id' => $order->id,
    //                 'reason' => ($was_free_delivery_eligible && !$is_free_delivery_eligible_by_settings) ? 'Lost free delivery eligibility' : 'Distance or delivery type changed',
    //                 'was_free_delivery_eligible' => $was_free_delivery_eligible,
    //                 'is_free_delivery_eligible_by_settings' => $is_free_delivery_eligible_by_settings,
    //                 'sub_total' => $adjusted_total,
    //                 'min_order_price' => $min_order_price,
    //                 'free_delivery_subtotal' => $free_delivery_subtotal
    //             ]);
    //         }
    
    //         // Handle manual delivery charge update first
    //         if ($is_manual_delivery_charge_update) {
    //             $newDeliveryCharge = (float) ($request->delivery_charge ?? 0);
    //             if ($newDeliveryCharge < 0) {
    //                 Log::channel('update_order')->error('Invalid delivery charge provided', [
    //                     'order_id' => $order->id,
    //                     'delivery_charge' => $newDeliveryCharge
    //                 ]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Delivery charge cannot be negative.'
    //                 ], 422);
    //             }
    //             $order->delivery_charge = $newDeliveryCharge;
    //             $order->actual_delivery_charge = $newDeliveryCharge;
    //             $order->is_free_delivery = false; // Force non-free delivery for manual charge
    //             $adjusted_delivery_charge = $newDeliveryCharge;
    //             if ($order->coupon_name == 'FREESHIP' || ($original_coupon && $original_coupon->discount_type === 'FREE')) {
    //                 $order->coupon_name = null;
    //                 $order->coupon_amount = 0;
    //                 $coupon_delivery_discount = 0;
    //             }
    //             Log::channel('update_order')->info('Delivery charge updated manually from frontend, treating order as regular (no min_order_price restriction)', [
    //                 'order_id' => $order->id,
    //                 'delivery_charge' => $newDeliveryCharge,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge,
    //                 'is_guest_user' => $isGuestUser,
    //                 'adjusted_total' => $adjusted_total,
    //                 'delivery_distance' => $delivery_distance,
    //                 'min_order_price_bypassed' => true,
    //                 'is_free_delivery' => $order->is_free_delivery
    //             ]);
    //         } elseif ($is_manual_free_delivery) {
    //             // Handle manual free delivery
    //             if ($is_free_delivery_eligible_by_settings) {
    //                 DB::rollBack();
    //                 Log::channel('update_order')->error('Manual free delivery not allowed when order is eligible for free delivery by settings', [
    //                     'order_id' => $order->id,
    //                     'sub_total' => $adjusted_total,
    //                     'free_delivery_subtotal' => $free_delivery_subtotal,
    //                     'delivery_distance' => $delivery_distance
    //                 ]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Cannot apply manual free delivery because the order is already eligible for free delivery.'
    //                 ], 422);
    //             }
    
    //             $order->delivery_charge = 0;
    //             $order->actual_delivery_charge = (float) ($order->actual_delivery_charge ?? $base_delivery_charge);
    //             if ($delivery_distance > $base_delivery_distance) {
    //                 $extra_distance = $delivery_distance - $base_delivery_distance;
    //                 $extra_units = ceil($extra_distance / $extra_delivery_distance);
    //                 $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
    //             }
    //             $order->is_free_delivery = true;
    //             $adjusted_delivery_charge = 0;
    //             $is_free_delivery_eligible = true;
    
    //             if ($original_coupon_name && $original_coupon && in_array($original_coupon->discount_type, ['FIXED', 'PERCENTAGE']) && $is_coupon_valid) {
    //                 $order->coupon_name = $original_coupon_name;
    //                 $order->coupon_amount = $original_coupon_amount;
    //                 Log::channel('update_order')->info('Preserving original coupon during manual free delivery', [
    //                     'order_id' => $order->id,
    //                     'coupon_name' => $original_coupon_name,
    //                     'coupon_amount' => $original_coupon_amount,
    //                     'coupon_type' => $original_coupon->discount_type
    //                 ]);
    //             } else {
    //                 $order->coupon_name = 'FREESHIP';
    //                 $order->coupon_amount = $order->actual_delivery_charge;
    //             }
    
    //             Log::channel('update_order')->info('Free delivery activated manually, company bears the cost', [
    //                 'order_id' => $order->id,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge,
    //                 'coupon_name' => $order->coupon_name,
    //                 'coupon_amount' => $order->coupon_amount,
    //                 'is_guest_user' => $isGuestUser,
    //                 'adjusted_total' => $adjusted_total,
    //                 'delivery_distance' => $delivery_distance
    //             ]);
    //         } elseif ($original_coupon && $original_coupon->discount_type === 'FREE') {
    //             // Preserve FREE coupon effect
    //             $order->delivery_charge = 0;
    //             $order->actual_delivery_charge = (float) ($order->actual_delivery_charge ?? $base_delivery_charge);
    //             if ($delivery_distance > $base_delivery_distance) {
    //                 $extra_distance = $delivery_distance - $base_delivery_distance;
    //                 $extra_units = ceil($extra_distance / $extra_delivery_distance);
    //                 $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
    //             }
    //             $order->is_free_delivery = true;
    //             $adjusted_delivery_charge = 0;
    //             $is_free_delivery_eligible = true;
    //             $order->coupon_name = $original_coupon_name;
    //             $order->coupon_amount = $order->actual_delivery_charge;
    //             Log::channel('update_order')->info('Preserving FREE coupon effect, delivery charge remains zero', [
    //                 'order_id' => $order->id,
    //                 'coupon_name' => $original_coupon_name,
    //                 'coupon_amount' => $order->coupon_amount,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge
    //             ]);
    //         } elseif ($is_free_delivery_eligible_by_settings && $request->delivery_type != '2') {
    //             // Apply free delivery if eligible due to restaurant settings
    //             $order->delivery_charge = 0;
    //             $order->actual_delivery_charge = (float) ($order->actual_delivery_charge ?? $base_delivery_charge);
    //             if ($delivery_distance > $base_delivery_distance) {
    //                 $extra_distance = $delivery_distance - $base_delivery_distance;
    //                 $extra_units = ceil($extra_distance / $extra_delivery_distance);
    //                 $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
    //             }
    //             $order->is_free_delivery = true;
    //             $adjusted_delivery_charge = 0;
    //             $is_free_delivery_eligible = true;
    
    //             // Remove FREESHIP coupon if present
    //             if ($order->coupon_name === 'FREESHIP') {
    //                 $order->coupon_name = null;
    //                 $order->coupon_amount = 0;
    //                 Log::channel('update_order')->info('FREESHIP coupon removed due to automatic free delivery eligibility', [
    //                     'order_id' => $order->id,
    //                     'sub_total' => $adjusted_total,
    //                     'free_delivery_subtotal' => $free_delivery_subtotal,
    //                     'delivery_distance' => $delivery_distance
    //                 ]);
    //             }
    
    //             Log::channel('update_order')->info('Free delivery applied due to eligibility by settings', [
    //                 'order_id' => $order->id,
    //                 'sub_total' => $adjusted_total,
    //                 'free_delivery_subtotal' => $free_delivery_subtotal,
    //                 'delivery_distance' => $delivery_distance,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge
    //             ]);
    //         } elseif ($request->delivery_type == '2') {
    //             // Handle Self-Pickup case
    //             $order->delivery_charge = 0;
    //             $order->actual_delivery_charge = 0;
    //             $order->is_free_delivery = false;
    //             $order->coupon_name = null;
    //             $order->coupon_amount = 0;
    //             $coupon_delivery_discount = 0;
    //             $adjusted_delivery_charge = 0;
    //             Log::channel('update_order')->info('Self-Pickup order, resetting delivery and coupon values', [
    //                 'order_id' => $order->id,
    //                 'delivery_type' => $order->delivery_type,
    //                 'delivery_charge' => $order->delivery_charge,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge,
    //                 'coupon_name' => $order->coupon_name,
    //                 'coupon_amount' => $order->coupon_amount,
    //                 'adjusted_total' => $adjusted_total,
    //                 'delivery_distance' => $delivery_distance
    //             ]);
    //         } else {
    //             // Handle Delivery case
    //             if ($recalculate_delivery_charge) {
    //                 // Recalculate delivery charge for transition to Delivery or eligibility change
    //                 $calculated_delivery_charge = $base_delivery_charge;
    //                 if ($delivery_distance > $base_delivery_distance) {
    //                     $extra_distance = $delivery_distance - $base_delivery_distance;
    //                     $extra_units = ceil($extra_distance / $extra_delivery_distance);
    //                     $calculated_delivery_charge += $extra_units * $extra_delivery_charge;
    //                 }
    //                 $order->delivery_charge = $calculated_delivery_charge;
    //                 $order->actual_delivery_charge = $calculated_delivery_charge;
    //                 $adjusted_delivery_charge = $calculated_delivery_charge;
    //                 $order->is_free_delivery = false;
    //                 $order->coupon_name = $is_coupon_valid ? $original_coupon_name : null;
    //                 $order->coupon_amount = $is_coupon_valid ? $original_coupon_amount : 0;
    //                 $coupon_delivery_discount = 0;
    //                 Log::channel('update_order')->info('Delivery charge recalculated for transition to Delivery or change in eligibility', [
    //                     'order_id' => $order->id,
    //                     'delivery_charge' => $calculated_delivery_charge,
    //                     'actual_delivery_charge' => $order->actual_delivery_charge,
    //                     'delivery_distance' => $delivery_distance
    //                 ]);
    //             } else {
    //                 // Preserve existing delivery charge and coupon
    //                 $order->is_free_delivery = $original_is_free_delivery;
    //                 $order->coupon_name = $is_coupon_valid ? $original_coupon_name : null;
    //                 $order->coupon_amount = $is_coupon_valid ? $original_coupon_amount : 0;
    //                 $coupon_delivery_discount = $order->coupon_name == 'FREESHIP' ? $order->coupon_amount : 0;
    //                 Log::channel('update_order')->info('Preserving existing delivery charge and coupon', [
    //                     'order_id' => $order->id,
    //                     'delivery_charge' => $order->delivery_charge,
    //                     'actual_delivery_charge' => $order->actual_delivery_charge,
    //                     'is_guest_user' => $isGuestUser,
    //                     'free_delivery_subtotal' => $free_delivery_subtotal,
    //                     'adjusted_total' => $adjusted_total,
    //                     'coupon_name' => $order->coupon_name,
    //                     'delivery_distance' => $delivery_distance
    //                 ]);
    //             }
    //         }
    
    //         // Calculate commission
    //         $deliveryGuyCommissionRate = $order->accept_delivery && $order->accept_delivery->user ? (float) ($order->accept_delivery->user->delivery_guy_detail->commission_rate ?? 10) : 10;
    //         $company_commission = 0;
    //         $delivery_charge = (float) ($order->delivery_charge ?? 0); // Use delivery_charge for commission calculation
    //         if ($delivery_charge < 0) {
    //             Log::channel('update_order')->warning('Negative delivery charge in updateOrder', [
    //                 'order_id' => $order->id,
    //                 'delivery_charge' => $delivery_charge
    //             ]);
    //             $delivery_charge = 0;
    //         }
    
    //         if ($order->delivery_charge == 0 && ($is_manual_free_delivery || $is_free_delivery_eligible_by_settings || ($original_coupon && $original_coupon->discount_type === 'FREE'))) {
    //             $company_commission = -($free_delivery_cost * ($free_delivery_comm / 100));
    //             Log::channel('update_order')->info('Commission calculated for free delivery', [
    //                 'order_id' => $order->id,
    //                 'adjusted_total' => $adjusted_total,
    //                 'free_delivery_cost' => $free_delivery_cost,
    //                 'free_delivery_comm' => $free_delivery_comm,
    //                 'company_commission' => $company_commission,
    //                 'is_manual_free_delivery' => $is_manual_free_delivery,
    //                 'is_free_delivery_eligible_by_settings' => $is_free_delivery_eligible_by_settings
    //             ]);
    //         } else {
    //             if ($order->payment_mode == 'WALLET' && $order->wallet_amount >= ($adjusted_total + $delivery_charge)) {
    //                 $company_commission = 0;
    //             } else {
    //                 if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
    //                     $remaining_amount = $adjusted_total + $delivery_charge - ($order->wallet_amount ?? 0);
    //                     $company_commission = -($deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0)));
    //                 } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
    //                     $company_commission = -($deliveryGuyCommissionRate / 100 * $delivery_charge);
    //                 }
    //             }
    //             Log::channel('update_order')->info('Commission calculated for regular order', [
    //                 'order_id' => $order->id,
    //                 'payment_mode' => $order->payment_mode,
    //                 'delivery_charge' => $delivery_charge,
    //                 'adjusted_delivery_charge' => $adjusted_delivery_charge,
    //                 'commission_rate' => $deliveryGuyCommissionRate,
    //                 'company_commission' => $company_commission
    //             ]);
    //         }
    
    //         // Calculate final profit
    //         $order->final_profit = $company_commission - $coupon_delivery_discount;
    //         Log::channel('update_order')->info('Final profit calculated in updateOrder', [
    //             'order_id' => $order->id,
    //             'company_commission' => $company_commission,
    //             'coupon_delivery_discount' => $coupon_delivery_discount,
    //             'final_profit' => $order->final_profit
    //         ]);
    
    //         // Calculate commission and other amounts
    //         $order->commission_rate = (float) ($order->restaurant->commission_rate ?? 0);
    //         $order->commission_amount = $order->commission_rate * $order->sub_total / 100;
    //         $order->restaurant_net_amount = $order->sub_total + ($order->restaurant_charge ?? 0) - $order->commission_amount;
    //         if ($order->delivery_charge == 0 && ($is_manual_free_delivery || $is_free_delivery_eligible_by_settings || ($original_coupon && $original_coupon->discount_type === 'FREE'))) {
    //             $order->restaurant_net_amount -= $free_delivery_cost;
    //         }
    
    //         // Calculate taxes
    //         if (config('setting.taxApplicable') == 'true') {
    //             $order->tax = (float) (config('setting.taxPercentage') ?? 0);
    //             $taxAmount = (float) (((float) config('setting.taxPercentage') / 100) * $orderTotal);
    //         } else {
    //             $taxAmount = 0;
    //         }
    //         $order->tax_amount = $taxAmount;
    //         $order->restaurant_net_amount += $order->tax_amount;
    
    //         // Calculate final total
    //         $orderTotal += $taxAmount;
    //         $orderTotal += $order->delivery_charge ?? 0;
    //         $orderTotal += $order->restaurant_charge ?? 0;
    //         $orderTotal += $order->tip_amount ?? 0;
    
    //         // Apply coupon discount if valid
    //         if ($order->coupon_name && $is_coupon_valid && $order->coupon_name !== 'FREESHIP' && (!$original_coupon || $original_coupon->discount_type !== 'FREE')) {
    //             $orderTotal -= $order->coupon_amount;
    //         }
    //         $order->total = max(0, $orderTotal);
    
    //         // Update payment
    //         if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
    //             $newTotal = $orderTotal;
    //             $availableBalance = (float) ($user->balanceFloat ?? 0);
    //             $originalPaymentMode = $order->payment_mode;
    //             $requestedPaymentMode = $request->input('payment_mode', $originalPaymentMode);
    //             Log::channel('update_order')->info('Updating payment in updateOrder', [
    //                 'order_id' => $order->id,
    //                 'new_total' => $newTotal,
    //                 'available_balance' => $availableBalance,
    //                 'original_payment_mode' => $originalPaymentMode,
    //                 'requested_payment_mode' => $requestedPaymentMode
    //             ]);
    
    //             if ($newTotal <= 0) {
    //                 $order->wallet_amount = 0;
    //                 $order->payable = 0;
    //                 $order->payment_mode = 'WALLET';
    //                 Log::channel('update_order')->info('Total is zero or negative, setting payment to WALLET', [
    //                     'order_id' => $order->id,
    //                     'new_total' => $newTotal
    //                 ]);
    //             } elseif ($requestedPaymentMode == 'COD') {
    //                 $order->wallet_amount = 0;
    //                 $order->payable = $newTotal;
    //                 $order->payment_mode = 'COD';
    //                 $order->restaurant_net_amount += $order->payable;
    //                 Log::channel('update_order')->info('Set to COD payment, no wallet deduction', [
    //                     'order_id' => $order->id,
    //                     'new_total' => $newTotal,
    //                     'payable' => $order->payable
    //                 ]);
    //             } else {
    //                 if ($newTotal <= $availableBalance) {
    //                     $order->wallet_amount = $newTotal;
    //                     $order->payable = 0;
    //                     $order->payment_mode = 'WALLET';
    //                     $user->withdraw($newTotal * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
    //                     Log::channel('update_order')->info('Withdrawing full amount from wallet', [
    //                         'order_id' => $order->id,
    //                         'amount' => $newTotal
    //                     ]);
    //                 } else {
    //                     $order->wallet_amount = $availableBalance;
    //                     $order->payable = $newTotal - $availableBalance;
    //                     $order->payment_mode = 'PARTIAL';
    //                     $order->restaurant_net_amount += $order->payable;
    //                     if ($availableBalance > 0) {
    //                         $user->withdraw($order->wallet_amount * 100, ['description' => 'Partial payment for manage order: ' . $order->unique_order_id]);
    //                         Log::channel('update_order')->info('Withdrawing partial amount from wallet', [
    //                             'order_id' => $order->id,
    //                             'amount' => $availableBalance
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }
    
    //         $order->save();
    //         Log::channel('update_order')->info('Order saved with updated delivery type', [
    //             'order_id' => $order->id,
    //             'delivery_type' => $order->delivery_type,
    //             'delivery_charge' => $order->delivery_charge,
    //             'actual_delivery_charge' => $order->actual_delivery_charge,
    //             'is_free_delivery' => $order->is_free_delivery
    //         ]);
    //         DB::commit();
    //         $responseData = [
    //             'success' => true,
    //             'message' => 'Order updated successfully!',
    //             'data' => [
    //                 'order_id' => $order->id,
    //                 'delivery_type' => $order->delivery_type,
    //                 'delivery_type_display' => $order->delivery_type == '1' ? 'Delivery' : ($order->delivery_type == '2' ? 'Self-Pickup' : 'Pickup-Drop'),
    //                 'delivery_charge' => $order->delivery_charge,
    //                 'actual_delivery_charge' => $order->actual_delivery_charge,
    //                 'is_free_delivery' => $order->is_free_delivery,
    //                 'total' => $order->total,
    //                 'sub_total' => $order->sub_total,
    //                 'coupon_name' => $order->coupon_name,
    //                 'coupon_amount' => $order->coupon_amount,
    //                 'payment_mode' => $order->payment_mode
    //             ]
    //         ];
    //         Log::channel('update_order')->info('Returning API response', [
    //             'order_id' => $order->id,
    //             'response_data' => $responseData
    //         ]);
    //         activity()->performedOn($order)->causedBy($author)->withProperties(['type' => 'Order_Modified'])->log('Order modified');
    //         return response()->json($responseData, 200)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    //     } catch (\Illuminate\Database\QueryException $qe) {
    //         DB::rollBack();
    //         Log::channel('update_order')->error('Database error in updateOrder: Order ID ' . $id . ', Error: ' . $qe->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'A database error occurred. Please try again.'
    //         ], 500);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::channel('update_order')->error('General error in updateOrder: Order ID ' . $id . ', Error: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred: ' . $e->getMessage()
    //         ], 500);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         Log::channel('update_order')->error('Unexpected error in updateOrder: Order ID ' . $id . ', Error: ' . $th->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An unexpected error occurred: ' . $th->getMessage()
    //         ], 500);
    //     }
    // }
         public function updateOrder(Request $request, $id)
    {
        $author = Auth::user();
        $orderTotal = 0;
        $logger = Log::channel('update_order');
        $order = Order::where('id', $id)->with(['orderitems.order_item_addons', 'restaurant', 'accept_delivery.user.delivery_guy_detail'])->lockForUpdate()->first();
    
        // Log incoming request data
        Log::channel('update_order')->info('Received updateOrder request', [
            'order_id' => $id,
            'request_data' => $request->all(),
            'delivery_type' => $request->delivery_type,
            'has_delivery_type' => $request->has('delivery_type')
        ]);
    
        if (!$order) {
            Log::channel('update_order')->error('Order not found in updateOrder: Order ID ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }
    
        DB::beginTransaction();
    
        try {
            // Check if user is a guest based on email
            $isGuestUser = false;
            $user = User::where('id', $order->user_id)->first();
            if ($user && strpos($user->email, '@app.motoboxapp.com') !== false) {
                $isGuestUser = true;
                Log::channel('update_order')->info('Guest user detected', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }
    
            // Refund previous wallet payment
            if (!$user) {
                Log::channel('update_order')->error('User not found for order: Order ID ' . $id . ', User ID ' . $order->user_id);
                throw new Exception('User not found for this order.');
            }
            if ($order->wallet_amount > 0) {
                Log::channel('update_order')->info('Refunding original wallet amount before update', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'wallet_amount' => $order->wallet_amount
                ]);
                $user->deposit($order->wallet_amount * 100, ['description' => 'Refund original payment for manage order: ' . $order->unique_order_id]);
                $order->wallet_amount = 0;
            }
    
            // Store original coupon and delivery charge details
            $original_coupon_name = $order->coupon_name;
            $original_coupon_amount = $order->coupon_amount;
            $original_is_free_delivery = $order->is_free_delivery;
            $original_coupon = $original_coupon_name ? Coupon::where('code', $original_coupon_name)->first() : null;
            $is_coupon_valid = true;
            $original_actual_delivery_charge = $order->actual_delivery_charge;
    
            // Re-validate coupon before item changes
            if ($original_coupon && in_array($original_coupon->discount_type, ['FIXED', 'FREE'])) {
                if ($order->sub_total < ($original_coupon->min_subtotal ?? 0)) {
                    $is_coupon_valid = false;
                    Log::channel('update_order')->info('Coupon invalid before item changes due to insufficient subtotal', [
                        'order_id' => $order->id,
                        'sub_total' => $order->sub_total,
                        'min_subtotal' => $original_coupon->min_subtotal,
                        'coupon_type' => $original_coupon->discount_type
                    ]);
                }
            }
    
            // Update delivery type
            $originalDeliveryType = $order->delivery_type;
            Log::channel('update_order')->info('Before updating delivery type', [
                'order_id' => $order->id,
                'original_delivery_type' => $originalDeliveryType,
                'request_delivery_type' => $request->delivery_type
            ]);
            if ($request->delivery_type && $order->delivery_type != $request->delivery_type) {
                $order->delivery_type = $request->delivery_type;
                Log::channel('update_order')->info('Delivery type updated', [
                    'order_id' => $order->id,
                    'original_delivery_type' => $originalDeliveryType,
                    'new_delivery_type' => $order->delivery_type
                ]);
            } else {
                Log::channel('update_order')->info('No delivery type update needed', [
                    'order_id' => $order->id,
                    'original_delivery_type' => $originalDeliveryType,
                    'request_delivery_type' => $request->delivery_type
                ]);
            }
    
            // Update restaurant charge
            if ($request->has('restaurant_charge')) {
                $order->restaurant_charge = (float) ($request->restaurant_charge ?? 0);
            }
    
            // Delete AcceptDelivery when switching to Self-Pickup
            if ($originalDeliveryType == '1' && $request->delivery_type == '2' && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                $acceptDeliveries = AcceptDelivery::where('order_id', $order->id)->get();
                if ($acceptDeliveries->isNotEmpty()) {
                    try {
                        foreach ($acceptDeliveries as $acceptDelivery) {
                            $deliveryGuyId = $acceptDelivery->user_id;
                            $acceptDelivery->delete();
                            Log::channel('update_order')->info('AcceptDelivery deleted successfully', [
                                'order_id' => $order->id,
                                'delivery_guy_id' => $deliveryGuyId
                            ]);
                            activity()
                                ->performedOn($order)
                                ->causedBy($author)
                                ->withProperties(['type' => 'Delivery_Canceled_Due_To_Self_Pickup', 'delivery_guy_id' => $deliveryGuyId])
                                ->log('Delivery canceled because order changed to Self-Pickup');
                        }
                    } catch (\Exception $e) {
                        Log::channel('update_order')->error('Failed to delete AcceptDelivery', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                        throw new Exception('Failed to delete delivery assignment: ' . $e->getMessage());
                    }
                } else {
                    Log::channel('update_order')->info('No delivery assignment found to remove for Self-Pickup', [
                        'order_id' => $order->id
                    ]);
                }
            }
    
            // Delete items
            $item_ids = $request->item_ids;
            if (!is_null($item_ids)) {
                $item_ids = explode(',', $item_ids);
                if (count($item_ids) > 0) {
                    foreach ($item_ids as $item_id) {
                        $order_item = Orderitem::find($item_id);
                        if ($order_item) {
                            Log::channel('update_order')->info('Deleting order item', ['item_id' => $item_id]);
                            $order_item_addons = OrderItemAddon::where('orderitem_id', $item_id)->get();
                            foreach ($order_item_addons as $order_item_addon) {
                                $order_item_addon->delete();
                            }
                            $order_item->delete();
                        }
                    }
                }
            }
    
            // Update existing items
            if ($request->has('existing_items')) {
                foreach ($request->existing_items as $itemId => $itemData) {
                    $item = Orderitem::find($itemId);
                    if ($item && in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                        if (isset($itemData['quantity']) && $itemData['quantity'] <= 0) {
                            Log::channel('update_order')->error('Invalid quantity for existing item', [
                                'item_id' => $itemId,
                                'quantity' => $itemData['quantity']
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => 'Quantity for item ID ' . $itemId . ' must be greater than 0.'
                            ], 422);
                        }
                        if (isset($itemData['price']) && $itemData['price'] < 0) {
                            Log::channel('update_order')->error('Invalid price for existing item', [
                                'item_id' => $itemId,
                                'price' => $itemData['price']
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => 'Price for item ID ' . $itemId . ' must be non-negative.'
                            ], 422);
                        }
                        $item->name = $itemData['name'] ?? $item->name;
                        $item->quantity = isset($itemData['quantity']) && $itemData['quantity'] > 0 ? (int)$itemData['quantity'] : $item->quantity;
                        $item->price = isset($itemData['price']) && $itemData['price'] >= 0 ? (float)$itemData['price'] : $item->price;
                        Log::channel('update_order')->info('Updating existing item', [
                            'item_id' => $itemId,
                            'name' => $item->name,
                            'quantity' => $item->quantity,
                            'price' => $item->price
                        ]);
                        $item->save();
                    }
                }
            }
    
            // Add new items with validation
            $validNewItems = 0;
            $name = $request->name;
            $quantity = $request->quantity;
            $price = $request->price;
            if (!empty($name)) {
                foreach ($name as $key => $na_me) {
                    if (!is_null($name[$key]) && !empty(trim($name[$key])) && !is_null($quantity[$key]) && !is_null($price[$key]) && $quantity[$key] > 0) {
                        $item = new Orderitem();
                        $item->order_id = $id;
                        $item->item_id = 0;
                        $item->name = trim($name[$key]);
                        $item->quantity = $quantity[$key];
                        $item->price = (float) $price[$key];
                        if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                            Log::channel('update_order')->info('Adding new item', [
                                'order_id' => $id,
                                'name' => $name[$key],
                                'quantity' => $quantity[$key],
                                'price' => $price[$key]
                            ]);
                            $item->save();
    
                            // Add addons
                            $addons = $request->input('addons', [])[$key] ?? [];
                            $addonTotal = 0;
                            foreach ($addons as $addonData) {
                                $orderItemAddon = new OrderItemAddon();
                                $orderItemAddon->orderitem_id = $item->id;
                                $orderItemAddon->name = $addonData['name'];
                                $orderItemAddon->addon_price = (float) ($addonData['price'] ?? 0);
                                $orderItemAddon->save();
                                $addonTotal += $orderItemAddon->addon_price;
                                Log::channel('update_order')->info('Adding addon for new item', [
                                    'order_id' => $id,
                                    'item_id' => $item->id,
                                    'addon_name' => $addonData['name'],
                                    'addon_price' => $orderItemAddon->addon_price
                                ]);
                            }
    
                            // Validate item
                            if (empty(trim($item->name)) || $item->quantity <= 0) {
                                $item->delete();
                                Log::channel('update_order')->warning('Invalid item with empty name or zero quantity', [
                                    'order_id' => $id,
                                    'item_id' => $item->id
                                ]);
                                continue;
                            }
                            $validNewItems++;
                        }
                    }
                }
            }
    
            // Reload order items to ensure all items are included
            $order->load('orderitems.order_item_addons');
            // Check if there are valid items
            if ($order->orderitems->isEmpty() && $validNewItems == 0) {
                DB::rollBack();
                Log::channel('update_order')->error('No valid items in order after processing', [
                    'order_id' => $id,
                    'valid_new_items' => $validNewItems
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'The order must contain at least one valid item (non-empty name, quantity greater than 0, and non-negative price).'
                ], 422);
            }
    
            // Calculate subtotal
            $orderTotal = 0;
            foreach ($order->orderitems as $item) {
                $itemTotal = ($item->price + $this->calculateAddonTotal($item->order_item_addons)) * $item->quantity;
                $orderTotal += $itemTotal;
                Log::channel('update_order')->info('Calculating item total', [
                    'order_id' => $id,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_price' => $item->price,
                    'addon_total' => $this->calculateAddonTotal($item->order_item_addons),
                    'quantity' => $item->quantity,
                    'item_total' => $itemTotal
                ]);
            }
            $order->sub_total = $orderTotal;
            $adjusted_total = $order->sub_total;
            Log::channel('update_order')->info('Subtotal calculated', [
                'order_id' => $id,
                'sub_total' => $order->sub_total
            ]);
    
            // Re-validate coupon after calculating new subtotal
            if ($original_coupon && in_array($original_coupon->discount_type, ['FIXED', 'FREE'])) {
                if ($order->sub_total < ($original_coupon->min_subtotal ?? 0)) {
                    $is_coupon_valid = false;
                    Log::channel('update_order')->info('Coupon removed due to insufficient subtotal after recalculation', [
                        'order_id' => $order->id,
                        'sub_total' => $order->sub_total,
                        'min_subtotal' => $original_coupon->min_subtotal,
                        'coupon_type' => $original_coupon->discount_type
                    ]);
                } elseif ($original_coupon->discount_type === 'FREE') {
                    Log::channel('update_order')->info('Preserving FREE coupon after item changes', [
                        'order_id' => $order->id,
                        'coupon_name' => $original_coupon_name,
                        'coupon_amount' => $original_coupon_amount
                    ]);
                }
            }
    
            // Remove coupon if invalid
            if (!$is_coupon_valid && $original_coupon_name) {
                $order->coupon_name = null;
                $order->coupon_amount = 0;
                Log::channel('update_order')->info('Coupon removed due to invalid conditions', [
                    'order_id' => $order->id,
                    'coupon_name' => $original_coupon_name,
                    'sub_total' => $order->sub_total
                ]);
            }
    
            // Restaurant settings
            $min_order_price = (float) ($order->restaurant->min_order_price ?? 0);
            $free_delivery_subtotal = (float) ($order->restaurant->free_delivery_subtotal ?? 120000);
            $free_delivery_distance = (float) ($order->restaurant->free_delivery_distance ?? 4);
            $free_delivery_cost = (float) ($order->restaurant->free_delivery_cost ?? 10000);
            $free_delivery_comm = (float) ($order->restaurant->free_delivery_comm ?? 40);
            $delivery_distance = (float) ($order->distance ?? 0);
            if ($request->has('distance') && $request->distance != $order->distance) {
                $delivery_distance = (float) $request->distance;
                $order->distance = $delivery_distance;
            }
    
            // Ensure free_delivery_cost and free_delivery_comm are set
            if ($free_delivery_cost <= 0) {
                $free_delivery_cost = 10000; // Default value
                $order->restaurant->free_delivery_cost = $free_delivery_cost;
                $order->restaurant->save();
                Log::channel('update_order')->info('Set default free_delivery_cost for restaurant', [
                    'restaurant_id' => $order->restaurant->id,
                    'free_delivery_cost' => $free_delivery_cost
                ]);
            }
            if ($free_delivery_comm <= 0) {
                $free_delivery_comm = 40; // Default value
                $order->restaurant->free_delivery_comm = $free_delivery_comm;
                $order->restaurant->save();
                Log::channel('update_order')->info('Set default free_delivery_comm for restaurant', [
                    'restaurant_id' => $order->restaurant->id,
                    'free_delivery_comm' => $free_delivery_comm
                ]);
            }
    
            // Calculate delivery charges based on distance
            $base_delivery_charge = (float) ($order->restaurant->base_delivery_charge ?? 14000);
            $base_delivery_distance = (float) ($order->restaurant->base_delivery_distance ?? 4);
            $extra_delivery_charge = (float) ($order->restaurant->extra_delivery_charge ?? 4000);
            $extra_delivery_distance = (float) ($order->restaurant->extra_delivery_distance ?? 1);
    
            // Keep current delivery_charge by default
            $adjusted_delivery_charge = $order->delivery_charge;
            $recalculate_delivery_charge = false;
    
            // Check minimum order before and after update
            $was_below_min_order = $order->sub_total < $min_order_price;
            $is_below_min_order = $adjusted_total < $min_order_price;
    
            // Check if delivery charge is manually updated from frontend
            $is_manual_delivery_charge_update = $request->has('delivery_charge') && $request->filled('delivery_charge') && is_numeric($request->delivery_charge);
            $is_manual_free_delivery = $request->has('free_delivery') && $request->free_delivery == 'true' && $request->delivery_type != '2';
    
            // Bypass min_order_price check for manual delivery charge update or manual free delivery
            if ($is_manual_delivery_charge_update || $is_manual_free_delivery) {
                $is_below_min_order = false;
                Log::channel('update_order')->info('Bypassing min_order_price check due to manual delivery charge update or manual free delivery', [
                    'order_id' => $order->id,
                    'sub_total' => $adjusted_total,
                    'min_order_price' => $min_order_price,
                    'is_manual_delivery_charge_update' => $is_manual_delivery_charge_update,
                    'is_manual_free_delivery' => $is_manual_free_delivery,
                    'delivery_charge' => $request->delivery_charge
                ]);
            }
    
            // Check free delivery eligibility based on restaurant settings
            $was_free_delivery_eligible = $order->is_free_delivery;
            $is_free_delivery_eligible_by_settings = (
                $free_delivery_subtotal > 0 &&
                $delivery_distance <= $free_delivery_distance &&
                $adjusted_total >= $free_delivery_subtotal &&
                !$is_below_min_order &&
                !$is_manual_delivery_charge_update &&
                !$is_manual_free_delivery
            );
    
            // Handle free delivery and delivery charges
            $coupon_delivery_discount = 0;
            $is_free_delivery_eligible = false;
    
            // Check if delivery charge needs recalculation
            if (
                ($request->has('distance') && $request->distance != $order->distance) ||
                ($request->has('delivery_type') && $request->delivery_type == '1' && $originalDeliveryType == '2')
            ) {
                $recalculate_delivery_charge = true;
                Log::channel('update_order')->info('Triggering delivery charge recalculation', [
                    'order_id' => $order->id,
                    'reason' => 'Distance or delivery type changed',
                    'was_free_delivery_eligible' => $was_free_delivery_eligible,
                    'is_free_delivery_eligible_by_settings' => $is_free_delivery_eligible_by_settings,
                    'sub_total' => $adjusted_total,
                    'min_order_price' => $min_order_price,
                    'free_delivery_subtotal' => $free_delivery_subtotal
                ]);
            }
    
            // Handle manual delivery charge update first
            if ($is_manual_delivery_charge_update) {
                $newDeliveryCharge = (float) ($request->delivery_charge ?? 0);
                if ($newDeliveryCharge < 0) {
                    Log::channel('update_order')->error('Invalid delivery charge provided', [
                        'order_id' => $order->id,
                        'delivery_charge' => $newDeliveryCharge
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Delivery charge cannot be negative.'
                    ], 422);
                }
                $order->delivery_charge = $newDeliveryCharge;
                $order->actual_delivery_charge = $newDeliveryCharge;
                $order->is_free_delivery = false; // Force non-free delivery for manual charge
                $adjusted_delivery_charge = $newDeliveryCharge;
                if ($order->coupon_name == 'FREESHIP' || ($original_coupon && $original_coupon->discount_type === 'FREE')) {
                    $order->coupon_name = null;
                    $order->coupon_amount = 0;
                    $coupon_delivery_discount = 0;
                }
                Log::channel('update_order')->info('Delivery charge updated manually from frontend, treating order as regular (no min_order_price restriction)', [
                    'order_id' => $order->id,
                    'delivery_charge' => $newDeliveryCharge,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'is_guest_user' => $isGuestUser,
                    'adjusted_total' => $adjusted_total,
                    'delivery_distance' => $delivery_distance,
                    'min_order_price_bypassed' => true,
                    'is_free_delivery' => $order->is_free_delivery
                ]);
            } elseif ($is_manual_free_delivery) {
                // Handle manual free delivery
                if ($is_free_delivery_eligible_by_settings) {
                    DB::rollBack();
                    Log::channel('update_order')->error('Manual free delivery not allowed when order is eligible for free delivery by settings', [
                        'order_id' => $order->id,
                        'sub_total' => $adjusted_total,
                        'free_delivery_subtotal' => $free_delivery_subtotal,
                        'delivery_distance' => $delivery_distance
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot apply manual free delivery because the order is already eligible for free delivery.'
                    ], 422);
                }
    
                $order->delivery_charge = 0;
                $order->actual_delivery_charge = $original_actual_delivery_charge ?: $base_delivery_charge;
                if ($request->has('distance') && $request->distance != $order->distance && $delivery_distance > $base_delivery_distance) {
                    $extra_distance = $delivery_distance - $base_delivery_distance;
                    $extra_units = ceil($extra_distance / $extra_delivery_distance);
                    $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
                }
                $order->is_free_delivery = true;
                $adjusted_delivery_charge = 0;
                $is_free_delivery_eligible = true;
    
                if ($original_coupon_name && $original_coupon && in_array($original_coupon->discount_type, ['FIXED', 'PERCENTAGE']) && $is_coupon_valid) {
                    $order->coupon_name = $original_coupon_name;
                    $order->coupon_amount = $original_coupon_amount;
                    Log::channel('update_order')->info('Preserving original coupon during manual free delivery', [
                        'order_id' => $order->id,
                        'coupon_name' => $original_coupon_name,
                        'coupon_amount' => $original_coupon_amount,
                        'coupon_type' => $original_coupon->discount_type
                    ]);
                } else {
                    $order->coupon_name = 'FREESHIP';
                    $order->coupon_amount = $order->actual_delivery_charge;
                }
    
                Log::channel('update_order')->info('Free delivery activated manually, company bears the cost', [
                    'order_id' => $order->id,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'coupon_name' => $order->coupon_name,
                    'coupon_amount' => $order->coupon_amount,
                    'is_guest_user' => $isGuestUser,
                    'adjusted_total' => $adjusted_total,
                    'delivery_distance' => $delivery_distance
                ]);
            } elseif ($original_coupon && $original_coupon->discount_type === 'FREE' && $is_coupon_valid) {
                // Preserve FREE coupon effect if still valid
                $order->delivery_charge = 0;
                $order->actual_delivery_charge = $original_actual_delivery_charge ?: $base_delivery_charge;
                if ($request->has('distance') && $request->distance != $order->distance && $delivery_distance > $base_delivery_distance) {
                    $extra_distance = $delivery_distance - $base_delivery_distance;
                    $extra_units = ceil($extra_distance / $extra_delivery_distance);
                    $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
                }
                $order->is_free_delivery = true;
                $adjusted_delivery_charge = 0;
                $is_free_delivery_eligible = true;
                $order->coupon_name = $original_coupon_name;
                $order->coupon_amount = $order->actual_delivery_charge;
                Log::channel('update_order')->info('Preserving FREE coupon effect, delivery charge remains zero', [
                    'order_id' => $order->id,
                    'coupon_name' => $original_coupon_name,
                    'coupon_amount' => $order->coupon_amount,
                    'actual_delivery_charge' => $order->actual_delivery_charge
                ]);
            } elseif ($is_free_delivery_eligible_by_settings && $request->delivery_type != '2') {
                // Apply free delivery if eligible due to restaurant settings
                $order->delivery_charge = 0;
                $order->actual_delivery_charge = $original_actual_delivery_charge ?: $base_delivery_charge;
                if ($request->has('distance') && $request->distance != $order->distance && $delivery_distance > $base_delivery_distance) {
                    $extra_distance = $delivery_distance - $base_delivery_distance;
                    $extra_units = ceil($extra_distance / $extra_delivery_distance);
                    $order->actual_delivery_charge += $extra_units * $extra_delivery_charge;
                }
                $order->is_free_delivery = true;
                $adjusted_delivery_charge = 0;
                $is_free_delivery_eligible = true;
    
                // Remove FREESHIP coupon if present
                if ($order->coupon_name === 'FREESHIP') {
                    $order->coupon_name = null;
                    $order->coupon_amount = 0;
                    Log::channel('update_order')->info('FREESHIP coupon removed due to automatic free delivery eligibility', [
                        'order_id' => $order->id,
                        'sub_total' => $adjusted_total,
                        'free_delivery_subtotal' => $free_delivery_subtotal,
                        'delivery_distance' => $delivery_distance
                    ]);
                }
    
                Log::channel('update_order')->info('Free delivery applied due to eligibility by settings', [
                    'order_id' => $order->id,
                    'sub_total' => $adjusted_total,
                    'free_delivery_subtotal' => $free_delivery_subtotal,
                    'delivery_distance' => $delivery_distance,
                    'actual_delivery_charge' => $order->actual_delivery_charge
                ]);
            } elseif ($request->delivery_type == '2') {
                // Handle Self-Pickup case
                $order->delivery_charge = 0;
                $order->actual_delivery_charge = 0;
                $order->is_free_delivery = false;
                $order->coupon_name = null;
                $order->coupon_amount = 0;
                $coupon_delivery_discount = 0;
                $adjusted_delivery_charge = 0;
                Log::channel('update_order')->info('Self-Pickup order, resetting delivery and coupon values', [
                    'order_id' => $order->id,
                    'delivery_type' => $order->delivery_type,
                    'delivery_charge' => $order->delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'coupon_name' => $order->coupon_name,
                    'coupon_amount' => $order->coupon_amount,
                    'adjusted_total' => $adjusted_total,
                    'delivery_distance' => $delivery_distance
                ]);
            } else {
                // Handle Delivery case
                if ($recalculate_delivery_charge) {
                    // Recalculate delivery charge for transition to Delivery or eligibility change
                    $calculated_delivery_charge = $base_delivery_charge;
                    if ($delivery_distance > $base_delivery_distance) {
                        $extra_distance = $delivery_distance - $base_delivery_distance;
                        $extra_units = ceil($extra_distance / $extra_delivery_distance);
                        $calculated_delivery_charge += $extra_units * $extra_delivery_charge;
                    }
                    $order->delivery_charge = $calculated_delivery_charge;
                    $order->actual_delivery_charge = $calculated_delivery_charge;
                    $adjusted_delivery_charge = $calculated_delivery_charge;
                    $order->is_free_delivery = false;
                    $order->coupon_name = $is_coupon_valid ? $original_coupon_name : null;
                    $order->coupon_amount = $is_coupon_valid ? $original_coupon_amount : 0;
                    $coupon_delivery_discount = 0;
                    Log::channel('update_order')->info('Delivery charge recalculated for transition to Delivery or change in eligibility', [
                        'order_id' => $order->id,
                        'delivery_charge' => $calculated_delivery_charge,
                        'actual_delivery_charge' => $order->actual_delivery_charge,
                        'delivery_distance' => $delivery_distance
                    ]);
                } else {
                    // Preserve existing delivery charge and coupon
                    $order->is_free_delivery = $original_is_free_delivery;
                    $order->coupon_name = $is_coupon_valid ? $original_coupon_name : null;
                    $order->coupon_amount = $is_coupon_valid ? $original_coupon_amount : 0;
                    $coupon_delivery_discount = $order->coupon_name == 'FREESHIP' ? $order->coupon_amount : 0;
                    Log::channel('update_order')->info('Preserving existing delivery charge and coupon', [
                        'order_id' => $order->id,
                        'delivery_charge' => $order->delivery_charge,
                        'actual_delivery_charge' => $order->actual_delivery_charge,
                        'is_guest_user' => $isGuestUser,
                        'free_delivery_subtotal' => $free_delivery_subtotal,
                        'adjusted_total' => $adjusted_total,
                        'coupon_name' => $order->coupon_name,
                        'delivery_distance' => $delivery_distance
                    ]);
                }
            }
    
            // Calculate commission
            $deliveryGuyCommissionRate = $order->accept_delivery && $order->accept_delivery->user ? (float) ($order->accept_delivery->user->delivery_guy_detail->commission_rate ?? 10) : 10;
            $company_commission = 0;
            $delivery_charge = (float) ($order->delivery_charge ?? 0); // Use delivery_charge for commission calculation
            if ($delivery_charge < 0) {
                Log::channel('update_order')->warning('Negative delivery charge in updateOrder', [
                    'order_id' => $order->id,
                    'delivery_charge' => $delivery_charge
                ]);
                $delivery_charge = 0;
            }
    
            if ($order->delivery_charge == 0 && ($is_manual_free_delivery || $is_free_delivery_eligible_by_settings || ($original_coupon && $original_coupon->discount_type === 'FREE' && $is_coupon_valid))) {
                $company_commission = -($free_delivery_cost * ($free_delivery_comm / 100));
                Log::channel('update_order')->info('Commission calculated for free delivery', [
                    'order_id' => $order->id,
                    'adjusted_total' => $adjusted_total,
                    'free_delivery_cost' => $free_delivery_cost,
                    'free_delivery_comm' => $free_delivery_comm,
                    'company_commission' => $company_commission,
                    'is_manual_free_delivery' => $is_manual_free_delivery,
                    'is_free_delivery_eligible_by_settings' => $is_free_delivery_eligible_by_settings
                ]);
            } else {
                if ($order->payment_mode == 'WALLET' && $order->wallet_amount >= ($adjusted_total + $delivery_charge)) {
                    $company_commission = 0;
                } else {
                    if (config('setting.deliveryGuyCommissionFrom') == 'FULLORDER') {
                        $remaining_amount = $adjusted_total + $delivery_charge - ($order->wallet_amount ?? 0);
                        $company_commission = -($deliveryGuyCommissionRate / 100 * ($remaining_amount - ($order->tip_amount ?? 0)));
                    } elseif (config('setting.deliveryGuyCommissionFrom') == 'DELIVERYCHARGE') {
                        $company_commission = -($deliveryGuyCommissionRate / 100 * $delivery_charge);
                    }
                }
                Log::channel('update_order')->info('Commission calculated for regular order', [
                    'order_id' => $order->id,
                    'payment_mode' => $order->payment_mode,
                    'delivery_charge' => $delivery_charge,
                    'adjusted_delivery_charge' => $adjusted_delivery_charge,
                    'commission_rate' => $deliveryGuyCommissionRate,
                    'company_commission' => $company_commission
                ]);
            }
    
            // Calculate final profit
            $order->final_profit = $company_commission - $coupon_delivery_discount;
            Log::channel('update_order')->info('Final profit calculated in updateOrder', [
                'order_id' => $order->id,
                'company_commission' => $company_commission,
                'coupon_delivery_discount' => $coupon_delivery_discount,
                'final_profit' => $order->final_profit
            ]);
    
            // Calculate commission and other amounts
            $order->commission_rate = (float) ($order->restaurant->commission_rate ?? 0);
            $order->commission_amount = $order->commission_rate * $order->sub_total / 100;
            $order->restaurant_net_amount = $order->sub_total + ($order->restaurant_charge ?? 0) - $order->commission_amount;
            if ($order->delivery_charge == 0 && ($is_manual_free_delivery || $is_free_delivery_eligible_by_settings || ($original_coupon && $original_coupon->discount_type === 'FREE' && $is_coupon_valid))) {
                $order->restaurant_net_amount -= $free_delivery_cost;
            }
    
            // Calculate taxes
            if (config('setting.taxApplicable') == 'true') {
                $order->tax = (float) (config('setting.taxPercentage') ?? 0);
                $taxAmount = (float) (((float) config('setting.taxPercentage') / 100) * $orderTotal);
            } else {
                $taxAmount = 0;
            }
            $order->tax_amount = $taxAmount;
            $order->restaurant_net_amount += $order->tax_amount;
    
            // Calculate final total
            $orderTotal += $taxAmount;
            $orderTotal += $order->delivery_charge ?? 0;
            $orderTotal += $order->restaurant_charge ?? 0;
            $orderTotal += $order->tip_amount ?? 0;
    
            // Apply coupon discount if valid
            if ($order->coupon_name && $is_coupon_valid && $order->coupon_name !== 'FREESHIP' && $original_coupon && $original_coupon->discount_type !== 'FREE') {
                $orderTotal -= $order->coupon_amount;
            }
            $order->total = max(0, $orderTotal);
    
            // Update payment
            if (in_array($order->orderstatus_id, [1, 2, 3, 4, 8, 10, 11])) {
                $newTotal = $orderTotal;
                $availableBalance = (float) ($user->balanceFloat ?? 0);
                $originalPaymentMode = $order->payment_mode;
                $requestedPaymentMode = $request->input('payment_mode', $originalPaymentMode);
                Log::channel('update_order')->info('Updating payment in updateOrder', [
                    'order_id' => $order->id,
                    'new_total' => $newTotal,
                    'available_balance' => $availableBalance,
                    'original_payment_mode' => $originalPaymentMode,
                    'requested_payment_mode' => $requestedPaymentMode
                ]);
    
                if ($newTotal <= 0) {
                    $order->wallet_amount = 0;
                    $order->payable = 0;
                    $order->payment_mode = 'WALLET';
                    Log::channel('update_order')->info('Total is zero or negative, setting payment to WALLET', [
                        'order_id' => $order->id,
                        'new_total' => $newTotal
                    ]);
                } elseif ($requestedPaymentMode == 'COD') {
                    $order->wallet_amount = 0;
                    $order->payable = $newTotal;
                    $order->payment_mode = 'COD';
                    Log::channel('update_order')->info('Set to COD payment, no wallet deduction', [
                        'order_id' => $order->id,
                        'new_total' => $newTotal,
                        'payable' => $order->payable
                    ]);
                } else {
                    if ($newTotal <= $availableBalance) {
                        $order->wallet_amount = $newTotal;
                        $order->payable = 0;
                        $order->payment_mode = 'WALLET';
                        $user->withdraw($newTotal * 100, ['description' => 'Payment for manage order: ' . $order->unique_order_id]);
                        Log::channel('update_order')->info('Withdrawing full amount from wallet', [
                            'order_id' => $order->id,
                            'amount' => $newTotal
                        ]);
                    } else {
                        $order->wallet_amount = $availableBalance;
                        $order->payable = $newTotal - $availableBalance;
                        $order->payment_mode = 'PARTIAL';
                        if ($availableBalance > 0) {
                            $user->withdraw($order->wallet_amount * 100, ['description' => 'Partial payment for manage order: ' . $order->unique_order_id]);
                            Log::channel('update_order')->info('Withdrawing partial amount from wallet', [
                                'order_id' => $order->id,
                                'amount' => $availableBalance
                            ]);
                        }
                    }
                }
            }
    
            $order->save();
            Log::channel('update_order')->info('Order saved with updated delivery type', [
                'order_id' => $order->id,
                'delivery_type' => $order->delivery_type,
                'delivery_charge' => $order->delivery_charge,
                'actual_delivery_charge' => $order->actual_delivery_charge,
                'is_free_delivery' => $order->is_free_delivery,
                'sub_total' => $order->sub_total,
                'total' => $order->total,
                'payable' => $order->payable
            ]);
            DB::commit();
            $responseData = [
                'success' => true,
                'message' => 'Order updated successfully!',
                'data' => [
                    'order_id' => $order->id,
                    'delivery_type' => $order->delivery_type,
                    'delivery_type_display' => $order->delivery_type == '1' ? 'Delivery' : ($order->delivery_type == '2' ? 'Self-Pickup' : 'Pickup-Drop'),
                    'delivery_charge' => $order->delivery_charge,
                    'actual_delivery_charge' => $order->actual_delivery_charge,
                    'is_free_delivery' => $order->is_free_delivery,
                    'total' => $order->total,
                    'sub_total' => $order->sub_total,
                    'coupon_name' => $order->coupon_name,
                    'coupon_amount' => $order->coupon_amount,
                    'payment_mode' => $order->payment_mode,
                    'payable' => $order->payable
                ]
            ];
            Log::channel('update_order')->info('Returning API response', [
                'order_id' => $order->id,
                'response_data' => $responseData
            ]);
            activity()->performedOn($order)->causedBy($author)->withProperties(['type' => 'Order_Modified'])->log('Order modified');
            return response()->json($responseData, 200)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Illuminate\Database\QueryException $qe) {
            DB::rollBack();
            Log::channel('update_order')->error('Database error in updateOrder: Order ID ' . $id . ', Error: ' . $qe->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'A database error occurred. Please try again.'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('update_order')->error('General error in updateOrder: Order ID ' . $id . ', Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::channel('update_order')->error('Unexpected error in updateOrder: Order ID ' . $id . ', Error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $th->getMessage()
            ], 500);
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
    $logger = Log::channel('update_order');
    foreach ($addons as $addon) {
        $total += (float) $addon->addon_price;
        $logger->info('Calculating addon price', [
            'addon_id' => $addon->id,
            'name' => $addon->name,
            'addon_price' => $addon->addon_price
        ]);
    }
    $logger->info('Total addon price', ['total' => $total]);
    return $total;
}
    
    
}