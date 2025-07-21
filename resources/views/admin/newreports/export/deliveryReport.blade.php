<table class="table table-striped">
	<thead class="table-dark">
	  <tr>
		<th>Date</th>
		<th>Order ID</th>
		<th>Store Name</th>
		<th>Completed in</th>
		<th>Payment Method</th>
		<th>Delivery By</th>
		<th>Order Total</th>
		<th>Delivery Charges</th>
		<th>Delivery Guy Earnings</th>
		<th>Admin Balance Earnings</th>
		<th>Tip Amount</th>
		<th>Tip Earnings</th>
		<th>Salary Earnings</th>
		<th>Sum Earnings</th>
	  </tr>
	</thead>
	<tbody>
	  @php
	  $orderTotalNet = 0;
	  $deliveryChargesTotalNet = 0;
	  $deliveryChargesEarningNet = 0;
	  $tipAmountNet = 0;
	  $tipAmountEarningNet = 0;
	  $adminBalanceTotalNet = 0;
	  $sumEarningNet = 0;
	  $totalSalaryEarnings = 0;
	  @endphp

	  @foreach ($orders as $order)
	  @if($order->accept_delivery && $order->accept_delivery->user && $order->accept_delivery->user->name)
	  @php
	  $orderDate = $order->created_at->format('d-m-Y');
	  $restaurantName = $order->restaurant->name;

	  $orderCompletedTime = $order->updated_at->diffInMinutes($order->created_at);

	  $paymentMethod = $order->payment_mode;

	  $deliveryGuyId = $order->accept_delivery->user->id;
	  $deliveryGuyName = $order->accept_delivery->user->name;

	  $orderTotal = $order->total;
	  $deliveryCharge = $order->delivery_charge;
	  $tipAmount = ( $order->tip_amount != NULL ? $order->tip_amount : '0');
	  $orderTotalLessTip = $orderTotal - $tipAmount;

	  if ($order->driver_order_commission_amount != NULL){
	  $deliveryChargeEarning = $order->driver_order_commission_amount;
	  $adminBalance = ($deliveryCharge - $deliveryChargeEarning);
	  } else {
	  $deliveryChargeEarning = 0;
	  $adminBalance = 0;
	  }

	  if ($order->driver_order_tip_amount != NULL){
	  $tipAmountEarning = $order->driver_order_tip_amount;
	  } else {
	  $tipAmountEarning = 0;
	  }

	  $sumEarning = $tipAmountEarning + $deliveryChargeEarning + $order->driver_fuel_amount +
	  $order->driver_incentive_amount;

	  $orderTotalNet += $orderTotal;
	  $deliveryChargesTotalNet += $deliveryCharge;
	  $deliveryChargesEarningNet += $deliveryChargeEarning;
	  $tipAmountNet += $tipAmount;
	  $tipAmountEarningNet += $tipAmountEarning;
	  $adminBalanceTotalNet += $adminBalance;
	  $sumEarningNet += $sumEarning;
	  $totalIncentiveEarning += $order->driver_incentive_amount;
	  $totalFuelEarning += $order->driver_fuel_amount;

	  @endphp
	  <tr>
		<td>{{ $orderDate }}</td>
		<td>{{ $order->unique_order_id }}</td>
		<td>{{ $restaurantName }}</td>
		<td>{{ $orderCompletedTime }} minutes</td>
		<td>{{ $paymentMethod }}</td>
		<td>{{ $deliveryGuyId }} - {{ $deliveryGuyName }}</td>
		<td>{{ $orderTotal }}</td>
		<td>{{ $deliveryCharge }}</td>
		<td>{{ $deliveryChargeEarning }}</td>
		<td>{{ $adminBalance }}</td>
		<td>{{ $tipAmount }}</td>
		<td>{{ $tipAmountEarning }}</td>
		<td>{{ $order->driver_salary }}</td>
		<td>{{ $sumEarning }}</td>
	  </tr>
	  @endif
	  @endforeach
	</tbody>
	<tfoot>
	  <tr>
		<th>TOTAL</th>
		<th></th>
		<th></th>
		<th></th>
		<th></th>
		<th></th>
		<th>{{ $orderTotalNet }}</th>
		<th>{{ $deliveryChargesTotalNet }} </th>
		<th>{{ $deliveryChargesEarningNet }} </th>
		<th>{{ $adminBalanceTotalNet }}</th>
		<th>{{ $tipAmountNet }} </th>
		<th>{{ $tipAmountEarningNet }} </th>
		<td>{{ $totalSalaryEarnings }}</td>
		<th>{{ $sumEarningNet }} </th>
	  </tr>
	</tfoot>
  </table>