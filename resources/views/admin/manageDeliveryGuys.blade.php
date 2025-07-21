@extends('admin.layouts.master')
@section("title")
Delivery Guys | Dashboard
@endsection
@section('content')

<div class="content mt-3">
    <div class="d-flex justify-content-between my-2">
        <h3><strong>Delivery Guys</strong></h3>
        <div>
            @if(config('setting.iHaveFoodomaaDeliveryApp') == "true")
            <a href="{{ route('admin.deliveryEagleView') }}"
                class="btn btn-secondary btn-labeled btn-labeled-left mr-2"> <b><i class="icon-target2"></i></b>
                Eagle View</a>
            @endif
             @can('all_users_edit')
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mr-2" id="addNewUser"
                data-toggle="modal" data-target="#addNewUserModal">
                <b><i class="icon-plus2"></i></b>
                Add New User
            </button>
            @endcan
            <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left" id="clearFilterAndState"> <b><i
                        class="icon-reload-alt"></i></b> Reset All Filters</button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="usersDatatable" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>{{ config('setting.walletName') }}</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-center"><i class="
                                icon-circle-down2"></i></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="addNewUserModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Add New User</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="{{ route("admin.saveNewUser") }}" method="POST" enctype="multipart/form-data">
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Name:</label>
                        <div class="col-lg-9">
                            <input type="text" name="name" class="form-control form-control-lg"
                                placeholder="Enter Full Name" required autocomplete="new-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Email:</label>
                        <div class="col-lg-9">
                            <input type="text" name="email" class="form-control form-control-lg"
                                placeholder="Enter Email Address" required autocomplete="new-email">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Phone:</label>
                        <div class="col-lg-9">
                            <input type="text" name="phone" class="form-control form-control-lg"
                                placeholder="Enter Phone Number" required autocomplete="new-phone">
                        </div>
                    </div>
                    <div class="form-group row form-group-feedback form-group-feedback-right">
                        <label class="col-lg-3 col-form-label">Password:</label>
                        <div class="col-lg-9">
                            <input name="password" type="password" class="form-control form-control-lg"
                                placeholder="Enter Password (min 6 characters)" required autocomplete="new-password"
                                id="newUserPassword">
                        </div>
                        <div class="form-control-feedback form-control-feedback-lg">
                            <span id="showPassword"><i class="icon-unlocked2"></i> Show</span>
                        </div>
                    </div>
                    
                            <input name="role" type="hidden" value="Delivery Guy" class="form-control form-control-lg"

                        
                    <div id="deliveryGuyDetails" class="form-group row">
                        <legend class="font-weight-semibold text-uppercase font-size-sm">
                            <i class="icon-truck mr-2"></i> Delivery Guy Details
                        </legend>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Name or Nick Name:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg" name="delivery_name"
                                    placeholder="Enter Name or Nickname of Delivery Guy" autocomplete="new-name">
                                <span class="help-text text-muted">This name will be displayed to the
                                    user/customers</span>
                            </div>

                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Age</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg" name="delivery_age"
                                    placeholder="Enter Delivery Guy's Age">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Delivery Guy's Photo:</label>
                            <div class="col-lg-9">
                                <input type="file" class="form-control" name="delivery_photo">
                                <span class="help-text text-muted">Image size 250x250</span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Description</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg" name="delivery_description"
                                    placeholder="Enter Short Description about this Delivery Guy">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Vehicle Number</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg" name="delivery_vehicle_number"
                                    placeholder="Enter Delivery Guy's Vehicle Number">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Commission Rate %</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg commission_rate"
                                    name="delivery_commission_rate"
                                    placeholder="Commission Rate % (By detault, it's set to 5%)" value="5"
                                    required="required">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Cash Limit
                                ({{ config('setting.currencyFormat') }})</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg cash_limit" name="cash_limit"
                                    value="0.00" />
                                <p>Enter an amount after which you don't want delivery guy to receive any orders.
                                    <strong><mark>Zero(0) means no limit.</mark></strong></p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label">Tip Commission Rate %</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg tip-commission_rate"
                                    name="tip_commission_rate"
                                    placeholder="Commission Rate % (By detault, it's set to 5%)" value="100"
                                    required="required">
                            </div>
                        </div>
                    </div>
                   <div class="text-right" style="margin-right: 20px; margin-bottom: 20px;">
    <button type="submit" class="btn btn-primary">
        SAVE
        <i class="icon-database-insert ml-1"></i>
    </button>
</div>
                    @csrf
                    
                </form>
                

            </div>
        </div>
    </div>
</div>


<script>
    $('body').tooltip({selector: '[data-popup="tooltip"]'});
     var datatable = $('#usersDatatable').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        lengthMenu: [ 10, 25, 50, 100, 200, 500 ],
        order: [[ 0, "desc" ]],
        ajax: '{{ route('admin.deliveryGuyUsersDatatable') }}',
        columns: [
            {data: 'id', visible: false, searchable: false},
            {data: 'name'},
            {data: 'email'},
            {data: 'phone'},
            {data: 'wallet', sortable: false, searchable: false,},
            {data: 'status', sortable: false, searchable: false,},
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
                   {extend: 'csv', filename: 'delivery-guys-'+ new Date().toISOString().slice(0,10), text: 'Export as CSV'},
               ]
           }
    });

     $('#clearFilterAndState').click(function(event) {
        if (datatable) {
            datatable.state.clear();
            window.location.reload();
        }
     });
</script>



@endsection