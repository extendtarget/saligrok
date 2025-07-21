<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Order;
use App\Orderitem;
use App\Item;
use App\Restaurant;
use App\User;
use Carbon\Carbon;

class customerPerformanceExport implements FromView
{
	public function view(): View
	{
        $users = User::with('orders')->where('is_active', '1')->paginate(2500);

        foreach($users as $user) {
            $orderIds = [];
            foreach($user->orders as $order) {
                $orderIds[] = $order->id;
            }

            $itemIds = [];
            $itemQuantities = [];

            $orderItems = Orderitem::whereIn('order_id', $orderIds)->get();
            foreach($orderItems as $orderItem) {
                $itemIds[] = $orderItem->item_id;

                if(isset($itemQuantities[$orderItem->item_id])) {
                    $itemQuantities[$orderItem->item_id] = $itemQuantities[$orderItem->item_id] + $orderItem->quantity;
                } else {
                    $itemQuantities[$orderItem->item_id] = $orderItem->quantity;
                }
            }

            $itemCountMap = [];
            for($i = 0; $i < count($itemIds); $i += 1) { 
                $itemId = $itemIds[$i];
                if(isset($itemCountMap[$itemId])) {
                    $itemCountMap[$itemId] = $itemCountMap[$itemId] + 1;
                } else {
                    $itemCountMap[$itemId] = 1;
                }
            }

            if(count($itemCountMap)) {
                arsort($itemCountMap);
                $favItemId = array_keys($itemCountMap)[0];
                $favItem = Item::where('id', $favItemId)->select(['id', 'name', 'restaurant_id'])->first();

                if($favItem) {
                    $user->favItem = $favItem;
                    $user->favItemText = $favItem->id . ' - ' . $favItem->name . ' x' . $user->favItemQuantity;
                    $user->favItemQuantity = $itemQuantities[$favItem->id];

                    $favRestaurant = Restaurant::where('id', $favItem->restaurant_id)->select(['id', 'name'])->first();
                    if($favRestaurant) {
                        $user->favRestaurant = $favRestaurant;
                        $user->favRestaurantText = $favRestaurant->id . ' - ' . $favRestaurant->name;
                    }
                } else {
                    $user->favItemText = '';
                    $user->favRestaurantText = '';
                }
            }
        }
        
        $orderData = Order::where('orderstatus_id', '5');
        $search_data['start_date'] = @$request->report_start_date;
        $search_data['end_date'] = @$request->report_end_date;

        if(!empty($request->report_start_date) && !empty($request->report_end_date)){
            $fromDate = Carbon::parse($request->report_start_date);            
            $toDate = Carbon::parse($request->report_end_date);
            $toDate->addDays(1);           
            $orderData = $orderData->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());            

            $Users = $users->with(['orders' => function($query) use ($fromDate,$toDate){
                $query->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());
            }]);
        }
        
        $displayUsers = $users->count();
		$deliveryCharges = $orderData->sum('delivery_charge');
		$tipAmount = $orderData->sum('tip_amount');
		$totalEarn = $orderData->sum('total');
		$displayEarnings = ($totalEarn - ( $deliveryCharges + $tipAmount ));
		$displaySales = $orderData->count();

        return view('admin.newreports.export.customerPerformance', compact('users'));
    }
}

        