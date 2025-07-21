@extends('admin.layouts.master')
@section("title") Stores - Wazone
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                @if(empty($query))
                <span class="font-weight-bold mr-2">TOTAL</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $count }} Stores</span>
                @else
                <span class="font-weight-bold mr-2">TOTAL</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $count }} Stores</span>
                <br>
                <span class="font-weight-normal mr-2">Showing results for "{{ $query }}"</span>
                @endif
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#wazoneSettingsModal">WAZONE SETTINGS</button>
        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#messageTemplatesModal">MESSAGE TEMPLATES</button>
        <a class="btn btn-warning" role="button" href="{{ route('Wazone.users') }}" >USER SETTINGS</a>
        <a class="btn btn-warning" role="button" href="{{ route('Wazone.restaurants') }}" >STORE SETTINGS</a>
    </div>
</div>
<div class="content">
    <div class="card">
        <div class="card-body">
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

<div id="wazoneSettingsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Wazone Settings</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
            <div class="container-fluid">
                <form action="{{ route("Wazone.saveWazoneSettings") }}" method="POST">
                    <?php
                        $filename = \Module::getModulePath('Wazone') . 'wazone_settings.json';
                        if (file_exists($filename)) {
                            $data = json_decode(file_get_contents($filename), true);
                        } else {
                            die('Unable to open file for read!');
                        }
                    ?>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Wazone Server:</label>
                        <div class="col-lg-8">
                            <input type="text" name="wazone_server" class="form-control form-control-lg"
                                placeholder="https://bot.visimisi.net" value="{{ $data['wazone_server'] }}" required autocomplete="new-wazone_server">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Wazone Sender:</label>
                        <div class="col-lg-8">
                            <input type="text" name="wazone_sender" class="form-control form-control-lg"
                                placeholder="6281212341234" value="{{ $data['wazone_sender'] }}" required autocomplete="new-wazone_sender">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Api Key (Token):</label>
                        <div class="col-lg-8">
                            <input type="text" name="wazone_apikey" class="form-control form-control-lg"
                                placeholder="256841" value="{{ $data['wazone_apikey'] }}" required autocomplete="new-wazone_apikey">
                        </div>
                    </div>
                    <div class="form-group row form-group-feedback form-group-feedback-right">
                        <label class="col-lg-4 col-form-label">Fallback if failed:</label>
                        <div class="col-lg-8">
                        <select class="form-control form-control-lg" name="wazone_fallback">
                            <option @if($data['wazone_fallback'] == "none") selected @endif value="none">NONE</option>
                            <option @if($data['wazone_fallback'] == "msg91") selected @endif value="msg91">MSG91</option>
                            <option @if($data['wazone_fallback'] == "twilio") selected @endif value="twilio">TWILIO</option>
                        </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Wazone Timeout<br>(default 30 seconds):</label>
                        <div class="col-lg-8">
                            <input type="text" name="wazone_timeout" class="form-control form-control-lg"
                                placeholder="Timeout in seconds" value="{{ $data['wazone_timeout'] }}" required autocomplete="new-wazone_timeout">
                        </div>
                    </div>
                    {{-- START Taken from SMS Settings --}}
                    <div class="form-group row form-group-feedback form-group-feedback-right">
                        <label class="col-lg-4 col-form-label">Login/Registration Type</label>
                        <div class="col-lg-8">
                            <select name="enOLnR" class="form-control form-control-lg select">
                                <option @if(config('setting.enOLnR') == "true") selected @endif value="true">Phone OTP Login (Recommended)</option>
                                <option @if(config('setting.enOLnR') == "false") selected @endif value="false">Email/Password Login</option>
                            </select>
                        </div>
                    </div>
                    {{-- END Taken from SMS Settings --}}
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                        SAVE
                        <i class="icon-database-insert ml-1"></i></button>
                    </div>
                    @csrf
                </form>
            </div>
        </div>
        </div>
    </div>
</div>

<div id="messageTemplatesModal" class="modal fade bd-example-modal-lg" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Message Templates</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="{{ route("Wazone.saveMessageTemplates") }}" method="POST">
                    <?php
                        $filename = \Module::getModulePath('Wazone') . 'message_templates.json';
                        if (file_exists($filename)) {
                            $data = json_decode(file_get_contents($filename), true);
                        } else {
                            die('Unable to open file for read!');
                        }
                    ?>
                    <div class="container-fluid">
                        <div class="row">
                            <!-- first column -->
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Owner Message: (New Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_owner" class="form-control form-control-lg" cols=30 rows=6
                                        placeholder="Message format to Owner">{{ $data['msg_owner'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Driver Message: (New Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_driver" class="form-control form-control-lg" cols=30 rows=6
                                        placeholder="Message format to Driver">{{ $data['msg_driver'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Store Message: (New Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_store" class="form-control form-control-lg" cols=30 rows=6
                                        placeholder="Message format to Store">{{ $data['msg_store'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Customer Message: (New Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_customer" class="form-control form-control-lg" cols=30 rows=6
                                        placeholder="Message format to Customer">{{ $data['msg_customer'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Admin Message: (New Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_admin" class="form-control form-control-lg" cols=30 rows=6
                                        placeholder="Message format for Admin">{{ $data['msg_admin'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Owner/Store/Admin Message: (Canceled Order)</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_cancel" class="form-control form-control-lg" cols=30 rows=3
                                        placeholder="Message format for canceled order">{{ $data['msg_cancel'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>OTP Message:</strong> {otp} only</label>
                                <div class="col-lg-9">
                                    <textarea name="msg_otp" class="form-control form-control-lg" cols=30 rows=3
                                        placeholder="Message format for OTP">{{ $data['msg_otp'] }}</textarea>
                                </div>
                            </div>
                        </div>
                        <!-- second column -->
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Preparing Order:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status2" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Store accept order">{{ $data['msg_status2'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Driver Assigned:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status3" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Driver accept order">{{ $data['msg_status3'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Order Picked Up:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status4" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When driver arrived at Store">{{ $data['msg_status4'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Order Delivered:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status5" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Driver arrived at Customer">{{ $data['msg_status5'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Order Canceled:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status6" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Order canceled">{{ $data['msg_status6'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Ready for Pickup:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status7" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Store finished prepare items">{{ $data['msg_status7'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Awaiting Payment:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status8" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Customer not paid">{{ $data['msg_status8'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Payment Failed:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status9" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When Customer payment failed">{{ $data['msg_status9'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Order Scheduled:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status10" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When scheduled order placed">{{ $data['msg_status10'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label"><strong>Order Confirmed:</strong></label>
                                <div class="col-lg-9">
                                    <textarea name="msg_status11" class="form-control form-control-lg" cols=30 rows=2
                                        placeholder="When order has been confirmed">{{ $data['msg_status11'] }}</textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-12 col-form-label"><strong>Available tags:</strong><br>
                                    {target}
                                    {unique_order_id}
                                    {payment_mode}
                                    {items}
                                    {total}
                                    {sub_total}
									{order_comment}
                                    {actual_delivery_charge}
                                    {restaurant_id}
                                    {restaurant_name}
                                    {restaurant_phone}
                                    {customer_id}
                                    {customer_name}
                                    {customer_phone}
                                </label>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">SAVE<i class="icon-database-insert ml-1"></i></button>
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
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
@endsection