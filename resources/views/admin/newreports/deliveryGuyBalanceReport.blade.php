@extends('admin.layouts.master')
@section("title") Delivery Guy Balance - Reports
@endsection
@section('content')
<div class="page-header">
<div class="page-header-content header-elements-md-inline">
  <div class="page-title d-flex">
    <h4><i class="icon-circle-right2 mr-2"></i>
      <span class="font-weight-bold mr-2">Delivery Guy Balance Report (Work Under Progress)</span>
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
                <table class="table">
                    <thead>
                        <tr>
                          <th>
                              ID  
                          </th>
                          <th>
                              Delivery Guy Name
                          </th>
                          <th>
                              Balance
                          </th>
                          <th>
                              Action
                          </th>
                        </tr>
                    </thead>
                    @foreach ($delivery_details as $key => $delivery_guy)
                    <tbody>
                      <tr>
                        <td>3</td>
                        <td>Bittu Jain</td>
                        <td₹300</td>
                        <td><form action="{{ route('admin.substractMoneyFromWallet') }}" method="POST" id="substractAmountForm" class="hidden" style="margin-top: 2rem;">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                     <div class="form-group row">
                        <label class="col-lg-4 col-form-label"><span class="text-danger">*</span>Create Payout</label>
                        <div class="col-lg-8">
                            <input type="text" class="form-control form-control-lg balance" name="substract_amount"
                                placeholder="Amount in {{ config('setting.currencyFormat') }}" required>
                        </div>
                    </div>
                     <div class="form-group row">
                        <label class="col-lg-4 col-form-label"><span class="text-danger">*</span>Message:</label>
                        <div class="col-lg-8">
                            <input type="text" class="form-control form-control-lg" name="substract_amount_description"
                                placeholder="Short Description or Message" required>
                        </div>
                    </div>
                    @csrf
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                        Confirm Payout
                        <i class="icon-database-insert ml-1"></i>
                        </button>
                    </div>
                </form></td>
                      </tr>
                    </tbody>
                    @endforeach
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