@extends('admin.layouts.master')
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Modules</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Call and Order Settings</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
        <div class="header-elements d-none py-0 mb-3 mb-md-0">
            <div class="breadcrumb">
            </div>
        </div>
    </div>
</div>
<div class="content">
    <div class="card">
        <div class="card-body">
            
            <form action="{{ route('cao.saveSettings') }}" method="POST" enctype="multipart/form-data">

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-semibold text-uppercase font-size-sm mb-0">
                            <i class="icon-phone2 mr-1"></i> Call And Order Settings
                        </h3>
                    </div>
                    <div>
                        <div class="float-right">
                            <a href="https://docs.foodomaa.com/premium-modules/call-and-order-module" target="_blank" class="btn btn-warning btn-md">
                                <i class="icon-file-text2 mr-1"></i> Read Documentation
                            </a>
                        </div>
                    </div>
                </div>
                <hr>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>Store Owners</strong></label>
                    <div class="col-lg-9">
                        <select class="form-control select storeOwnerSelect" name="user_id[]" multiple="multiple" id="storeOwnerSelect">
                            @foreach ($storeOwners as $storeOwner)
                            <option value="{{ $storeOwner->id }}" class="text-capitalize" {{ isset($storeOwner) && in_array($storeOwner->id, $storeOwnersIdsWithPermission) ? 'selected' : '' }}>{{ $storeOwner->name }}</option>
                            @endforeach
                        </select>
                        <input type="checkbox" id="selectAllStoreOwners"><span class="ml-1">Select All Stores Owners</span>

                        <div class="mt-2">
                            <small>Select Store Owners that will be allowed to take orders on call for only <b>New Users (Guest Checkout)</b></small> <br>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>Allow Store Owner to login to Registered Customers
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(config('setting.allowStoreOwnersPlaceLoginOrders') == "true") checked="checked" @endif
                            name="allowStoreOwnersPlaceLoginOrders">
                            </label>
                            <br>
                            <b class="text-danger">Be very careful if you need to enable this function.</b><br>
                            <small>By default, only the <b>Admin</b> and the staff with the permission of <b>Login as Customer</b> are allowed to access Registered Customer data to Login. If this option is enabled the selected Store Owners will also be able to view and access the registered customer login</small>
                            
                        </div>
                    </div>
                </div>
                
            @csrf
            <div class="text-right">
                <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg">
                <b><i class="icon-database-insert ml-1"></i></b>
                Save Settings
                </button>
            </div>
            </form>

        </div>
    </div>
</div>

<script>
    "use strict";
    $(function() {
        var elems = document.querySelectorAll('.switchery-primary');
        for (var i = 0; i < elems.length; i++) {
            var switchery = new Switchery(elems[i], { color: '#8360c3' });
        }

        $('.storeOwnerSelect').select2({
            closeOnSelect: false
        })

        $("#selectAllStoreOwners").click(function(){
            if($("#selectAllStoreOwners").is(':checked') ){
                $("#storeOwnerSelect > option").prop("selected","selected");
                $("#storeOwnerSelect").trigger("change");
            }else{
                $("#storeOwnerSelect > option").removeAttr("selected");
                 $("#storeOwnerSelect").trigger("change");
             }
        });
    })
</script>
@endsection