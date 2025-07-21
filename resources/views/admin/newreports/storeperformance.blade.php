@extends('admin.layouts.master')
@section("title") Store Performance - Reports @endsection
@section('content')
<style>
}
.range-selector {
  margin: 10px;
}
</style>
<div class="page-header">
  <div class="page-header-content header-elements-md-inline">
    <div class="page-title d-flex">
      <h4><i class="icon-circle-right2 mr-2"></i>
        <span class="font-weight-bold mr-2">Store Performance Report</span>
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
              <div class="dashboard-display-number">{{ $displayRestaurants }}</div>
              <div class="font-size-sm text-uppercase text-muted">Total Approved Stores</div>
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
              <div class="dashboard-display-number">{{ config('setting.currencyFormat') }} {{ $displayEarnings }}
              </div>
              <div class="font-size-sm text-uppercase text-muted">Total Sales<br><span style="font-size: 0.5rem; font-weight: 500;">(Less Delivery Charges & Tip)</span></div>
            </div>
          </a>
        </div>
      </div>
    </div>

      @if($user_type == "admin")
      <form action="{{ route('admin.storeperformance') }}" method="GET">
      @else
      <form action="{{ route('restaurant.storeperformance') }}" method="GET">
      @endif
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
      </form>

    <div class="page-header-content header-elements-md-inline">
     <div class="page-title d-flex">
      <div class="card">
        <div class="mt-0">
          <a href="javascript:void(0)" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-printer mr-1"></i> Print Report</a>                    
          @if($user_type != "owner")
          <a href="{{ route('admin.exportReport', 'store_performance') }}" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-file-excel mr-1"></i>Export to XLS</a>
          @endif
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
               <th>ID</th>
               <th>Store/Restaurant Name</th>
               <th>Joined on</th>
               <th>Completed Orders</th>
               <th>Cancelled Orders</th>
               <th>Total Orders</th>
               <th>Net Earnings</th>
               <th>Admin Commission</th>
               <th>Total Amount</th>              
               <th>Average Completion</th>
             </thead>
             <tbody>
               @php $totalTimeTaking = $amountCountDisplay = $orderCountDisplay = $cancelCountDisplay = $completedCountDisplay = $earningCountDisplay = $adminTotalEarn = $totalTimeCount = 0; @endphp

               @foreach ($restaurants as $key => $restaurant)
               @if($restaurant->is_accepted == '1')
               <tr>
                <td>{{ $restaurant->id }}</td>
                <td>{{ $restaurant->name }}</td>
                <td>{{ $restaurant->created_at->format('d-m-Y') }}</td>
                <td>
                 {{ $restaurant->completedCount }}
                 @php $completedCountDisplay = ($restaurant->completedCount + $completedCountDisplay); @endphp
               </td>
               <td>
                 {{ $restaurant->cancelledCount }}
                 @php $cancelCountDisplay = ($restaurant->cancelledCount + $cancelCountDisplay); @endphp
               </td>
               <td>
                 {{ $restaurant->totalCount }}
                 @php $orderCountDisplay = ($restaurant->totalCount + $orderCountDisplay); @endphp
               </td>
               <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->totalEarningData }}
                @php $earningCountDisplay = ($restaurant->totalEarningData + $earningCountDisplay); @endphp
              </td>
              <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->adminEarning }}
                @php $adminTotalEarn = ($restaurant->adminEarning + $adminTotalEarn); @endphp
              </td>      
               <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->totalAmountData }}
                @php $amountCountDisplay = ($restaurant->totalAmountData + $amountCountDisplay); @endphp
              </td>            
              <td>                          
               
               @if($restaurant->deliveryTime !== 0)
               @php $totalTimeTaking = ((float)$restaurant->deliveryTime + (float)$totalTimeTaking); $totalTimeCount++; @endphp
                
                @if ($restaurant->deliveryTime > 45 || $restaurant->deliveryTime == 45)
                <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left" style="background-color: red; color: white;">
                  {{$restaurant->deliveryTime}} minutes
                </span>
                @endif
                
                @if ($restaurant->deliveryTime > 30 && $restaurant->deliveryTime < 45)
                <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left" style="background-color: #ff8400; color: white;">
                  {{$restaurant->deliveryTime}} minutes
                </span>
                @endif

                @if ($restaurant->deliveryTime < 30 || $restaurant->deliveryTime == 30)
                <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left" style="background-color: green; color: white;">
                  {{$restaurant->deliveryTime}} minutes
                </span>
                @endif
                @else
                <span class="badge badge-flat border-grey-800 text-default text-capitalize text-left">
                  0 minutes
                </span>
                @endif

              </td>
            </tr>
            @endif
            @endforeach
            <hr>
          </tbody>
          <tfoot>
            <tr>
              <th>TOTAL</th>
              <th></th>
              <th></th>
              <th>{{ $completedCountDisplay }}</th>
              <th>{{ $cancelCountDisplay }}</th>
              <th>{{ $orderCountDisplay }}</th>
              <th>{{ config('setting.currencyFormat') }}{{ $earningCountDisplay }}</th>
              <th>{{ config('setting.currencyFormat') }}{{ $adminTotalEarn }}</th>       
              <th>{{ config('setting.currencyFormat') }}{{ $amountCountDisplay }}</th>                          
              @if($totalTimeCount !== 0)
              <th>{{number_format(($totalTimeTaking / $totalTimeCount),2) }} minutes</th>
              @else
              <th>{{$totalTimeTaking}} minutes</th>
              @endif
            </tr>
          </table>
          <div class="mt-3">
            {{ $restaurants->appends($_GET)->links() }}
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