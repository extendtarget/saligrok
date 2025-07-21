@extends('admin.layouts.master')
@section("title") Order-wise Tax - Reports
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4><i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Order-wise Tax Report</span>
                <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
    <div class="header-elements">
        <form action="{{ route('admin.orderWiseTaxReport') }}" method="GET">
            <div class="form-group row mb-0">
                <div class="col-lg-5" style="display: inline-flex;">
                    <label style="margin: 5px auto">From </label>
                    <input type="text" class="form-control-sm form-control daterange-single" name="report_start_date" value="{{@$search_data['start_date']}}" placeholder="Start Date" style="width: 43%" />&nbsp;&nbsp;&nbsp;
                    <label style="margin: 5px auto">To </label>
                    <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date" value="{{@$search_data['end_date']}}" placeholder="End Date" style="width: 43%"/>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-search4"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
          <div class="card">
              <div class="mt-0">
                <a href="javascript:void(0)" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-printer mr-1"></i> Print Report</a>            
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                               <th>Date</th>
                               <th>Order ID</th>
                               <th>Store Name</th>
                               <th>Tax Rate</th>
                               <th>Order Total</th>
                               <th>Tax Amount</th>
                           </tr>
                       </thead>
                       <tbody>
                         @php 
                         $totalOrder = 0;
                         $taxTotal = 0;
                         @endphp

                         @foreach ($orders as $order)

                         @php
                         $orderDate = $order->created_at->format('d-m-Y');
                         $restaurantName = $order->restaurant->name;
                         $orderTaxRate = $order->tax != NULL ? $order->tax . '%' : 'NA';
                         $orderAmount = $order->total;
                         $orderTaxAmountIfNull = (($order->total - $order->tip_amount) * $order->tax/100);
                         $orderTaxAmount = $order->tax_amount != NULL ? $order->tax_amount : $orderTaxAmountIfNull;

                         $totalOrder += $orderAmount;
                         $taxTotal += $orderTaxAmount;
                         @endphp
                         <tr>
                            <td>{{ $orderDate }}</td>
                            <td><a href="{{ route('admin.viewOrder', $order->unique_order_id) }}"><span style="font-size: 0.8rem; font-weight: 700;">{{ $order->unique_order_id }}</span></a></td>
                            <td>{{ $restaurantName }}</td>
                            <td>{{ $orderTaxRate }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ $orderAmount }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ $orderTaxAmount }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                     <th>TOTAL</th>
                     <th></th>
                     <th></th>
                     <th></th>
                     <th>{{ config('setting.currencyFormat') }} {{$totalOrder}}</th>
                     <th>{{ config('setting.currencyFormat') }} {{$taxTotal}}</th>
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

<script>
    $('#printButton').on('click',function(){
        $('.table').printThis();
    });


    $('.daterange-single').daterangepicker({ 
       singleDatePicker: true,
   });

</script>
@endsection



