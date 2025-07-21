@extends('admin.layouts.master')
@section("title") Orders - Dashboard
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

    @media (min-width: 1200px) {
        .container {
            max-width: 95%;
        }
    }

    
    .orders-info-alert {
        margin-bottom: 20px;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 14px;
    }

    
    #ordersDataTable th,
    #ordersDataTable td {
        padding: 12px 15px;
        vertical-align: middle;
    }

    #ordersDataTable th {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #333;
    }

    #ordersDataTable tbody tr:hover {
        background-color: #f1f3f5;
        transition: background-color 0.3s;
    }
</style>

<div class="content mt-3">
    <div class="d-flex justify-content-between my-2">
        <h3><strong>Order Management</strong></h3>
        <div>
            @if(\Nwidart\Modules\Facades\Module::find('CallAndOrder') &&
            \Nwidart\Modules\Facades\Module::find('CallAndOrder')->isEnabled())
            @can("login_as_customer")
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mr-2" id="manualOrderForGuest">
                <b><i class="icon-clipboard3"></i></b>
                Order for Guest
            </button>
            @endcan
            @endif
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left" id="clearFilterAndState"> 
                <b><i class="icon-reload-alt"></i></b> Reset All Filters
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="ordersDataTable" width="100%">
                    <thead>
                        <tr>
                            <th class="hidden">ID</th>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>Store Name</th>
                            <th>Mode</th>
                            <th>Total</th>
                            <th>Order Placed At</th>
                            <th class="text-center">Live Timer</th>
                            <th class="text-center" style="width: 10%;"><i class="icon-circle-down2"></i></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Customers -->
<div class="content mt-3">
    <div class="d-flex justify-content-between my-2">
        <h3><strong>Customers</strong></h3>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="usersDatatable" width="100%">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="text-center"><i class="icon-circle-down2"></i></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$(function() {
    $('body').tooltip({selector: '[data-popup="tooltip"]'});
    
    var ordersDatatable = $('#ordersDataTable').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        @can('Showing_entries')
            lengthMenu: [10, 25, 50, 100],
            @role('Admin')
                dom: '<"custom-processing-banner"r>flBtip',
            @else
                dom: '<"custom-processing-banner"r>fltip',
            @endrole
        @else
            @role('Admin')
                lengthMenu: [10, 25, 50, 100],
                dom: '<"custom-processing-banner"r>flBtip',
            @else
                lengthMenu: [],
                dom: '<"custom-processing-banner"r>fBti',
            @endrole
        @endcan
        pageLength: 10,
        order: [[0, "desc"]],
        ajax: '{{ route("admin.ordersDataTable") }}',
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
        drawCallback: function(settings) {
            $('select').select2({
                minimumResultsForSearch: Infinity,
                width: 'auto'
            });
            timer = setInterval(updateClock, 1000);

            var newDate = new Date();
            var newStamp = newDate.getTime();

            var timer;

            function updateClock() {
                $('.liveTimer').each(function(index, el) {
                    var orderCreatedData = $(this).attr("title");
                    var startDateTime = new Date(orderCreatedData);
                    var startStamp = startDateTime.getTime();

                    newDate = new Date();
                    newStamp = newDate.getTime();
                    var diff = Math.round((newStamp - startStamp) / 1000);

                    var d = Math.floor(diff / (24 * 60 * 60));
                    diff = diff - (d * 24 * 60 * 60);
                    var h = Math.floor(diff / (60 * 60));
                    diff = diff - (h * 60 * 60);
                    var m = Math.floor(diff / (60));
                    diff = diff - (m * 60);
                    var s = diff;
                    var checkDay = d > 0 ? true : false;
                    var checkHour = h > 0 ? true : false;
                    var checkMin = m > 0 ? true : false;
                    var checkSec = s > 0 ? true : false;
                    var formattedTime = checkDay ? d + " day" : "";
                    formattedTime += checkHour ? " " + h + " hr" : "";
                    formattedTime += checkMin ? " " + m + " min" : "";
                    formattedTime += checkSec ? " " + s + " sec" : "";

                    $(this).text(formattedTime);
                });
            }
        },
        scrollX: true,
        scrollCollapse: true,
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search with anything...',
            lengthMenu: '_MENU_',
            paginate: { 'first': 'First', 'last': 'Last', 'next': '→', 'previous': '←' },
            processing: '<i class="icon-spinner10 spinner position-left mr-1"></i>Waiting for server response...'
        },
        buttons: {
            dom: {
                button: {
                    className: 'btn btn-default'
                }
            },
            buttons: [
                {extend: 'csv', filename: 'orders-' + new Date().toISOString().slice(0, 10), text: 'Export as CSV'},
            ]
        }
    });

    $('#clearFilterAndState').click(function(event) {
        if (ordersDatatable) {
            ordersDatatable.state.clear();
            window.location.reload();
        }
    });

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
        } else {
            $('#deliveryGuyDetails').addClass('hidden');
            $("[name='delivery_name']").removeAttr('required');
        }
    });
    
    $('.commission_rate').numeric({allowThouSep:false, maxDecimalPlaces: 2, max: 100, allowMinus: false});
    $('.cash_limit').numeric({allowThouSep:false, maxDecimalPlaces: 2, allowMinus: false});

    $('body').tooltip({selector: '[data-popup="tooltip"]'});
    
    @can('all_users_view')
    var usersDatatable = $('#usersDatatable').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        @can('Showing_entries')
            lengthMenu: [10, 25, 50, 100],
            @role('Admin')
                dom: '<"custom-processing-banner"r>flBtip',
            @else
                dom: '<"custom-processing-banner"r>fltip',
            @endrole
        @else
            @role('Admin')
                lengthMenu: [10, 25, 50, 100],
                dom: '<"custom-processing-banner"r>flBtip',
            @else
                lengthMenu: [],
                dom: '<"custom-processing-banner"r>fBti',
            @endrole
        @endcan
        pageLength: 10,
        order: [[0, "desc"]],
        ajax: {
            url: '@if(\Nwidart\Modules\Facades\Module::find('CallAndOrder') && \Nwidart\Modules\Facades\Module::find('CallAndOrder')->isEnabled() && Request::is('callandorder/users')){{ route('cao.usersDatatable') }}@else{{ route('admin.usersDatatable') }}@endif',
            error: function(xhr, error, thrown) {
                console.log('AJAX Error: ' + xhr.status + ' - ' + error);
                alert('فشل في جلب بيانات العملاء. يرجى التحقق من الصلاحيات أو الاتصال بالمسؤول.');
            }
        },
        columns: [
            {data: 'name'},
            {data: 'phone'},
            {data: 'action', sortable: false, searchable: false},
        ],
        colReorder: true,
        drawCallback: function(settings) {
            $('select').select2({
                minimumResultsForSearch: Infinity,
                width: 'auto'
            });
        },
        scrollX: true,
        scrollCollapse: true,
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search with anything...',
            lengthMenu: '_MENU_',
            paginate: { 'first': 'First', 'last': 'Last', 'next': '→', 'previous': '←' },
            processing: '<i class="icon-spinner10 spinner position-left mr-1"></i>Waiting for server response...'
        },
        buttons: {
            dom: {
                button: {
                    className: 'btn btn-default'
                }
            },
            buttons: [
                {extend: 'csv', filename: 'users-' + new Date().toISOString().slice(0, 10), text: 'Export as CSV'},
            ]
        }
    });
    @else
        $('#usersDatatable').replaceWith('<div class="alert alert-warning">ليس لديك صلاحية لعرض العملاء.</div>');
    @endcan

    $('#clearFilterAndState').click(function(event) {
        if (usersDatatable) {
            usersDatatable.state.clear();
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