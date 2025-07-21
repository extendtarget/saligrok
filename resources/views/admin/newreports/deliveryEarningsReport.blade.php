@extends('admin.layouts.master')
@section("title") Delivery Guy Earnings - Reports
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
        <span class="font-weight-bold mr-2">Delivery Guy Earnings Report</span>
        <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
      </h4>
      <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
    </div>
  </div>
  <div>
    <div class="header-elements">
      <form action="{{ route('admin.deliveryEarningsReport') }}" method="GET">
        <div class="form-group row mb-0">
          <div class="col-lg-3">
            <select class="form-control form-control-sm mr-3 selectDB" name="deliver_by_id">
              <option value="">Select Delivery Boy</option>
              @foreach ($delivery_details as $delivery_boy)
              <option value="{{ $delivery_boy->id }}" @if( app('request')->input('deliver_by_id') == $delivery_boy->id)
                selected @endif class="text-capitalize">{{ $delivery_boy->id }} - {{ $delivery_boy->name ?? 'N/A' }} </option>
              @endforeach
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
              <option value="25" @if (@$search_data['record_length']==25) selected @endif>25</option>
              <option value="50" @if (@$search_data['record_length']==50) selected @endif>50</option>
              <option value="100" @if (@$search_data['record_length']==100) selected @endif>100</option>
              <option value="250" @if (@$search_data['record_length']==250) selected @endif>250</option>
              <option value="500" @if (@$search_data['record_length']==500) selected @endif>500</option>
            </select>
          </div>
          <div class="col-lg-0">
            <button type="submit" class="btn btn-primary">
              <i class="icon-search4"></i>
            </button>
          </div>
          <div class="">
            <button type="button" id="printButton" class="btn btn-sm btn-secondary ml-3"><i
                class="icon-printer"></i></button>
            <a href="{{ route('admin.exportReport', 'delivery_details') }}?deliver_by_id={{@$search_data['deliver_by_id']}}&report_start_date={{@$search_data['start_date']}}&report_end_date={{@$search_data['end_date']}}"
              id="printButton" class="btn btn-sm btn-secondary ml-2"><i class="icon-file-excel"></i></a>
          </div>
        </div>
      </form>
    </div>
<!--Extend By Aya -->
    <div class="page-header-content header-elements-md-inline">
      <div class="page-title d-flex">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead class="table-dark">
                  <tr>
                    <th>Date</th>
                    <th>Customer Name</th>
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
                    <th>Wallet Amount</th>
                    <th>Coupon Amount</th>
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
                  $walletAmountNet = 0; 
                    $couponAmountNet = 0; 
                  @endphp
                @foreach ($orders as $order)
                @if($order->accept_delivery && $order->accept_delivery->user && !empty($order->accept_delivery->user->name) && !empty($order->accept_delivery->user->id))
                @php
                $orderDate = $order->created_at->format('d-m-Y H:i');
                $restaurantName = $order->restaurant ? $order->restaurant->name : 'N/A';
                $orderCompletedTime = $order->updated_at->diffInMinutes($order->created_at);
                $paymentMethod = $order->payment_mode ?? 'N/A';
                
                if ($paymentMethod == "COD" && ($order->wallet_amount ?? 0) > 0) {
                    $paymentMethod = "partial";
                } elseif (($order->coupon_amount ?? 0) > 0) {
                    $paymentMethod = "Coupon";
                } elseif (($order->wallet_amount ?? 0) > 0 && ($order->coupon_amount ?? 0) > 0) {
                    $paymentMethod = "Wallet + Coupon";
                }
                
                $wallet_amount = $order->wallet_amount ?? 0;
                $customerName = $order->user ? $order->user->name : 'N/A';
                $deliveryGuyId = $order->accept_delivery && $order->accept_delivery->user ? $order->accept_delivery->user->id : 'N/A';
                $deliveryGuyName = $order->accept_delivery && $order->accept_delivery->user ? $order->accept_delivery->user->name : 'N/A';
                
                $orderTotal = $order->total ?? 0;
                $deliveryCharge = $order->delivery_charge ?? 0;
                $tipAmount = $order->tip_amount ?? 0;
                $orderTotalLessTip = $orderTotal - $tipAmount;
                
                $deliveryChargeEarning = $order->driver_order_commission_amount ?? 0;
                $adminBalance = ($deliveryCharge - $deliveryChargeEarning);
                $tipAmountEarning = $order->driver_order_tip_amount ?? 0;
                $coupon_amount = $order->coupon_amount ?? 0;
                
                $sumEarning = $tipAmountEarning + $deliveryChargeEarning + ($order->driver_fuel_amount ?? 0) + ($order->driver_incentive_amount ?? 0);
                
                $orderTotalNet += $orderTotal;
                $deliveryChargesTotalNet += $deliveryCharge;
                $deliveryChargesEarningNet += $deliveryChargeEarning;
                $tipAmountNet += $tipAmount;
                $tipAmountEarningNet += $tipAmountEarning;
                $adminBalanceTotalNet += $adminBalance;
                $sumEarningNet += $sumEarning;
                $totalSalaryEarnings += $order->driver_salary ?? 0;
                $walletAmountNet += $wallet_amount; // تجميع إجمالي wallet_amount
                $couponAmountNet += $coupon_amount; // تجميع إجمالي coupon_amount
                @endphp
                <tr>
                    <td>{{ $orderDate }}</td>
                    <td>{{ $customerName }}</td>
                    <td><a href="{{ route('admin.viewOrder', $order->unique_order_id) }}"><span
                                style="font-size: 0.8rem; font-weight: 700;">{{ $order->unique_order_id }}</span></a></td>
                    <td class="text-truncate">{{ $restaurantName }}</td>
                    <td>
                        @if ($order->orderstatus_id == 5 && ($orderCompletedTime >= 45))
                        <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                            style="background-color: red; color: white;">
                            {{ $orderCompletedTime }} minutes
                        </span>
                        @elseif ($order->orderstatus_id == 5 && ($orderCompletedTime > 30 && $orderCompletedTime < 45))
                        <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                            style="background-color: #ff8400; color: white;">
                            {{ $orderCompletedTime }} minutes
                        </span>
                        @elseif ($order->orderstatus_id == 5 && ($orderCompletedTime <= 30))
                        <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left"
                            style="background-color: green; color: white;">
                            {{ $orderCompletedTime }} minutes
                        </span>
                        @endif
                    </td>
                    <td>{{ $paymentMethod }}</td>
                    <td>
                        <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left">
                            {{ $deliveryGuyId }} - {{ $deliveryGuyName }}
                        </span>
                    </td>
                    <td>{{ $orderTotal }}</td>
                    <td>{{ $deliveryCharge }}</td>
                    <td>{{ $deliveryChargeEarning }}</td>
                    <td>{{ $adminBalance }}</td>
                    <td>{{ $tipAmount }}</td>
                    <td>{{ $wallet_amount }}</td>
                    <td>{{ $coupon_amount }}</td>
                    <td>{{ $tipAmountEarning }}</td>
                    <td>{{ $order->driver_salary ?? 0 }}</td>
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
                        <th></th>
                        <th>{{ $orderTotalNet }}</th>
                        <th>{{ $deliveryChargesTotalNet }}</th>
                        <th>{{ $deliveryChargesEarningNet }}</th>
                        <th>{{ $adminBalanceTotalNet }}</th>
                        <th>{{ $tipAmountNet }}</th>
                        <th>{{ $walletAmountNet }}</th> 
                        <th>{{ $couponAmountNet }}</th> 
                        <th>{{ $tipAmountEarningNet }}</th>
                        <th>{{ $totalSalaryEarnings }}</th>
                        <th>{{ $sumEarningNet }}</th>
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
  </div>
</div>

<script>
  $('#printButton').on('click', function () {
    $('.table').printThis();
  });

  $('.daterange-single').daterangepicker({
    singleDatePicker: true,
    timePicker: true,
  });

  $('.selectDB').select2({
    placeholder: 'Select Delivery Guy',
    allowClear: true,
  });
</script>
@endsection