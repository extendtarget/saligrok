@extends('admin.layouts.master')
@section("title") Store Ledger Book - Reports
@endsection
@section('content')
<div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead>                    
                <tr>
                 <th>Date</th>
                 <th>Particulars</th>
                 <th>Debit</th>
                 <th>Credit</th>
                 <th>Balance</th>
               </tr>
             </thead>
             <tbody>
             	@foreach ($restaurants as $restaurant)
             	
             </tbody>
              <tfoot>
               <tr>
                 <th></th>
                 <th>TOTAL</th>
                 <th></th>
                 <th></th>
                 <th></th>
                 <th></th>
                 <th>{{ config('settings.currencyFormat') }}{{$totalNet}}</th>
                 <th></th>
                 <th>{{ config('settings.currencyFormat') }}{{$earningNet}}</th>
                 <th>{{ config('settings.currencyFormat') }}{{$subTotalNet}}</th>
                 <th>{{ config('settings.currencyFormat') }}{{$deliveryTotalNet}}</th>
                 <th>{{ config('settings.currencyFormat') }}{{$totalEarn}}</th>
               </tr>
             </tfoot>               
           </table>
           <div class="mt-3">
            {{ $orders->appends($_GET)->links() }}
          </div>
        </div>
      </div>
    </div>