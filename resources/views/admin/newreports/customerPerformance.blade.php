@extends('admin.layouts.master')
@section("title") Customer Performance - Reports @endsection
@section('content')
<style>
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
    <span class="font-weight-bold mr-2">Customer Performance Report</span>
    <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
    </h4>
    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
  </div>
  </div>
  <div class="content mb-5">
  <div class="row mt-3">
    <div class="col-6 col-xl-3 mt-2">
    <div class="col-xl-12 dashboard-display p-3">
      <a class="block block-link-shadow text-left" href="javascript:void(0)">
      <div class="block-content block-content-full clearfix">
        <div class="float-right mt-10 d-none d-sm-block">
        <i class="dashboard-display-icon icon-city"></i>
        </div>
        <div class="dashboard-display-number">{{ $displayUsers }}</div>
        <div class="font-size-sm text-uppercase text-muted">Total Customers</div>
      </div>
      </a>
    </div>
    </div>
    <div class="col-6 col-xl-3 mt-2">
    <div class="col-xl-12 dashboard-display p-3">
      <a class="block block-link-shadow text-left" href="javascript:void(0)">
      <div class="block-content block-content-full clearfix">
        <div class="float-right mt-10 d-none d-sm-block">
        <i class="dashboard-display-icon icon-basket"></i>
        </div>
        <div class="dashboard-display-number">{{ $displaySales }}</div>
        <div class="font-size-sm text-uppercase text-muted">Total Orders</div>
      </div>
      </a>
    </div>
    </div>
    <div class="col-6 col-xl-3 mt-2">
    <div class="col-xl-12 dashboard-display p-3">
      <a class="block block-link-shadow text-left" href="javascript:void(0)">
      <div class="block-content block-content-full clearfix">
        <div class="float-right mt-10 d-none d-sm-block">
        <i class="dashboard-display-icon icon-coin-dollar"></i>
        </div>
        <div class="dashboard-display-number">{{ config('setting.currencyFormat') }} {{ $displayEarnings }}</div>
        <div class="font-size-sm text-uppercase text-muted">Total Sales<br><span style="font-size: 0.5rem; font-weight: 500;">(Less Delivery Charges & Tip)</span></div>
      </div>
      </a>
    </div>
    </div>
  </div>
  <!-- <form action="{{ route('admin.customerPerformance') }}" method="GET">
    <div class="col-12 mt-4">  
        <div class="col-12 " style="display:inline-flex;">        
            <label style="margin-top:5px">From&nbsp;</label>
            <input type="text" class="form-control-sm form-control daterange-single" name="report_start_date" value="{{@$search_data['start_date']}}" placeholder="Start Date" style="width: 15%" />&nbsp;&nbsp;&nbsp;
            <label style="margin-top:5px">To&nbsp;</label>
            <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date" value="{{@$search_data['end_date']}}" placeholder="End Date" style="width: 15%"/>&nbsp;&nbsp;&nbsp;
          <button type="submit" class="btn btn-primary">
            <i class="icon-search4"></i>
          </button>  
        </div>
    </div>
  </form> -->
  <div class="page-header-content header-elements-md-inline">
   <div class="page-title d-flex">
    <div class="card">
    <div class="mt-0">
      <a href="javascript:void(0)" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-printer mr-1"></i> Print Report</a>                    
      <a href="{{ route('admin.exportReport', 'customer_performance') }}" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-file-excel mr-1"></i>Export to XLS</a>
    </div>
    <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
         <th>ID</th>
         <th>Customer Name</th>
         <th>Joined on</th>
         <th>Completed Orders</th>
         <th>Cancelled Orders</th>
         <th>Total Orders</th>
         <th>Total Tip</th>
         <th>Total Amount</th>
         <th>Average Completion</th>
         <th>Most Ordered Item</th>
         <th>Most Ordered Store</th>
         <th>Suggested Action</th>
       </thead>
       <tbody>
         @php
          $grandTotalDeliveredOrders = 0;   
          $grandTotalCancelledOrders = 0;   
          
          $grandTotalTipAmount = 0;   
          $grandTotalOrdersAmount = 0;   
          $grandTotalDeliveryTime = 0;

          $uniqueUserOrderDeliveries = 0;
         @endphp
        @foreach($users as $user)
          @php
            $deliveredOrders = $user->orders->where('orderstatus_id', 5)->count();
            $cancelledOrders = $user->orders->where('orderstatus_id', 6)->count();
            $totalOrders = $deliveredOrders + $cancelledOrders;

            $orders = $user->orders;

            $tipAmount = 0;
            $orderTotal = 0;
            $deliveryTime = 0;
            
            $orderItems = [];

            foreach($orders as $order) {
              $tipAmount += $order->tip_amount;
              if($order->orderstatus_id === 5) {
                $orderTotal += ($order->total - ($order->delivery_charges + $order->tip_amount));
                $deliveryTime += $order->updated_at->diffInMinutes($order->created_at);
              }
            }

            $averageDeliveryTime = $deliveryTime && $deliveredOrders ? ($deliveryTime / $deliveredOrders) : 0;
            $averageDeliveryTime = number_format((float)$averageDeliveryTime, 2, '.', '');

            $grandTotalDeliveredOrders += $deliveredOrders;
            $grandTotalCancelledOrders += $cancelledOrders;
            $grandTotalTipAmount += $tipAmount;

            $grandTotalOrdersAmount += $orderTotal;
            $grandTotalDeliveryTime += $averageDeliveryTime;

            if($deliveredOrders) {
              $uniqueUserOrderDeliveries += 1;
            }
          @endphp
        <tr>
          <td>{{ $user->id }}</td>
          <td>{{ $user->name }}</td>
          <td>{{ $user->created_at->format('d-m-Y') }}</td>
          <td>{{ $deliveredOrders }}</td>
          <td>{{ $cancelledOrders }}</td>
          <td>{{ $totalOrders }}</td>
          <td>{{ config('setting.currencyFormat') }}{{ $tipAmount }}</td>
          <td>{{ config('setting.currencyFormat') }}{{ $orderTotal }}</td>
          <td>                          
          @if ($averageDeliveryTime > 45 || $averageDeliveryTime == 45)
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background-color: red; color: white;">
              {{ $averageDeliveryTime ? $averageDeliveryTime . ' minutes' : 'N/A' }}
            </span>
          @elseif ($averageDeliveryTime > 30 && $averageDeliveryTime < 45)
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background-color: #ff8400; color: white;">
              {{ $averageDeliveryTime ? $averageDeliveryTime . ' minutes' : 'N/A' }}
            </span>
          @elseif ($averageDeliveryTime < 30 || $averageDeliveryTime == 30)
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background-color: green; color: white;">
              {{ $averageDeliveryTime ? $averageDeliveryTime . ' minutes' : 'N/A' }}
            </span>
          @endif
          </td>
          <td>{{ !empty($user->favItemText) ? $user->favItemText : 'N/A' }}</td>
          <td>{{ !empty($user->favRestaurantText) ? $user->favRestaurantText : 'N/A' }}</td>
          <td>
            @if (($cancelledOrders > $deliveredOrders) && (($cancelledOrders - $deliveredOrders) > 15 ))
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background: red; color: white; border-radius: 0px !important;">
            <center><a href="{{ route('admin.banUser', $user->id) }}" style="color: white; font-size:14px;"><i class="icon-blocked"></i> BAN</a></center><br><br><a href="tel:{{$user->email}}" style="color: white;"><i class="icon-envelope"></i> {{ $user->email }}<br><br><i class="icon-phone"></i><a href="tel:{{$user->phone}}" style="color: white;"> {{$user->phone}}</a>
            </span>
            @elseif (($cancelledOrders > $deliveredOrders) && (($cancelledOrders - $deliveredOrders) > 7 ))
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background: orange; color: white; border-radius: 0px !important;">
              <i class="icon-warning"></i> PAY ATTENTION
            </span>
            @elseif (($deliveredOrders > $cancelledOrders) && (($deliveredOrders - $cancelledOrders) > 12 ))
            <span class="badge badge-flat border-grey-800 text-default text-left" style="background: green; color: white; border-radius: 0px !important;">
              <i class="icon-price-tag"></i> GIVE OFFER
            </span>
            @else
            <span class="badge badge-flat border-grey-800 text-default text-left" style="border-radius: 0px !important;">
              No Action Suggested
            </span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th>TOTAL</th>
          <th></th>
          <th></th>
          <th>{{ $grandTotalDeliveredOrders }}</th>
          <th>{{ $grandTotalCancelledOrders }}</th>
          <th>{{ $grandTotalDeliveredOrders + $grandTotalCancelledOrders }}</th>
          <th>{{ config('setting.currencyFormat') }}{{ $grandTotalTipAmount }}</th>
          <th>{{ config('setting.currencyFormat') }}{{ $grandTotalOrdersAmount }}</th>
          <th>{{ $grandTotalDeliveryTime && $uniqueUserOrderDeliveries ? number_format((float) ($grandTotalDeliveryTime / $uniqueUserOrderDeliveries), 2) : 0 }} minutes</th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      </tfoot>
      </table>
      <div class="mt-3">
        {{ $users->appends($_GET)->links() }}
      </div>
      </div>
    </div>
  </div>
  </div>
</div>
</div>
</div>
<script>
  $('#printButton').on('click',function(){
    $('.table').printThis();
  });
  $('.selectRange').select2();

  $('.daterange-single').daterangepicker({ 
    singleDatePicker: true,
  });
  
</script>
@endsection