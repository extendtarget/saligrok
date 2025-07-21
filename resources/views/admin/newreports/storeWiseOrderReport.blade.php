@extends('admin.layouts.master')
@section("title") Store-Wise Orders - Reports
@endsection
@section('content')
<style>
  .select2-selection--single .select2-selection__rendered {
    padding-left: .875rem !important;
    padding-right: 5.375rem !important;
  }

  .range-selector {
    margin: 10px;
  }
</style>
<div class="page-header">
  <div class="page-header-content header-elements-md-inline">
    <div class="page-title d-flex">
      <h4><i class="icon-circle-right2 mr-2"></i>
        <span class="font-weight-bold mr-2">Store-Wise Order Report</span>
        <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
      </h4>
      <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
    </div>
  </div>
  <div>
    <div class="header-elements">
      @if($user_type != "owner")
      <form action="{{ route('admin.storeWiseOrderReport') }}" method="GET">
        @else
        <form action="{{ route('restaurant.storeWiseOrderReport') }}" method="GET">
          @endif
          <div class="form-group row mb-0">
            <div class="col-lg-3">
              <select class="form-control form-control-sm mr-3 selectRest" name="restaurant_id">
                <option value="" selected>Select Restaurant</option>
                @foreach ($restaurants as $restaurant)
                <option value="{{ $restaurant->id }}" @if( app('request')->input('restaurant_id') == $restaurant->id)
                  selected @endif class="text-capitalize">{{ $restaurant->id }} - {{ $restaurant->name }} </option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-2">
              <input type="text" class="form-control" placeholder="Payment Mode" name="payment_mode"
                value="{{@$search_data['payment_mode']}}">
            </div>
            <div class="col-lg-2">
              <select name="delivery_type" class="form-control form-control-lg selectOrderType">
                <option></option>
                <option value="1" @if($search_data['delivery_type']=="1" ) selected @endif>Delivery</option>
                <option value="2" @if($search_data['delivery_type']=="2" ) selected @endif>Self-Pickup</option>
                <option value="4" @if($search_data['delivery_type']=="4" ) selected @endif>Pickup-Drop</option>
              </select>
            </div>
            <div class="col-lg-3" style="display:inline-flex;">
              <label class="m-auto" style="margin-right: 1vh !important">From </label>
              <input type="text" class="form-control-sm form-control daterange-single mr-3" name="report_start_date"
                value="{{@$search_data['start_date']}}" placeholder="Start Date" />
              <label class="m-auto" style="margin-right: 1vh !important">To </label>
              <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date"
                value="{{@$search_data['end_date']}}" placeholder="End Date" />
            </div>
            <div class="col-lg-0 mr-2">
              <select class="form-control form-control-sm mr-3" name="record_length">
                <option value="25" @if (@$search_data['record_length'] == 25) selected @endif>25</option>
                <option value="50" @if (@$search_data['record_length'] == 50) selected @endif>50</option>
                <option value="100" @if (@$search_data['record_length'] == 100) selected @endif>100</option>
                <option value="250" @if (@$search_data['record_length'] == 250) selected @endif>250</option>
                <option value="500" @if (@$search_data['record_length'] == 500) selected @endif>500</option>
              </select>
            </div>
            <div class="col-lg-0">
              <button type="submit" class="btn btn-primary">
                <i class="icon-search4"></i>
              </button>
            </div>
            <div class="">
              <button type="button" id="printButton" class="btn btn-sm btn-secondary ml-3"><i class="icon-printer"></i></button>
              @if($user_type == "admin")
              <a href="{{ route('admin.exportReport', 'store_wise') }}?restaurant_id={{@$search_data['restaurant_id']}}&payment_mode={{@$search_data['payment_mode']}}&report_start_date={{@$search_data['start_date']}}&report_end_date={{@$search_data['end_date']}}"
                id="printButton" class="btn btn-sm btn-secondary ml-2"><i class="icon-file-excel"></i></a>
              @else
              <a href="{{ route('restaurant.exportReport', 'store_wise') }}?restaurant_id={{@$search_data['restaurant_id']}}&payment_mode={{@$search_data['payment_mode']}}&report_start_date={{@$search_data['start_date']}}&report_end_date={{@$search_data['end_date']}}"
                id="printButton" class="btn btn-sm btn-secondary ml-2"><i class="icon-file-excel"></i></a>
              @endif
            </div>
          </div>
        </form>
    </div>
    <div class="card mt-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead class="table-dark">
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
                <th>Round Off</th>
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
              $orderDistance = $order->distance != NULL ? number_format($order->distance, 2) : 'N/A';
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
                <td class="truncate-text text-truncate">{{ $restaurantName }}</td>
                <td><a href="{{ route('admin.viewOrder', $order->unique_order_id) }}"><span
                      style="font-size: 0.8rem; font-weight: 700;">{{ $order->unique_order_id }}</span></a></td>
                <td><span class="badge badge-flat border-grey-800 text-default text-capitalize">
                    {{ $orderType }}
                </td>
                <td>
                  @if ($order->orderstatus_id == 5 && ($order->updated_at->diffInMinutes($order->created_at) > 45 ||
                  $order->updated_at->diffInMinutes($order->created_at) == 45))
                  <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                    style="background-color: red; color: white;">
                    {{ $orderCompletionTime }} minutes
                  </span>
                  @endif
                  @if ($order->orderstatus_id == 5 && ($order->updated_at->diffInMinutes($order->created_at) > 30 &&
                  $order->updated_at->diffInMinutes($order->created_at) < 45)) <span
                    class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                    style="background-color: #ff8400; color: white;">
                    {{ $orderCompletionTime }} minutes
                    </span>
                    @endif
                    @if (($order->orderstatus_id == 5 && ($order->updated_at->diffInMinutes($order->created_at) < 30) ||
                      $order->updated_at->diffInMinutes($order->created_at) == 30))
                      <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                        style="background-color: green; color: white;">
                        {{ $orderCompletionTime }} minutes
                      </span>
                      @endif
                </td>
                <td>{{ $orderDistance }} km</td>
                <td>
                  <span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $paymentMethod
                    }}</span>
                </td>
                <td>{{ config('setting.currencyFormat') }}{{ $walletAmount }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $restaurantNetAmount }}</td>
                <td>{{ $order->commission_rate }}%</td>
                <td>{{ config('setting.currencyFormat') }}{{ $commissionAmount }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderSubTotal }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderCouponAmount }}</td>
                <td>{{ $orderStoreCouponRate }}%</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderStoreCouponAmount }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderAdminCouponCost }}</td>
                @if (config('setting.taxApplicable') == "true")
                <td>{{ config('setting.currencyFormat') }}{{ $orderTaxAmount }}</td>
                @endif
                <td>{{ config('setting.currencyFormat') }}{{ $orderRestaurantCharge }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderDeliveryCharge }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderTipAmount }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $orderTotal }}</td>
                <td>{{ config('setting.currencyFormat') }}{{ $finalProfit }}</td>
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
                <th>{{ config('setting.currencyFormat') }}{{$totalWallet}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$earningNet}}</th>
                <th></th>
                <th>{{ config('setting.currencyFormat') }}{{$totalEarn}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$subTotalNet}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$couponTotalNet}}</th>
                <th></th>
                @if(config('setting.taxApplicable') == "true")
                <th>{{ config('setting.currencyFormat') }}{{$taxTotalNet}}</th>
                @endif
                <th>{{ config('setting.currencyFormat') }}{{$totalRestaurantCharge}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$deliveryTotalNet}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$totalTip}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$totalNet}}</th>
                <th>{{ config('setting.currencyFormat') }}{{$totalFinalProfit}}</th>
              </tr>
            </tfoot>
          </table>
          <div class="mt-3">
            {{ $orders->appends($_GET)->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $('.selectRest').select2({
    placeholder: 'Select Store',
    allowClear: true,
  });
  $('.selectOrderType').select2({
    placeholder: 'Select Order Type',
    allowClear: true,
  });

  $('.selectRange').select2();

  $('.daterange-single').daterangepicker({
    singleDatePicker: true,
  });

  $('#printButton').on('click', function () {
    $('.table').printThis();
  });
</script>
@endsection