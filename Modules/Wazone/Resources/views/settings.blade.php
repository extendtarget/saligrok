@extends('admin.layouts.master')
@section("title") Wazone - Module
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Wazone</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Settings</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
        <!-- <a class="btn btn-warning" role="button" href="{{ route('Wazone.show') }}" target="_blank">SERVER CONNECTION</a> -->
    </div>
</div>

<div class="content">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body" style="min-height: 60vh;">
                <form action="{{ route('Wazone.saveWazoneSettings') }}" method="POST" enctype="multipart/form-data"
                    id="storeMainForm" style="min-height: 60vh;">
                    @csrf
                    <input type="hidden" name="window_redirect_hash" value="">

                    <div class="justify-content-lg-left">
                        <ul class="nav nav-pills nav-pills-main mr-lg-3 wmin-lg-250 mb-lg-0">
                            <li class="nav-item">
                                <a href="#serverSettings" class="nav-link active" data-toggle="tab">
                                    <i class="icon-server mr-2"></i>
                                    Server settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#messageTemplates" class="nav-link" data-toggle="tab">
                                    <i class="icon-stack mr-2"></i>
                                    Message templates
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0)" class="nav-link" data-toggle="tab" id="userSettings">
                                    <i class="icon-users mr-2"></i>
                                    Users settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0)" class="nav-link" data-toggle="tab" id="storeSettings">
                                    <i class="icon-store2 mr-2"></i>
                                    Stores settings
                                </a>
                            </li>
                        </ul>
                        <hr>
                        <div class="tab-content" style="width: 100%; padding: 0 25px;">

                            <div class="tab-pane fade show active" id="serverSettings">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Server Details (for Whatsify gateway server v0.8.x and up)
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Whatsify admin panel:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="wazone_panel"
                                            value="{{ $setting['wazone_panel'] }}" placeholder="Enter wazone admin panel url"
                                            autocomplete="new-wazone_panel">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Whatsify API domain:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="wazone_server"
                                            value="{{ $setting['wazone_server'] }}" placeholder="Enter domain with https://" required
                                            autocomplete="new-wazone_server">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Device Account ID:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="wazone_sender"
                                            value="{{ $setting['wazone_sender'] }}" placeholder="Enter Device Account ID (not unique ID)" required
                                            autocomplete="new-wazone_sender">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Secret:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="wazone_token"
                                            value="{{ $setting['wazone_token'] }}" placeholder="Enter whatsify API key with Whatsapp Permissions" required
                                            autocomplete="new-wazone_token">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">If fail, send with:</label>
                                    <div class="col-lg-9 d-flex align-items-center">
                                        <select class="form-control form-control-lg" name="wazone_fallback">
                                            <option {{($setting['wazone_fallback'] == "none") ? 'selected' :''}} value="none">NONE</option>
                                            <option {{($setting['wazone_fallback'] == "msg91") ? 'selected' :''}} value="msg91">MSG91</option>
                                            <option {{($setting['wazone_fallback'] == "twilio") ? 'selected' :''}} value="twilio">TWILIO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Timeout:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="wazone_timeout"
                                            value="{{ $setting['wazone_timeout'] }}" placeholder="Enter sending timeout" required
                                            autocomplete="new-wazone_timeout">
                                    </div>
                                </div>
                                {{-- START Taken from SMS Settings --}}
                                <div class="form-group row form-group-feedback form-group-feedback-right">
                                    <label class="col-lg-3 col-form-label">Login/Registration Type</label>
                                    <div class="col-lg-9">
                                        <select name="enOLnR" class="form-control form-control-lg select">
                                            <option @if(config('setting.enOLnR') == "true") selected @endif value="true">Phone OTP Login (Recommended)</option>
                                            <option @if(config('setting.enOLnR') == "false") selected @endif value="false">Email/Password Login</option>
                                        </select>
                                    </div>
                                </div>
                                {{-- END Taken from SMS Settings --}}
                            </div>

                            <div class="tab-pane fade" id="messageTemplates">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    Message Templates
                                </legend>
                                <div class="container-fluid">
                                    <div class="row">
                                        <!-- first column -->
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Owner Message: (New Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_owner" class="form-control form-control-lg" cols=30 rows=6
                                                    placeholder="Message format to Owner">{{ $template['msg_owner'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Driver Message: (New Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_driver" class="form-control form-control-lg" cols=30 rows=6
                                                    placeholder="Message format to Driver">{{ $template['msg_driver'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Store Message: (New Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_store" class="form-control form-control-lg" cols=30 rows=6
                                                    placeholder="Message format to Store">{{ $template['msg_store'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Customer Message: (New Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_customer" class="form-control form-control-lg" cols=30 rows=6
                                                    placeholder="Message format to Customer">{{ $template['msg_customer'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Admin Message: (New Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_admin" class="form-control form-control-lg" cols=30 rows=6
                                                    placeholder="Message format for Admin">{{ $template['msg_admin'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                                <label class="col-lg-3 col-form-label"><strong>Admin Message: (Order Picked Up)</strong></label>
                                                <div class="col-lg-9">
                                                    <textarea name="msg_admin_status4" class="form-control form-control-lg" cols=30 rows=6
                                                        placeholder="Message for Admin when driver arrived at Store">{{ $template['msg_admin_status4'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Owner/Store/Admin Message: (Canceled Order)</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_cancel" class="form-control form-control-lg" cols=30 rows=3
                                                    placeholder="Message format for canceled order">{{ $template['msg_cancel'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>OTP Message:</strong> {otp} only</label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_otp" class="form-control form-control-lg" cols=30 rows=3
                                                    placeholder="Message format for OTP">{{ $template['msg_otp'] }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- second column -->
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Preparing Order:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status2" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Store accept order">{{ $template['msg_status2'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Driver Assigned:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status3" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Driver accept order">{{ $template['msg_status3'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Order Picked Up:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status4" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When driver arrived at Store">{{ $template['msg_status4'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                                <label class="col-lg-3 col-form-label"><strong>Guest Order Picked Up:</strong></label>
                                                <div class="col-lg-9">
                                                    <textarea name="msg_guest_status4" class="form-control form-control-lg" cols=30 rows=2
                                                        placeholder="Message for Guest when driver arrived at Store">{{ $template['msg_guest_status4'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        <div class="form-group row">
                                            <!--<label class="col-lg-3 col-form-label"><strong>Order Delivered:</strong></label>-->
                                            <!--<div class="col-lg-9">-->
                                            <!--    <textarea name="msg_status5" class="form-control form-control-lg" cols=30 rows=2-->
                                            <!--        placeholder="When Driver arrived at Customer">{{ $template['msg_status5'] }}</textarea>-->
                                            <!--</div>-->
                                            <label class="col-lg-3 col-form-label"><strong>Order Delivered:</strong></label>
                                                <div class="col-lg-9">
                                                    <textarea name="msg_status5" class="form-control form-control-lg" cols=30 rows=2
                                                        placeholder="When Driver arrived at Customer">{{ $template['msg_status5'] }}</textarea>
                                                </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Guest Order Delivered:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_guest_status5" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="Message for Guest when Driver arrived at Customer">{{ $template['msg_guest_status5'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Order Canceled:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status6" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Order canceled">{{ $template['msg_status6'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Ready for Pickup:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status7" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Store finished prepare items">{{ $template['msg_status7'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Awaiting Payment:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status8" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Customer not paid">{{ $template['msg_status8'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Payment Failed:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status9" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When Customer payment failed">{{ $template['msg_status9'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Order Scheduled:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status10" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When scheduled order placed">{{ $template['msg_status10'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label"><strong>Order Confirmed:</strong></label>
                                            <div class="col-lg-9">
                                                <textarea name="msg_status11" class="form-control form-control-lg" cols=30 rows=2
                                                    placeholder="When order has been confirmed">{{ $template['msg_status11'] }}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-12 col-form-label"><strong>Available tags:</strong><br>
                                             {target}]
                                            {order_id}
                                            {unique_order_id}
                                            {payment_mode}
                                            {items}
                                            {total}
                                            {sub_total}
                                            {restaurant_charge}
                                            {delivery_charge} <!-- added by Aya -->
                                            {tax_amount}
                                            {tip_amount}
                                            {coupon_amount} <!-- added by Aya -->
                                            {payable} <!-- added by Aya -->
                                            {wallet_amount} <!-- added by Aya -->
                                            {wallet_balance} <!-- added by Aya -->
                                            {order_comment}
                                            {actual_delivery_charge}
                                            {restaurant_id}
                                            {restaurant_name}
                                            {restaurant_phone}
                                            {customer_id}
                                            {customer_name}
                                            {customer_phone}
                                            {receive_from_driver}
                                            {order_type}
                                            {wallet_message} <!-- added by Aya -->
                                            {cancel_reason} <!-- added by qusay -->
                                            {otp}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-right mt-5">
                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg btnUpdateUser">
                            <b><i class="icon-database-insert ml-1"></i></b>
                            Update Server settings & Message templates
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="content" id="userSettingsBlock" style="margin-bottom: 5rem;">
    <div class="content mt-3">
        <div class="card">
            <div class="card-body">
                <div class="page-title d-flex">
                    <h4>
                        <span class="font-weight-bold mr-2">Total</span>
                        <i class="icon-circle-right2 mr-2"></i>
                        <span class="font-weight-bold mr-2"> {{ $userCount }} users</span>
                    </h4>
                </div>
                <a class="btn btn-danger float-right" role="button" href="{{ route('Wazone.disableAllUsers') }}">DISABLE ALL USERS</a>
                <a class="btn btn-success float-right" role="button" href="{{ route('Wazone.enableAllUsers') }}">ENABLE ALL USERS</a>
                <div class="table-responsive">
                    <table class="table table-striped" id="usersDatatable" width="100%">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%">Name</th>
                                <th style="width: 15%">Email</th>
                                <th style="width: 15%">Phone</th>
                                <th style="width: 5%">Role</th>
                                <th class="text-center" style="width: 5%;"><i class="icon-circle-down2"></i></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content" id="storeSettingsBlock" style="margin-bottom: 5rem;">
    <div class="content">
        <div class="card">
            <div class="card-body">
            <div class="page-title d-flex">
                    <h4>
                        <span class="font-weight-bold mr-2">Total</span>
                        <i class="icon-circle-right2 mr-2"></i>
                        <span class="font-weight-bold mr-2"> {{ $restaurantCount }} stores</span>
                    </h4>
                </div>
                <a class="btn btn-danger float-right" role="button" href="{{ route('Wazone.disableAllStores') }}">DISABLE ALL STORES</a>
                <a class="btn btn-success float-right" role="button" href="{{ route('Wazone.enableAllStores') }}">ENABLE ALL STORES</a>
                <div class="table-responsive">
                    <table class="table table-datatable" id="storesDatatable" width="100%">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 10%">Image</th>
                                <th style="width: 15%">Name</th>
                                <th style="width: 15%">Phone</th>
                                <th class="text-center" style="width: 5%;"><i class="icon-circle-down2"></i></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="mt-3">
                        {{ $restaurants->appends($_GET)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.form-control-uniform').uniform();

        $('body').tooltip({selector: '[data-popup="tooltip"]'});
         var datatable = $('#usersDatatable').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            lengthMenu: [ 10, 25, 50, 100, 200, 500 ],
            order: [[ 0, "desc" ]],
            ajax: '{{ route('Wazone.usersDatatable') }}',
            columns: [
                {data: 'id', visible: false, searchable: false},
                {data: 'name'},
                {data: 'email'},
                {data: 'phone'},
                {data: 'role', sortable: false, name: 'roles.name'},
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
            dom: '<"custom-processing-banner"r>flBtip',
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
<script>
    $(function () {
        $('.form-control-uniform').uniform();

        $('body').tooltip({selector: '[data-popup="tooltip"]'});
         var datatable = $('#storesDatatable').DataTable({
            searchDelay: 1000,
            processing: true,
            serverSide: true,
            stateSave: true,
            lengthMenu: [ 10, 25, 50, 100, 200, 500 ],
            order: [[ 0, "desc" ]],
            ajax: '{{ route('Wazone.storesDatatable') }}',
            columns: [
                {data: 'id', visible: false, searchable: false, sortable: false},
                {data: 'image', searchable: false, sortable: false},
                {data: 'name', sortable: true},
                {data: 'phone', searchable: true, sortable: true},
                {data: 'action', searchable: false, sortable: false},
            ],
            colReorder: false,
            drawCallback: function( settings ) {
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
                sEmptyTable: "No data found",
                lengthMenu: '_MENU_',
                paginate: { 'first': 'First', 'last': 'Last', 'next': '&rarr;', 'previous': '&larr;' },
                processing: '<i class="icon-spinner2 spinner position-left mr-1"></i>Please wait...'
            },
           buttons: {
                   dom: {
                       button: {
                           className: 'btn btn-default'
                       }
                   },
                   buttons: [
                       {extend: 'csv', filename: 'stores-'+ new Date().toISOString().slice(0,10), text: 'Export as CSV'},
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
    $('#userSettings').click(function(event) {
        var targetOffset = $('#userSettingsBlock').offset().top - 70;
        $('html, body').animate({scrollTop: targetOffset}, 500);
    });

    $('#storeSettings').click(function(event) {
        var targetOffset = $('#storeSettingsBlock').offset().top - 70;
        $('html, body').animate({scrollTop: targetOffset}, 500);
    });
</script>

@endsection