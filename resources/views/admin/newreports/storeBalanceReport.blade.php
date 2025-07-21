@extends('admin.layouts.master')
@section("title") Store Balance - Reports
@endsection
@section('content')
<div class="page-header">
<div class="page-header-content header-elements-md-inline">
  <div class="page-title d-flex">
    <h4><i class="icon-circle-right2 mr-2"></i>
      <span class="font-weight-bold mr-2">Store Balance Report</span>
      <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
    </h4>
  </div>
</div>
<div class="row">
    <div class="col-md-12">
        <a href="javascript:void(0)" id="printButton" class="btn btn-sm btn-primary my-2" style="color: #fff; border: 1px solid #ccc; float: right;"><i class="icon-printer mr-1"></i> Print Report</a>
    </div>
</div>
<div class ="card" style=" display:inline-block;">
    <div class="card-body">
            <div class="table-responsive">
                @php
                    $grandTotalSalesBalance = 0;
                    $grandTotalPayableBalance = 0;
                    $grandTotalPayoutBalance = 0;
                    $grandTotalBalance = 0;
                @endphp
                <table class="table">
                    <thead>
                        <tr>
                          <th>ID</th>
                          <th>Store Name</th>
                          <th>Sales Balance</th>
                          <th>Payable Balance</th>
                          <th>Payout Requested</th>
                          <th>Total Balance</th>
                          <th>Last Payout Requested Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($restaurants as $key => $restaurant)
                            @php
                                $payoutBalance = $restaurant->payoutBalance ? $restaurant->payoutBalance->amount : 0;
                                $payoutBalanceAfterCommission = $payoutBalance - ($payoutBalance * ($restaurant->commission_rate / 100));
                                
                                $requestedBalance = $restaurant->requestedBalance ? $restaurant->requestedBalance->amount : 0;
                                $requestedBalanceAfterCommission = $requestedBalance - ($requestedBalance * ($restaurant->commission_rate / 100));

                                $totalBalance = ($payoutBalanceAfterCommission + $requestedBalanceAfterCommission);
                                
                                $requestedBalanceDateTime = $restaurant->requestedBalance ? $restaurant->requestedBalance->updated_at->format('d-m-Y h:i A') : 'N/A';
                                
                                $grandTotalSalesBalance += $payoutBalance;
                                $grandTotalPayableBalance += $payoutBalanceAfterCommission;
                                $grandTotalPayoutBalance += $requestedBalanceAfterCommission;
                                $grandTotalBalance += $totalBalance;
                            @endphp
                          <tr>
                            <td>{{ $restaurant->id }}</td>
                            <td>{{ $restaurant->name }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ number_format($payoutBalance, 2) }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ number_format($payoutBalanceAfterCommission, 2) }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ number_format($requestedBalanceAfterCommission, 2) }}</td>
                            <td>{{ config('setting.currencyFormat') }}{{ number_format($totalBalance,2) }}</td>
                            <td>{{ $requestedBalanceDateTime }}</td>
                          </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                       <tr>
                            <th>TOTAL</th>
                            <th></th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($grandTotalSalesBalance, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($grandTotalPayableBalance,2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($grandTotalPayoutBalance, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($grandTotalBalance,2) }}</th>
                            <th></th>
                       </tr>
                    </tfoot>
                </table>
              <div class="mt-3">
                {{ $restaurants->appends($_GET)->links() }}
              </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
  $('#printButton').on('click',function(){
    $('.table').printThis();
  });
</script>
@endsection