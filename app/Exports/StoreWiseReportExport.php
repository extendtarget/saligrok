<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Order;
use App\Setting;
use App\Restaurant;
use App\User;
use Carbon\Carbon;

class StoreWiseReportExport implements FromView
{
	public function view(): View
	{	
	    $userType = '';
		$search_data['start_date'] = @$_GET['report_start_date'];
		$search_data['end_date'] = @$_GET['report_end_date'];
		$search_data['payment_mode'] = @$_GET['payment_mode'];   
		$search_data['restaurant_id'] = @$_GET['restaurant_id'];   

		$settings = Setting::get();

		if(\Route::current()->getName() === "restaurant.exportReport"){
	        $userType = 'owner';
	        $user = \Auth::user();
	        $restaurants = $user->restaurants;
	        $ownerRestaurant = $restaurants->pluck('id');	                         	        
	        $orders = Order::whereIn('restaurant_id',$ownerRestaurant)->where('orderstatus_id','5')->latest()->get();                   
        	$restaurants = Restaurant::whereIn('id',$ownerRestaurant)->where('id')->get();
		}else{
		    $userType = 'admin';
			$orders = Order::where('orderstatus_id','5')->latest()->get();          			
			$restaurants = Restaurant::where('is_accepted', '1')->get();
		}

        /////////////// For Restaurants Based Search/////////////////

		if (!empty($search_data['restaurant_id'])) {
			$orders = $orders->where('restaurant_id', $search_data['restaurant_id']);                      
		}

		if(!empty($search_data['start_date']) && !empty($search_data['end_date'])){
			$fromDate = Carbon::parse($search_data['start_date']);            
			$toDate = Carbon::parse($search_data['end_date']);
			$toDate->addDays(1);      
			$orders = $orders->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());
		}

		if (!empty($search_data['payment_mode'])) {        
			$orders = $orders->where('payment_mode',$search_data['payment_mode']);
		}


		if (!empty($search_data['delivery_type'])) {        
        	$orders = $orders->where('delivery_type',$search_data['delivery_type']);
    	}

		return view('admin.newreports.export.storeWiseReport', compact('orders','restaurants'));
	}
}