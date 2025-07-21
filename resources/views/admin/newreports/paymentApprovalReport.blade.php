@extends('admin.layouts.master')
@section("title") Approved Payments - Reports
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
      <span class="font-weight-bold mr-2">Approved Payments Report</span>
      <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
    </h4>
    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
  </div>
</div>
<div>
<div class="header-elements mb-3 ml-3">
  <form action="{{ route('admin.approvedPaymentReport') }}" method="GET">
    <div class="form-group row mb-0">
      <div class="col-lg-2">   
        <input type="text" class="form-control"
          placeholder="Payment Method..." name="payment_mode" value="{{@$search_data['payment_mode']}}">
      </div>
      <div class="col-lg-4" style="display:inline-flex;">
        <label style="margin: 5px auto">From </label>
        <input type="text" class="form-control-sm form-control daterange-single" name="report_start_date" value="{{@$search_data['start_date']}}" placeholder="Start Date" style="width: 43%" />&nbsp;&nbsp;&nbsp;
        <label style="margin: 5px auto">To </label>
        <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date" value="{{@$search_data['end_date']}}" placeholder="End Date" style="width: 43%"/>
      </div>
      <div class="col-lg-1">
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
    </div>
  </form>
</div>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead class="table-dark">
          <tr>
            <th>Date</th>
            <th>Store Name</th>
            <th>Order ID</th>
            <th>Order Type</th>
            <th>Zone</th>
            <th>Payment Mode</th>
            <th>Approved by</th>
          </tr>
        </thead>
        @if (count($orders) > 0)
        <tbody>
          @foreach ($orders as $order)
          <tr>
            <td>{{ Carbon\Carbon::parse($order->created_at)->format('d m Y h:i A') }}</td>
            <td>{{ $order->order->restaurant->name }}</td>
            <td><a href="{{ route('admin.viewOrder', $order->order->unique_order_id) }}"><span style="font-size: 0.8rem; font-weight: 700;">{{ $order->order->unique_order_id }}</span></a></td>
            <td><span class="badge badge-flat border-grey-800 text-default text-capitalize">
              {{ $order->order->delivery_type == 1 ? "Delivery" : "Self Pickup" }}
            </td>
            <td>@if (!is_null($order->zone))<span class="badge badge-flat border-grey-800 text-default text-capitalize">
              {{ $order->zone->name }}@endif
            </td>
            <td>
              <span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $order->order->payment_mode }}
            </td>
            <td>{{ $order->user->id . " - " . $order->user->name}}</td>
          </tr>
          @endforeach
        </tbody>
        @else
        <tfoot>
          <tr>
            <center><h3 class="mt-3">No data found</h3></center>
          </tr>
        </tfoot>
        @endif
      </table>
      <div class="mt-3">
        {{ $orders->appends($_GET)->links() }}
      </div>
    </div>
  </div>
</div>
<script>
  
  $('.selectRange').select2();
  
  $('.daterange-single').daterangepicker({ 
    singleDatePicker: true,
  });
  
  $('#printButton').on('click',function(){
    $('.table').printThis();
  });
</script>
@endsection