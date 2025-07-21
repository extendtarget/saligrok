<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\User;
use App\Order;
use App\Setting;
use Carbon\Carbon;

class DeliverReportExport implements FromView
{
	public function view(): View
	{
		$search_data['start_date'] = @$_GET['report_start_date'];
		$search_data['end_date'] = @$_GET['report_end_date'];
		$search_data['deliver_by_id'] = @$_GET['deliver_by_id'];

		/////////////// For delivery boy Based Search and date wise search/////////////////

		if (!empty($search_data['deliver_by_id'])) {
			$orders = Order::where('orderstatus_id', '5')->whereHas('accept_delivery', function ($query) use ($search_data) {
				$query->where('user_id', $search_data['deliver_by_id']);
			})->latest()->get();
		} else {
			$orders = Order::where('orderstatus_id', '5')->latest()->get();
		}

		if (!empty($search_data['start_date']) && !empty($search_data['end_date'])) {
			$fromDate = Carbon::parse($search_data['start_date']);
			$toDate = Carbon::parse($search_data['end_date']);
			$toDate->addDays(1);
			$orders = $orders->where('created_at', '>=', $fromDate->toDateString())->where('created_at', '<=', $toDate->toDateString());
		}

		return view('admin.newreports.export.deliveryReport', compact('orders'));
	}
}
