<?php

namespace App\Http\Controllers;

use App\User;
use App\Zone;
use App\Order;
use App\Setting;
use Carbon\Carbon;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class LiveOrderController extends Controller
{
//   public function viewLiveOrders()
// {
//     $todaysDate = Carbon::now();
//     $serverTime = $todaysDate->format('Y-m-d H:i:s');
//     $serverTimezone = "Asia/Damascus"; // Assuming your server timezone is set in the Laravel configuration

//     $orders = Order::select(
//         'id',
//         'total',
//         'payment_mode',
//         'orderstatus_id',
//         'unique_order_id',
//         'user_id',
//         'restaurant_id',
//         'created_at',
//         'updated_at',
//         'location',
//         'delivery_type',
//         'schedule_date',
//         'schedule_slot',
//         'prep_time'
//     )
//         ->with(['restaurant:id,name', 'accept_delivery', 'user:id,name,phone'])
//         ->whereBetween('created_at', [
//             $todaysDate->subWeek()->startOfWeek()->toDateTimeString(),
//             $todaysDate->addWeeks(2)->startOfWeek()->toDateTimeString()
//         ])
//         ->get();

//     $statusGroups = $orders->groupBy('orderstatus_id');

//     $awaitingOrders = $statusGroups->get('8', collect());
//     $newOrders = $statusGroups->get('1', collect());
//     $preparingOrders = $statusGroups->get('2', collect());
//     $deliveryAssignedOrders = $statusGroups->get('3', collect());
//     $pickedUpOrders = $statusGroups->get('4', collect());
//     $completedOrders = $statusGroups->get('5', collect());
//     $cancelledOrders = $statusGroups->get('6', collect());
//     $pickupReadyOrders = $statusGroups->get('7', collect());
//     $scheduledOrders = $statusGroups->get('10', collect());
//     $scheduleConfirmedOrders = $statusGroups->get('11', collect());

//     $completedCount = $completedOrders->count();
//     $cancelledCount = $cancelledOrders->count();

//     $onGoingOrders = $orders->whereIn('orderstatus_id', ['1', '2', '3', '4', '7', '10', '11'])->count();

//     $onlineDrivers = User::role('Delivery Guy');

//     if (session()->has('selectedZone')) {
//         $zone_id = session('selectedZone');
//         $onlineDrivers->where('zone_id', $zone_id);
//     }

//     $onlineDrivers = $onlineDrivers->whereHas('delivery_guy_detail', function ($q) {
//         $q->where('status', 1);
//     })->count();

//     $todayOrderRevenue = $completedOrders->sum('total');

//     $zones = session()->has('selectedZone') ? Zone::where('id', session('selectedZone'))->get() : Zone::all();

//     $driverOrderRate = $onlineDrivers > 0 ? $onGoingOrders / $onlineDrivers : 0;

//     return view('admin.liveOrder', [
//         'orders' => $orders,
//         'todaysDate' => $todaysDate,
//         'awaitingOrders' => $awaitingOrders,
//         'newOrders' => $newOrders,
//         'preparingOrders' => $preparingOrders,
//         'deliveryAssignedOrders' => $deliveryAssignedOrders,
//         'pickedUpOrders' => $pickedUpOrders,
//         'completedOrders' => $completedOrders,
//         'cancelledOrders' => $cancelledOrders,
//         'onGoingOrders' => $onGoingOrders,
//         'pickupReadyOrders' => $pickupReadyOrders,
//         'scheduledOrders' => $scheduledOrders,
//         'scheduleConfirmedOrders' => $scheduleConfirmedOrders,
//         'totalCompletedOrdersToday' => $completedCount,
//         'totalCancelledOrdersToday' => $cancelledCount,
//         'todayOrderRevenue' => $todayOrderRevenue,
//         'completedCount' => $completedCount,
//         'cancelledCount' => $cancelledCount,
//         'zones' => $zones,
//         'onlineDrivers' => $onlineDrivers,
//         'driverOrderRate' => $driverOrderRate,
//         'serverTime' => $serverTime,
//         'serverTimezone' => $serverTimezone,
//     ]);
// }

public function viewLiveOrders()
    {
        $todaysDate = Carbon::now()->tz('Asia/Damascus');
        $serverTime = $todaysDate->format('Y-m-d H:i:s');
        $serverTimezone = "Asia/Damascus";

        $orders = Order::select(
            'id',
            'total',
            'payment_mode',
            'orderstatus_id',
            'unique_order_id',
            'user_id',
            'restaurant_id',
            'created_at',
            'updated_at',
            'location',
            'delivery_type',
            'schedule_date',
            'schedule_slot',
            'prep_time',
            'delay_before_driver_visibility'
        )
            ->with([
                'restaurant:id,name,show_time_on_order_accept',
                'accept_delivery',
                'user:id,name,phone'
            ])
            ->whereBetween('created_at', [
                $todaysDate->subWeek()->startOfWeek()->toDateTimeString(),
                $todaysDate->addWeeks(2)->startOfWeek()->toDateTimeString()
            ])
            ->orderBy('created_at', 'ASC')
            ->get();

        $statusGroups = $orders->groupBy('orderstatus_id');

        $awaitingOrders = $statusGroups->get('8', collect());
        $newOrders = $statusGroups->get('1', collect());
        $preparingOrders = $statusGroups->get('2', collect());
        $deliveryAssignedOrders = $statusGroups->get('3', collect());
        $pickedUpOrders = $statusGroups->get('4', collect());
        $completedOrders = $statusGroups->get('5', collect());
        $cancelledOrders = $statusGroups->get('6', collect());
        $pickupReadyOrders = $statusGroups->get('7', collect());
        $scheduledOrders = $statusGroups->get('10', collect());
        $scheduleConfirmedOrders = $statusGroups->get('11', collect());

        $completedCount = $completedOrders->count();
        $cancelledCount = $cancelledOrders->count();

        $onGoingOrders = $orders->whereIn('orderstatus_id', ['1', '2', '3', '4', '7', '10', '11'])->count();

        $onlineDrivers = User::role('Delivery Guy');

        if (session()->has('selectedZone')) {
            $zone_id = session('selectedZone');
            $onlineDrivers->where('zone_id', $zone_id);
        }

        $onlineDrivers = $onlineDrivers->whereHas('delivery_guy_detail', function ($q) {
            $q->where('status', 1);
        })->count();

        $todayOrderRevenue = $completedOrders->sum('total');

        $zones = session()->has('selectedZone') ? Zone::where('id', session('selectedZone'))->get() : Zone::all();

        $driverOrderRate = $onlineDrivers > 0 ? $onGoingOrders / $onlineDrivers : 0;

        return view('admin.liveOrder', [
            'orders' => $orders,
            'todaysDate' => $todaysDate,
            'awaitingOrders' => $awaitingOrders,
            'newOrders' => $newOrders,
            'preparingOrders' => $preparingOrders,
            'deliveryAssignedOrders' => $deliveryAssignedOrders,
            'pickedUpOrders' => $pickedUpOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'onGoingOrders' => $onGoingOrders,
            'pickupReadyOrders' => $pickupReadyOrders,
            'scheduledOrders' => $scheduledOrders,
            'scheduleConfirmedOrders' => $scheduleConfirmedOrders,
            'totalCompletedOrdersToday' => $completedCount,
            'totalCancelledOrdersToday' => $cancelledCount,
            'todayOrderRevenue' => $todayOrderRevenue,
            'completedCount' => $completedCount,
            'cancelledCount' => $cancelledCount,
            'zones' => $zones,
            'onlineDrivers' => $onlineDrivers,
            'driverOrderRate' => $driverOrderRate,
            'serverTime' => $serverTime,
            'serverTimezone' => $serverTimezone,
        ]);
    }

}
