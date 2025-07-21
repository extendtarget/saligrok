<?php

namespace App\Exports;

use App\Order;
use App\Coupon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CouponOrdersExport implements FromView
{
    public $coupon_id;

    public function __construct($coupon_id)
    {
        $this->coupon_id = $coupon_id;
    }
    public function view(): View
    {
        $coupon = Coupon::where('id', $this->coupon_id)->first();

        if ($coupon) {
            $orders = Order::where('coupon_name', $coupon->code)
                ->with('restaurant.zone', 'user')
                ->get();
            return view('admin.couponOrdersExport', compact('orders', 'coupon'));
        } else {
            return redirect()->back()->with(['success' => false, 'message' => "Coupon not found"]);
        }
    }
}
