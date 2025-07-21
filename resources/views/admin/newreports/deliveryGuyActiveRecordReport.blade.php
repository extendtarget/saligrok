@extends('admin.layouts.master')
@section("title") Delivery Guys Timing - Reports
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
        <span class="font-weight-bold mr-2">Delivery Guys Active Timing Report</span>
        <span class="badge badge-primary badge-pill animated flipInX mr-2"></span>
      </h4>
      <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
    </div>
  </div>
  <div>
    <div class="header-elements mb-3 ml-3">
      <form action="{{ route('admin.deliveryGuyActiveRecordReport') }}" method="GET">
        <div class="form-group row mb-0">
          <div class="col-lg-3">
            <select class="form-control form-control-sm mr-3 selectDB" name="deliver_by_id">
              <option value="" selected>Select Delivery Boy</option>
              @foreach ($deliveryGuys as $delivery_boy)
              <option value="{{ $delivery_boy->id }}" @if( app('request')->input('deliver_by_id') == $delivery_boy->id)
                selected @endif class="text-capitalize">{{ $delivery_boy->id }} - {{ $delivery_boy->name ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-4" style="display:inline-flex;">
            <label class="m-auto" style="margin-right: 1vh !important">From </label>
            <input type="text" class="form-control-sm form-control daterange-single mr-3" name="report_start_date"
              value="{{@$search_data['start_date']}}" placeholder="Start Date" />
            <label class="m-auto" style="margin-right: 1vh !important">To </label>
            <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date"
              value="{{@$search_data['end_date']}}" placeholder="End Date" />
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
          <button type="button" id="printButton" class="btn btn-sm btn-primary ml-3"><i class="icon-printer"></i></button>
          <button type="button" id="exportToCsv" class="btn btn-sm btn-primary ml-3"><i class="icon-file-excel"></i></button>
        </div>
      </form>
    </div>
    <div class="card col-lg-12">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped" id="records-table">
            <thead class="table-dark">
              <tr>
                <th>Date</th>
                <th>Delivery Guy Name</th>
                <th>Zone</th>
                <th>Online Time</th>
                <th>Offline Time</th>
                <th>Total Timing</th>
              </tr>
            </thead>
            <tbody>
              @if (count($records) > 0)
                @foreach ($records->reverse() as $record)
                  @if($record->user)
                    <tr>
                      <td>{{ Carbon\Carbon::parse($record->date)->format('d-m-Y') }}</td>
                      <td>{{ $record->user->id . " - " . ($record->user->name ?? '') }}</td>
                      <td><span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $record->user->zone ? $record->user->zone->name : '' }}</span></td>
                      <td>{{ Carbon\Carbon::parse($record->online_time)->format('h:i A') }}</td>
                      <td>{{ $record->offline_time ? Carbon\Carbon::parse($record->offline_time)->format('h:i A') : '' }}</td>
                      @php
                        $formattedTime = '';
                        if (!is_null($record->offline_time)) {
                          $startTime = Carbon\Carbon::parse($record->online_time);
                          $endTime = Carbon\Carbon::parse($record->offline_time);
                          $diffInMinutes = $startTime->diffInMinutes($endTime);

                          if ($diffInMinutes >= 60) {
                              $hours = floor($diffInMinutes / 60);
                              $minutes = $diffInMinutes % 60;
                              $formattedTime = $hours . ' hours ' . $minutes . ' minutes';
                          } else {
                              $formattedTime = $diffInMinutes . ' minutes';
                          }
                        }
                      @endphp
                      <td>{{ $formattedTime }}</td>
                    </tr>
                  @endif
                @endforeach
              @else
                <tr>
                  <td colspan="6" class="text-center">
                    <h3 class="mt-3">No data found</h3>
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
          <div class="mt-3">
            {{ $records->appends($_GET)->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://unpkg.com/tableexport@5.2.1/dist/js/tableexport.min.js"></script>
<script>
  $("#exportToCsv").click(function(){  
    $('.table').tableExport({
        type: 'xls',
        fileName: 'DeliveryGuyTiming'
      });
  });
  $('.selectDB').select2({
    placeholder: 'Select Delivery Guy',
    allowClear: true
  });
  $('.daterange-single').daterangepicker({
    singleDatePicker: true,
    timePicker: true
  });
  $('#printButton').on('click', function () {
    $('.table').printThis();
  });
</script>
@endsection