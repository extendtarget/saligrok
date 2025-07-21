@foreach ($restaurants as $restaurant_select)
@endforeach
<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Store Name</th>
      <th>Order ID</th>
      <th>Order Type</th>
      <th>Completed in</th>
      <th>Distance</th>
      <th>Payment Mode</th>
      <th>Paid with Wallet</th>
      <th>Net Amount</th>
      <th>Commission Rate</th>
      <th>Earnings</th>
      <th>Subtotal</th>
      <th>Coupon</th>
      @if (config('setting.taxApplicable') == "true")
      <th>Tax</th>
      @endif
      <th>Restaurant Charge</th>
      <th>Delivery Charge</th>
      <th>Delivery Tip</th>
      <th>Total</th>
      <th>Final Profit</th>
    </tr>
  </thead>
  <tbody>
    @php 
    $earningNet = 0;
    $subTotalNet = 0;
    $deliveryTotalNet = 0;
    $totalEarn = 0;
    $totalNet = 0;
    $totalWallet = 0;
    $couponTotalNet = 0;
    $taxTotalNet = 0;
    $totalTip = 0;
    $totalRestaurantCharge = 0;
    $totalFinalProfit = 0;
    @endphp
    
    @foreach ($orders as $order)
      @php
      $orderDate = $order->created_at->format('d-m-Y');
      $restaurantName = $order->restaurant->name;
      
      if ($order->delivery_type == 1) {
        $orderType = 'Delivery';
      } elseif ($order->delivery_type == 2) {
        $orderType = 'Self-Pickup';
      }

      $orderCompletionTime = $order->updated_at->diffInMinutes($order->created_at);
      $orderDistance = $order->distance != NULL ? number_format($order->distance,2) : 'N/A';
      $paymentMethod = $order->payment_mode;

      if ($order->wallet_amount != NULL) {
        $walletAmount = $order->wallet_amount;
        } else {
          $walletAmount = 0;
      }

      $orderTotal = $order->total;
      $orderDeliveryCharge = $order->delivery_charge != NULL ? $order->delivery_charge : '0';
      
      $orderTipAmount = $order->tip_amount != NULL ? $order->tip_amount : '0';

      $orderCouponAmount = $order->coupon_amount != NULL ? $order->coupon_amount : '0';

      if (($order->tax_amount != NULL) && (config('setting.taxApplicable') == "true")) {
          $orderTaxAmount = $order->tax_amount;
      } else {
        $orderTaxAmount = 0;
      }
      
      $orderRestaurantCharge = $order->restaurant_charge != NULL ? $order->restaurant_charge : '0';
        
      $orderSubTotal = $order->sub_total;

      $orderStoreCouponRate = $order->store_coupon_rate;
      $orderStoreCouponAmount = $order->store_coupon_amount;

      $orderAdminCouponCost = $order->coupon_amount - $order->store_coupon_amount;

      $commissionRate = $order->commission_rate;
      $commissionAmount = $order->commission_amount;
      
      $restaurantNetAmount = $order->restaurant_net_amount;

      $roundOffAmount = $order->round_off_amount;
      $serviceChargeAmount = $order->service_charge_amount;
      $cashbackAmount = $order->restaurant_cashback_amount;
      $finalProfit = $order->final_profit;
      $driverFuelAmount = $order->driver_fuel_amount;
      $driverIncentiveAmount = $order->driver_incentive_amount;
      
      $earningNet += $restaurantNetAmount;
      $subTotalNet += $orderSubTotal;
      $deliveryTotalNet += $orderDeliveryCharge;
      $totalEarn += $commissionAmount;
      $totalNet += $orderTotal;
      $totalWallet += $walletAmount;
      $couponTotalNet += $orderCouponAmount;
      $taxTotalNet += $orderTaxAmount;
      $totalTip += $orderTipAmount;
      $totalRestaurantCharge += $orderRestaurantCharge;
      $totalFinalProfit += $finalProfit;
      @endphp

    <tr>
      <td>{{ $orderDate}}</td>
      <td>{{ $restaurantName }}</td>
      <td>{{ $order->unique_order_id }}</td>
      <td>{{ $orderType }}</td>
      <td>{{ $orderCompletionTime }} minutes</td>
      <td>{{ $orderDistance }} km</td>
      <td>{{ $paymentMethod }}</td>
      <td>{{ $walletAmount }}</td>
      <td>{{ $restaurantNetAmount }}</td>
      <td>{{ $order->commission_rate }}%</td>
      <td>{{ $commissionAmount }}</td>
      <td>{{ $orderSubTotal }}</td>
      <td>{{ $orderCouponAmount }}</td>
      @if (config('setting.taxApplicable') == "true")
      <td>{{ $orderTaxAmount }}</td>
      @endif
      <td>{{ $orderRestaurantCharge }}</td>
      <td>{{ $orderDeliveryCharge }}</td>
      <td>{{ $orderTipAmount }}</td>
      <td>{{ $orderTotal }}</td>
      <td>{{ $finalProfit }}</td>
      @endforeach
  </tbody>
  <tfoot>
    <tr>
      <th></th>
      <th>TOTAL</th>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th></th>
      <th>{{$totalWallet}}</th>
      <th>{{$earningNet}}</th>
      <th></th>
      <th>{{$totalEarn}}</th>
      <th>{{$subTotalNet}}</th>
      <th>{{$couponTotalNet}}</th>
      @if(config('setting.taxApplicable') == "true")
      <th>{{$taxTotalNet}}</th>
      @endif
      <th>{{$totalRestaurantCharge}}</th>
      <th>{{$deliveryTotalNet}}</th>
      <th>{{$totalTip}}</th>
      <th>{{$totalNet}}</th>
      <th>{{$totalFinalProfit}}</th>
    </tr>
  </tfoot>
</table>