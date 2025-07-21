@extends('admin.layouts.master')
@section("title") Order Payments - Reports
@endsection
@section('content')
<style>
    .range-selector {
        margin: 10px;
    }
</style>
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4><i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Order Payment Report</span>
                <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
    <div>
        <div class="header-elements">
            <form action="{{ route('admin.paymentReport') }}" method="GET">
                <div class="form-group row mb-0">
                    <div class="col-lg-2.5">
                        <select class="form-control selectRest" name="restaurant_id" style="width: 100px !important;">
                        <option></option>
                        @foreach ($restaurants as $restaurant_select)
                        <option value="{{ $restaurant_select->id }}" @if( app('request')->input('restaurant_id') == $restaurant_select->id) selected @endif class="text-capitalize">{{ $restaurant_select->id }} - {{ $restaurant_select->name }}</option>
                        @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <input type="text" class="form-control" placeholder="Payment Method..." name="payment_mode"
                            value="{{@$search_data['payment_mode']}}">
                    </div>
                    <div class="col-lg-4" style="display:inline-flex;">
                        <label style="margin: 5px auto">From </label>
                        <input type="text" class="form-control-sm form-control daterange-single"
                            name="report_start_date" value="{{@$search_data['start_date']}}" placeholder="Start Date"
                            style="width: 43%"/>
                        <label style="margin: 5px auto">To </label>
                        <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date"
                            value="{{@$search_data['end_date']}}" placeholder="End Date" style="width: 43%" />
                    </div>
                    <div class="col-lg-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-search4"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="row">
            <div class="col-md-8 mt-4">
                <a href="javascript:void(0)" id="printButton" class="btn btn-sm btn-primary my-2"
                    style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-printer mr-1"></i> Print
                    Report</a>
                <!-- <a href="{{ route('admin.exportReport', 'store_wise') }}?restaurant_id={{@$search_data['restaurant_id']}}&payment_mode={{@$search_data['payment_mode']}}&report_start_date={{@$search_data['start_date']}}&report_end_date={{@$search_data['end_date']}}" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-file-excel mr-1"></i>Export to XLS</a> -->
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Store Name</th>
                                <th>Order ID</th>
                                <th>Payment Method</th>
                                <th>Order Total</th>
                                <th>By Wallet</th>
                                <th>By Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $orderAmountTotal = 0;
                                $orderWalletTotal = 0;
                                $orderPaymentTotal = 0;
                            @endphp

                            @foreach ($orders as $order)
                                @php
                                    $orderDate = $order->created_at->format('d-m-Y');
                                    $orderId = $order->unique_order_id;
                                    $restaurantName = $order->restaurant->name;
                                    $orderPaymentMode = $order->payment_mode;
                                    $orderAmount = $order->total;
                                    $orderWalletAmount = $order->wallet_amount != NULL ? $order->wallet_amount : '0';
                                    $orderPayment = ($orderAmount - $orderWalletAmount);
                                    
                                    $orderAmountTotal += $orderAmount;
                                    $orderWalletTotal += $orderWalletAmount;
                                    $orderPaymentTotal += $orderPayment;
                                @endphp
                                <tr>
                                    <td>{{ $orderDate }}</td>
                                    <td>{{ $restaurantName }}</td>
                                    <td><a href="{{ route('admin.viewOrder', $orderId) }}"><span style="font-size: 0.8rem; font-weight: 700;">{{ $orderId }}</span></a></td>
                                    <td><span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $orderPaymentMode }}</span></td>
                                    <td>{{ config('setting.currencyFormat') }}{{ $orderAmount }}</td>
                                    <td>{{ config('setting.currencyFormat') }}{{ $orderWalletAmount }}</td>
                                    <td>{{ config('setting.currencyFormat') }}{{ $orderPayment }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <th>Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{ config('setting.currencyFormat') }}{{ $orderAmountTotal }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ $orderWalletTotal }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ $orderPaymentTotal }}</th>
                        </tfoot>
                    </table>
                </div>
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
    $('.selectRest').select2({
    placeholder: 'Select Store',
    allowClear: true,
    width: "200px"
    });
</script>
@endsection