@extends('admin.layouts.master')
@section("title") Coupons - Dashboard
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Total</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $couponTotal }} Coupons</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
        <div class="header-elements d-none py-0 mb-3 mb-md-0">
            <div class="breadcrumb">
                <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left" id="addNewCoupon"
                    data-toggle="modal" data-target="#addNewCouponModal">
                    <b><i class="icon-plus2"></i></b>
                    Add New Coupon
                </button>
            </div>
        </div>
    </div>
</div>
<div class="content">
    <div class="card">
        <div class="card-body">
            <!-- START qusay -->
            <form class="form-inline" action="{{ route('admin.coupons') }}">
                <input type="hidden" name="action_search" value="true" />
                <div class="form-group">
                    <label for="code">Search By Code: </label>
                    <input type="text" class="form-control" id="code" name="search_by_code" value="{{ isset($_GET['search_by_code']) ? $_GET['search_by_code'] : null }}">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <!-- END qusay -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Coupon Applicable Store</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Discount</th>
                            <th>Delivery Discount %</th>
                            <th>Status</th>
                            <th>Show in Store</th>
                            <th>Show in Home</th>
                            <th>Show in Cart</th>
                            <th>Usage</th>
                            <th style="min-width: 150px;">Expiry Date</th>
                            <th>Min Subtotal</th>
                            <th>Max Discount</th>
                            <th>Free Delivery</th>
                            <th class="text-center" style="width: 10%;"><i class="icon-circle-down2"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                        <tr>
                            <td>{{ $coupon->name }}</td>
                            <td>
                                @if(count($coupon->restaurants) > 1)
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">MULTIPLE STORES</span>
                                @else
                                @foreach($coupon->restaurants as $couponRestaurant)
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $couponRestaurant->name }}</span>
                                @endforeach
                                @endif
                            </td>
                            <td><b>{{ $coupon->code }}</b></td>
                            <td>
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                                  {{ $coupon->discount_type == 'FREE' ? 'Free Delivery' : $coupon->discount_type }}
                                </span>
                            </td>
                              <td>
                                    @if($coupon->discount_type == 'AMOUNT')
                                    {{ config('setting.currencyFormat') }} {{ $coupon->discount }}
                                    @elseif($coupon->discount_type == 'PERCENTAGE')
                                    {{ $coupon->discount }} <strong>%</strong>
                                    @elseif($coupon->discount_type == 'FREE')
                                    0
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>{{ $coupon->delivery_discount_percentage ?? '' }}%</td>
                            <td>@if($coupon->is_active)
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">Active</span>
                                @else
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">Inactive</span>
                                @endif
                            </td>
                            <td>@if($coupon->show_in_restaurant)
                                <span class="badge badge-success text-white border-grey-800 text-default text-capitalize">Yes</span>
                                @else
                                <span class="badge badge-danger text-white border-grey-800 text-default text-capitalize">No</span>
                                @endif
                            </td>
                            <td>@if($coupon->show_in_home)
                                <span class="badge badge-success text-white border-grey-800 text-default text-capitalize">Yes</span>
                                @else
                                <span class="badge badge-danger text-white border-grey-800 text-default text-capitalize">No</span>
                                @endif
                            </td>
                            <td>@if($coupon->show_in_cart)
                                <span class="badge badge-success text-white border-grey-800 text-default text-capitalize">Yes</span>
                                @else
                                <span class="badge badge-danger text-white border-grey-800 text-default text-capitalize">No</span>
                                @endif
                            </td>
                            <td><span class="badge badge-flat border-grey-800 text-default text-capitalize">{{ $coupon->count }}</span></td>
                            <td class="small">
                                @if(\Carbon\Carbon::now() > $coupon->expiry_date)
                                <p class="mb-0 font-weight-bold text-danger blink-soft">EXPIRED</p>
                                @endif
                                {{ $coupon->expiry_date->diffForHumans() }} <br>
                                ({{ $coupon->expiry_date->format('Y-m-d') }})
                            </td>
                            <td>{{ $coupon->min_subtotal }}</td>
                            <td>@if($coupon->max_discount) {{ $coupon->max_discount }} @else <span class="badge badge-flat border-grey-800 text-default text-capitalize">NA</span>@endif</td>
                            <td>
                                @if($coupon->is_used_for_delivery)
                                    <span class="badge badge-success text-white">Yes</span>
                                @else
                                    <span class="badge badge-danger text-white">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-justified">
                                    <a href="{{ route('admin.get.getEditCoupon', $coupon->id) }}"
                                        class="btn btn-sm btn-primary"> Edit</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</div>
<div id="addNewCouponModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Add New Coupon</span></h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.post.saveNewCoupon') }}" method="POST">
                    <!-- حقول الخصم -->
                    <div class="discount-fields">
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Discount Type:</label>
                            <div class="col-lg-9">
                                <select class="form-control select-search select" name="discount_type" required>
                                    <option value="AMOUNT" class="text-capitalize">Fixed Amount Discount</option>
                                    <option value="PERCENTAGE" class="text-capitalize">Percentage Discount</option>
                                    <option value="FREE" class="text-capitalize">Free Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row hidden" id="max_discount">
                            <label class="col-lg-3 col-form-label">Max Discount</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg max_discount" name="max_discount"
                                       placeholder="Max discount applicable in {{ config('setting.currencyFormat') }}">
                            </div>
                        </div>
                        <div class="form-group row" id="discount_field">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Discount:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg discount" name="discount"
                                       placeholder="Coupon Discount" required>
                            </div>
                        </div>
                    </div>
                    <!--Free Delivery Coupon by aya -->
                    <div class="form-group row" id="delivery_discount_percentage_field">
                        <label class="col-lg-3 col-form-label">Delivery Discount Percentage:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg delivery_discount_percentage" 
                                   name="delivery_discount_percentage" 
                                   placeholder="Enter delivery discount percentage (0-100)" 
                                   value="100">
                        </div>
                    </div>
                    <!--============-->
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Name:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="name"
                                   placeholder="Coupon Name" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Coupon Description:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="description"
                                   placeholder="Coupon Description">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Code:</label>
                        <div class="col-lg-7">
                            <input type="text" class="form-control form-control-lg" name="code"
                                   placeholder="Coupon Code" required>
                        </div>
                        <div class="col-lg-2">
                            <button class="btn btn-primary" type="button" title="generation" id="generate-code"><i class="icon-spinner11"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Expiry Date:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg daterange-single"
                                       value="{{ $todaysDate }}" name="expiry_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Applicable Stores:</label>
                        <div class="col-lg-9">
                            <select multiple="multiple" class="form-control select-search couponStoreSelect"
                                    name="restaurant_id[]" required id="storeSelect">
                                @foreach ($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" class="text-capitalize">{{ $restaurant->name }}</option>
                                @endforeach
                            </select>
                            <input type="checkbox" id="selectAllStores"><span class="ml-1">Select All Stores</span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Max number of use in total</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg max_count" name="max_count"
                                   placeholder="Max number of use" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Min Subtotal</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg min_subtotal" name="min_subtotal"
                                   placeholder="Min subtotal required for coupon in {{ config('setting.currencyFormat') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Subtotal not reached message</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="subtotal_message"
                                   placeholder="Subtotal not reached message">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon User Type</label>
                        <div class="col-lg-9">
                            <select class="form-control select-search select" name="user_type" required>
                                <option value="ALL" class="text-capitalize">Unlimited times for all users</option>
                                <option value="ONCENEW" class="text-capitalize">Once for new user for first order</option>
                                <option value="ONCE" class="text-capitalize">Once per user</option>
                                <option value="CUSTOM" class="text-capitalize">Define custom limit per user</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row hidden" id="maxUsePerUser">
                        <label class="col-lg-3 col-form-label">Max number of use per user:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg max_count_per_user"
                                   name="max_count_per_user" placeholder="Max number of use per user">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Is Active?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary isactive" checked="checked"
                                       name="is_active">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Restaurant?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showinrestaurant"
                                       name="show_in_restaurant">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Home?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showinhome"
                                       name="show_in_home">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Cart?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showincart"
                                       name="show_in_cart">
                            </div>
                        </div>
                    </div>
                    @csrf
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            SAVE
                            <i class="icon-database-insert ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $('.select').select2();
    $('.couponStoreSelect').select2({
        closeOnSelect: false
    });

    var isactive = document.querySelector('.isactive');
    new Switchery(isactive, { color: '#2196f3' });
    var showinrestaurant = document.querySelector('.showinrestaurant');
    new Switchery(showinrestaurant, { color: '#2196f3' });
    var showinhome = document.querySelector('.showinhome');
    new Switchery(showinhome, { color: '#2196f3' });
    var showincart = document.querySelector('.showincart');
    new Switchery(showincart, { color: '#2196f3' });

    $('.form-control-uniform').uniform();

    $('.daterange-single').daterangepicker({ 
        singleDatePicker: true,
    });

    // التعامل مع حقل discount_type
    $('[name="discount_type"]').on('change', function () {
        var discountType = $(this).val();
        if (discountType === "PERCENTAGE") {
            $('#max_discount').removeClass('hidden').show();
            $('#discount_field').removeClass('hidden').show();
            $('[name="discount"]').attr('required', 'required');
            $('[name="max_discount"]').removeAttr('required');
        } else if (discountType === "FREE") {
            $('#max_discount').addClass('hidden').hide();
            $('#discount_field').addClass('hidden').hide();
            $('[name="discount"]').removeAttr('required').val(0);
            $('[name="max_discount"]').removeAttr('required').val(null);
        } else { // AMOUNT
            $('#max_discount').addClass('hidden').hide();
            $('#discount_field').removeClass('hidden').show();
            $('[name="discount"]').attr('required', 'required');
            $('[name="max_discount"]').removeAttr('required');
        }
    }).trigger('change'); 

$('[name="discount_type"]').on('change', function () {
    var discountType = $(this).val();
    if (discountType === "PERCENTAGE") {
        $('#max_discount').removeClass('hidden').show();
        $('#discount_field').removeClass('hidden').show();
        $('#delivery_discount_percentage_field').addClass('hidden').hide();
        $('[name="discount"]').attr('required', 'required');
        $('[name="max_discount"]').removeAttr('required');
        $('[name="delivery_discount_percentage"]').removeAttr('required').val(0);
    } else if (discountType === "FREE") {
        $('#max_discount').addClass('hidden').hide();
        $('#discount_field').addClass('hidden').hide();
        $('#delivery_discount_percentage_field').removeClass('hidden').show();
        $('[name="discount"]').removeAttr('required').val(0);
        $('[name="max_discount"]').removeAttr('required').val(null);
        $('[name="delivery_discount_percentage"]').attr('required', 'required').val(100);
    } else {
        $('#max_discount').addClass('hidden').hide();
        $('#discount_field').removeClass('hidden').show();
        $('#delivery_discount_percentage_field').addClass('hidden').hide();
        $('[name="discount"]').attr('required', 'required');
        $('[name="max_discount"]').removeAttr('required');
        $('[name="delivery_discount_percentage"]').removeAttr('required').val(0);
    }
}).trigger('change');

$('.delivery_discount_percentage').numeric({ 
    allowThouSep: false, 
    maxDecimalPlaces: 2, 
    allowMinus: false, 
    max: 100 
});
    $('.min_subtotal').numeric({ allowThouSep: false, maxDecimalPlaces: 2, allowMinus: false });
    $('.max_discount').numeric({ allowThouSep: false, maxDecimalPlaces: 2, allowMinus: false });
    $('.max_count').numeric({ allowThouSep: false, maxDecimalPlaces: 0, allowMinus: false });
    $('.max_count_per_user').numeric({ allowThouSep: false, maxDecimalPlaces: 0, allowMinus: false, max: 99999999 });
    $('.discount').numeric({ allowThouSep: false, maxDecimalPlaces: 2, allowMinus: false });

    // التحقق من وجود رمز الكوبون
    $('[name="code"]').on('change', function () {
        var code = $(this).val();
        if (code != '') {
            $.ajax({
                url: '/public/admin/coupon/check-existing-coupon',
                method: 'GET',
                data: { code: code },
                dataType: 'JSON',
                success: function (response) {
                    if (response.status == true) {
                        $.jGrowl(response.message, {
                            position: 'bottom-center',
                            header: 'Wooopsss ⚠️',
                            theme: 'bg-warning',
                        });
                    }
                },
                error: function (error) {
                    console.log(error);
                    $.jGrowl("Something went wrong! Please try again later.", {
                        position: 'bottom-center',
                        header: 'Wooopsss ⚠️',
                        theme: 'bg-warning',
                    });
                }
            });
        }
    });

    // توليد رمز كوبون عشوائي
    $('#generate-code').on('click', function () {
        var code = '';
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var charsLength = chars.length;
        for (var i = 0; i < 10; i++) {
            code += chars.charAt(Math.floor(Math.random() * charsLength));
        }
        $('[name="code"]').val(code);
    });

    // التحكم في حقل max_count_per_user
    $("[name='user_type']").on('change', function () {
        let selectedUserType = $(this).val();
        if (selectedUserType == "CUSTOM") {
            $("[name='max_count_per_user']").attr('required', 'required');
            $('#maxUsePerUser').removeClass('hidden').show();
        } else {
            $("[name='max_count_per_user']").removeAttr('required');
            $('#maxUsePerUser').addClass('hidden').hide();
        }
    });

    // التحكم في اختيار جميع المتاجر
    $("#selectAllStores").on('click', function () {
        if ($(this).is(':checked')) {
            $("#storeSelect > option").prop("selected", "selected");
            $("#storeSelect").trigger("change");
        } else {
            $("#storeSelect > option").removeAttr("selected");
            $("#storeSelect").trigger("change");
        }
    });
});
</script>
@endsection