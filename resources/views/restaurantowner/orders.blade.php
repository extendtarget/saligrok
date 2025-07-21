@extends('admin.layouts.master')
@section("title") {{__('storeDashboard.opTitle')}}
@endsection
@section('content')
<style>
    .pulse {
        display: inline-block;
        width: 12.5px;
        height: 12.5px;
        border-radius: 50%;
        animation: pulse 1.2s infinite;
        vertical-align: middle;
        margin: -3px 5px 0 0;
    }

    .pulse-warning {
        background: #ffc107;
    }

    .pulse-danger {
        background: #ff5722;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 87, 34, 0.5);
        }

        50% {
            box-shadow: 0 0 0 26px rgba(255, 87, 34, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(255, 87, 34, 0);
        }
    }

    .linked-item {
        color: #4e535a;
    }

    .linked-item:hover {
        color: #8360c3;
        text-decoration: underline;
        opacity: 1;
    }
</style>

<div class="content mt-3">
    <div class="d-flex justify-content-between my-2">
        <h3><strong>{{__('storeDashboard.ordersPageTitle')}}</strong></h3>
        <div>
             @if(\Nwidart\Modules\Facades\Module::find('CallAndOrder') &&
            \Nwidart\Modules\Facades\Module::find('CallAndOrder')->isEnabled())
            
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mr-2" id="manualOrderForGuest">
                <b><i class="icon-clipboard3"></i></b>
                Order for Guest
            </button>
            
            @endif
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left" id="clearFilterAndState"> <b><i
                        class=" icon-reload-alt"></i></b> {{ __('storeDashboard.resetFilterButton') }}</button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <div class="table-responsive">
                    <table class="table table-striped" id="ordersDataTable" width="100%">
                        <thead>
                            <tr>
                                <th class="hidden">ID</th>
                                <th>{{ __('storeDashboard.opTableOrderId') }}</th>
                                <th>{{ __('storeDashboard.opTableStatus') }}</th>
                                <th>{{ __('storeDashboard.opTableRestName') }}</th>
                                <th>{{ __('storeDashboard.ovPaymentMode') }}</th>
                                <th>{{ __('storeDashboard.ovTotal') }}</th>
                                <th>{{ __('storeDashboard.ovOrderPlaced') }}</th>
                                <th class="text-center">{{ __('storeDashboard.liveTimerText') }}</th>
                                <th class="text-center" style="width: 10%;"><i class="
                                    icon-circle-down2"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
    
        $('body').tooltip({selector: '[data-popup="tooltip"]'});
        
         var datatable = $('#ordersDataTable').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            lengthMenu: [ 10, 25, 50, 100, 200, 500 ],
            order: [[ 0, "desc" ]],
            ajax: '{{ route('restaurant.ordersDataTable') }}',
            columns: [
                {data: 'id', visible: false, searchable: false},
                {data: 'unique_order_id'},
                {data: 'orderstatus_id', name: "orderstatus.name"},
                {data: 'restaurant_name', name: "restaurant.name"},
                {data: 'payment_mode'},
                {data: 'total'},
                {data: 'created_at'},
                {data: 'live_timer', searchable: false, orderable: false},
                {data: 'action', sortable: false, searchable: false, reorder: false},
            ],
            fixedColumns: {
                leftColumns: 0,
                rightColumns: 1
            },
            colReorder: {
                   fixedColumnsRight: 1
               },
            drawCallback: function( settings ) {
                $('select').select2({
                   minimumResultsForSearch: Infinity,
                   width: 'auto'
                });
                timer = setInterval(updateClock, 1000);

                var newDate = new Date();
                console.log(newDate)
                var newStamp = newDate.getTime();

                var timer; 

                function updateClock() {

                    $('.liveTimer').each(function(index, el) {
                        var orderCreatedData = $(this).attr("title");
                        var startDateTime = new Date(orderCreatedData); 
                        var startStamp = startDateTime.getTime();
                    
                        newDate = new Date();
                        newStamp = newDate.getTime();
                        var diff = Math.round((newStamp-startStamp)/1000);
                        
                        var d = Math.floor(diff/(24*60*60));
                        diff = diff-(d*24*60*60);
                        var h = Math.floor(diff/(60*60));
                        diff = diff-(h*60*60);
                        var m = Math.floor(diff/(60));
                        diff = diff-(m*60);
                        var s = diff;
                        var checkDay = d > 0 ? true : false;
                        var checkHour = h > 0 ? true : false;
                        var checkMin = m > 0 ? true : false;
                        var checkSec = s > 0 ? true : false;
                        var formattedTime = checkDay ? d+ " day" : "";
                        formattedTime += checkHour ? " " +h+ " hr" : "";
                        formattedTime += checkMin ? " " +m+ " min" : "";
                        formattedTime += checkSec ? " " +s+ " sec" : "";

                        $(this).text(formattedTime);
                    });
                }
            },
            scrollX: true,
            scrollCollapse: true,
            dom: '<"custom-processing-banner"r>flBtip',
            language: {
                search: '_INPUT_',
                searchPlaceholder: '{{__('storeDashboard.searchPlaceholder')}}',
                lengthMenu: '_MENU_',
                paginate: { 'first': 'First', 'last': 'Last', 'next': '&rarr;', 'previous': '&larr;' },
                processing: '<i class="icon-spinner10 spinner position-left mr-1"></i>{{ __('storeDashboard.waitingForServerResponseText') }}'
            },
           buttons: {
                   dom: {
                       button: {
                           className: 'btn btn-default'
                       }
                   },
                   buttons: [
                       {extend: 'csv', filename: 'orders-'+ new Date().toISOString().slice(0,10), text: 'Export as CSV'},
                   ]
               }
        });
    
         $('#clearFilterAndState').click(function(event) {
            if (datatable) {
                datatable.state.clear();
                window.location.reload();
            }
         });
    });
</script>

<script>
    $(function () {
        $('.form-control-uniform').uniform();

        $("#showPassword").click(function (e) { 
            $("#newUserPassword").attr("type", "text");
        });
    
        $('.select').select2({
            minimumResultsForSearch: Infinity,
            placeholder: 'Select Role/s (Old roles will be revoked and these roles will be applied)',
        });

         $("[name='role']").change(function(event) {
            if ($(this).val() == "Delivery Guy") {
                $('#deliveryGuyDetails').removeClass('hidden');
                $("[name='delivery_name']").attr('required', 'required');
            }
            else {
                $('#deliveryGuyDetails').addClass('hidden');
                $("[name='delivery_name']").removeAttr('required')
            }
        });
        
        $('.commission_rate').numeric({ allowThouSep:false, maxDecimalPlaces: 2, max: 100, allowMinus: false });
        $('.cash_limit').numeric({allowThouSep:false, maxDecimalPlaces: 2, allowMinus: false });

        $('body').tooltip({selector: '[data-popup="tooltip"]'});
         var datatable = $('#usersDatatable').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            lengthMenu: [ 10, 25, 50, 100, 200, 500 ],
            order: [[ 0, "desc" ]],
            ajax: '{{ route('admin.customerDatatable') }}',
            columns: [
                {data: 'id', visible: false, searchable: false},
                {data: 'name'},
                {data: 'email'},
                {data: 'phone'},
                {data: 'role', sortable: false, searchable: false},
                {data: 'wallet', sortable: false, searchable: false,},
                {data: 'created_at'},
                {data: 'action', sortable: false, searchable: false},
            ],
            colReorder: true,
            drawCallback: function( settings ) {
                $('select').select2({
                   minimumResultsForSearch: Infinity,
                   width: 'auto'
                });
            },
            scrollX: true,
            scrollCollapse: true,
            @role('Admin')
            dom: '<"custom-processing-banner"r>flBtip',
            @else
            dom: '<"custom-processing-banner"r>fltip',
            @endrole
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search with anything...',
                lengthMenu: '_MENU_',
                paginate: { 'first': 'First', 'last': 'Last', 'next': '&rarr;', 'previous': '&larr;' },
                processing: '<i class="icon-spinner10 spinner position-left mr-1"></i>Waiting for server response...'
            },
            
           buttons: {
                   dom: {
                       button: {
                           className: 'btn btn-default'
                       }
                   },
                   buttons: [
                       {extend: 'csv', filename: 'users-'+ new Date().toISOString().slice(0,10), text: 'Export as CSV'},
                   ]
               }
        });

         $('#clearFilterAndState').click(function(event) {
            if (datatable) {
                datatable.state.clear();
                window.location.reload();
            }
         });
    });
</script>

@if(\Nwidart\Modules\Facades\Module::find('CallAndOrder') &&
\Nwidart\Modules\Facades\Module::find('CallAndOrder')->isEnabled())
@include('callandorder::scripts')
@endif

@endsection