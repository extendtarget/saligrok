<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Auth;
use App\User;
use App\Order;
use App\Setting;
use App\Restaurant;
use Carbon\Carbon;

class storePerformanceExport implements FromView
{
	public function view(): View 
    {
        // eleqoent query
        $userType = '';
        $search_data['start_date'] = @$request->report_start_date;
        $search_data['end_date'] = @$request->report_end_date;

            $userType = 'admin';

            $restaurantData = Restaurant::where('is_accepted','1')->with('orders');        

            $displayRestaurants = $restaurantData->count();

            //$restaurants = Restaurant::where('is_accepted','1')->with('orders')->paginate(50);                        

            $orderData = Order::where('orderstatus_id', 5);

        if(!empty($request->report_start_date) && !empty($request->report_end_date)){
            $fromDate = Carbon::parse($request->report_start_date);            
            $toDate = Carbon::parse($request->report_end_date);
            $toDate->addDays(1);           
            $orderData = $orderData->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());            

            $restaurantData = Restaurant::where('is_accepted','1')->with(['orders' => function($query) use ($fromDate,$toDate){
                $query->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());   
            }]);
        }

            $restaurants = $restaurantData->get();

            $totalEarn = $orderData->sum('total');
            $deliveryCharges = $orderData->sum('delivery_charge');
			$tipAmount = $orderData->sum('tip_amount');
			$displayEarnings = ($totalEarn - ( $deliveryCharges + $tipAmount ));            
            
            $displaySales = $orderData->count();
        
            foreach ($restaurants as $restaurant) {
                $completedCount = 0;
                $cancelledCount = 0;
                $totalAmountData = 0;
                $totalEarningData = 0;
                $deliveryTime = 0;

                foreach ($restaurant->orders as $key => $order) {
                    if($order->orderstatus_id == "5")
                    {
                        $completedCount++;
                        //$totalAmountData += $order->total;                        
                        $totalAmountData += ($order->total - ($order->delivery_charge + $order->tip_amount));
                        //$totalEarningData += $order->total - ($order->total * $restaurant->commission_rate/100);
                        $totalEarningData += (($order->total - ($order->delivery_charge + $order->tip_amount)) - (($order->total - ($order->delivery_charge + $order->tip_amount))*$restaurant->commission_rate/100));
                        $deliveryTime += ($order->updated_at->diffInMinutes($order->created_at));
                    }
                    if($order->orderstatus_id == "6")
                    {
                        $cancelledCount++;
                    }
                }
            
                $totalCount = count($restaurant->orders);
                $restaurant->completedCount = $completedCount;
                $restaurant->cancelledCount = $cancelledCount;
                $restaurant->totalCount = $totalCount;
                $restaurant->totalAmountData = $totalAmountData;        
                $restaurant->totalEarningData = $totalEarningData;
                $restaurant->adminEarning = ($totalAmountData - $totalEarningData);

                if($completedCount !== 0){
                    $restaurant->deliveryTime = number_format(($deliveryTime / $completedCount),2);    
                }else{
                    $restaurant->deliveryTime = ($deliveryTime);
                }            
            }
        return view('admin.newreports.export.storePerformanceExport', compact('restaurants'));
    }
}