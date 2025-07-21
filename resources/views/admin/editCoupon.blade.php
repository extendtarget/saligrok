@extends('admin.layouts.master')
@section("title") Edit Coupon - Dashboard
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Editing</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $coupon->name }} - {{ $coupon->code }}</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
</div>
<div class="content">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.updateCoupon') }}" method="POST">
                    <legend class="font-weight-semibold text-uppercase font-size-sm">
                        <i class="icon-address-book mr-2"></i> Coupon Details
                    </legend>
                    <input type="hidden" name="id" value="{{ $coupon->id }}">
                    <!-- حقول الخصم -->
                    <div class="discount-fields">
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Discount Type:</label>
                            <div class="col-lg-9">
                                <select class="form-control select-search select" name="discount_type" required>
                                    <option value="AMOUNT" {{ $coupon->discount_type == 'AMOUNT' ? 'selected' : '' }}>Fixed Amount Discount</option>
                                    <option value="PERCENTAGE" {{ $coupon->discount_type == 'PERCENTAGE' ? 'selected' : '' }}>Percentage Discount</option>
                                    <option value="FREE" {{ $coupon->discount_type == 'FREE' ? 'selected' : '' }}>Free Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row {{ $coupon->discount_type == 'PERCENTAGE' ? '' : 'hidden' }}" id="max_discount">
                            <label class="col-lg-3 col-form-label">Max Discount</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg max_discount" name="max_discount"
                                       placeholder="Max discount applicable in {{ config('setting.currencyFormat') }}"
                                       value="{{ $coupon->max_discount ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group row {{ $coupon->discount_type == 'FREE' ? 'hidden' : '' }}" id="discount_field">
                            <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Discount:</label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control form-control-lg discount" name="discount"
                                       placeholder="Coupon Discount" value="{{ $coupon->discount ?? '' }}"
                                       {{ $coupon->discount_type == 'FREE' ? '' : 'required' }}>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row {{ $coupon->discount_type == 'FREE' ? '' : 'hidden' }}" id="delivery_discount_percentage_field">
                        <label class="col-lg-3 col-form-label">Delivery Discount Percentage:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg delivery_discount_percentage" 
                                   name="delivery_discount_percentage" 
                                   placeholder="Enter delivery discount percentage (0-100)" 
                                   value="{{ $coupon->delivery_discount_percentage ?? 100 }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Name:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="name"
                                   placeholder="Coupon Name" value="{{ $coupon->name }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Coupon Description:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="description"
                                   placeholder="Coupon Description" value="{{ $coupon->description }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Code:</label>
                        <div class="col-lg-7">
                            <input type="text" class="form-control form-control-lg" name="code"
                                   placeholder="Coupon Code" value="{{ $coupon->code }}" required>
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
                                       value="{{ $coupon->expiry_date->format('m-d-Y') }}" name="expiry_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon Applicable Stores:</label>
                        <div class="col-lg-9">
                            <select class="form-control select-search couponStoreSelect" name="restaurant_id[]" required
                                    multiple="multiple" id="storeSelect">
                                @foreach ($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}"
                                        {{ in_array($restaurant->id, $couponAssignedRestaurants) ? 'selected' : '' }}>
                                        {{ $restaurant->name }}</option>
                                @endforeach
                            </select>
                            <input type="checkbox" id="selectAllStores"><span class="ml-1">Select All Stores</span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Max number of use in total:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg max_count" name="max_count"
                                   placeholder="Max number of use in total" value="{{ $coupon->max_count }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Min Subtotal</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg min_subtotal" name="min_subtotal"
                                   placeholder="Min subtotal required for coupon in {{ config('setting.currencyFormat') }}"
                                   value="{{ $coupon->min_subtotal }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Subtotal not reached message</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="subtotal_message"
                                   placeholder="Subtotal not reached message" value="{{ $coupon->subtotal_message }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Coupon User Type</label>
                        <div class="col-lg-9">
                            <select class="form-control select-search select" name="user_type" required>
                                <option value="ALL" {{ $coupon->user_type == 'ALL' ? 'selected' : '' }}>Unlimited times for all users</option>
                                <option value="ONCENEW" {{ $coupon->user_type == 'ONCENEW' ? 'selected' : '' }}>Once for new user for first order</option>
                                <option value="ONCE" {{ $coupon->user_type == 'ONCE' ? 'selected' : '' }}>Once per user</option>
                                <option value="CUSTOM" {{ $coupon->user_type == 'CUSTOM' ? 'selected' : '' }}>Define custom limit per user</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row {{ $coupon->user_type == 'CUSTOM' ? '' : 'hidden' }}" id="maxUsePerUser">
                        <label class="col-lg-3 col-form-label">Max number of use per user:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg max_count_per_user"
                                   name="max_count_per_user" placeholder="Max number of use per user"
                                   value="{{ $coupon->max_count_per_user ?? '' }}"
                                   {{ $coupon->user_type == 'CUSTOM' ? 'required' : '' }}>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Is Active?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary isactive"
                                       name="is_active" {{ $coupon->is_active ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Restaurant?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showinrestaurant"
                                       name="show_in_restaurant" {{ $coupon->show_in_restaurant ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Home?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showinhome"
                                       name="show_in_home" {{ $coupon->show_in_home ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Show in Cart?</label>
                        <div class="col-lg-9 d-flex align-items-center">
                            <div class="checkbox checkbox-switchery">
                                <input value="true" type="checkbox" class="switchery-primary showincart"
                                       name="show_in_cart" {{ $coupon->show_in_cart ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    @csrf
                    <div class="text-left">
                        <a class="btn btn-danger text-white" data-toggle="modal" data-target="#deleteCouponConfirmModal"
                           id="deleteCouponButton">
                            DELETE
                            <i class="icon-trash ml-1"></i>
                        </a>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            UPDATE
                            <i class="icon-database-insert ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="deleteCouponConfirmModal" class="modal fade mt-5" tabindex="-1">
    <div class="modal-dialog modal-xs">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Are you sure?</span></h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body">
                <div class="mt-4 d-flex justify-content-center align-items-center">
                    <a href="{{ route('admin.deleteCoupon', $coupon->id) }}" class="btn btn-primary mr-2">Yes</a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
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
                $('#delivery_discount_percentage_field').addClass('hidden').hide();
                $('[name="discount"]').attr('required', 'required');
                $('[name="max_discount"]').removeAttr('required');
            } else if (discountType === "FREE") {
                $('#max_discount').addClass('hidden').hide();
                $('#discount_field').addClass('hidden').hide();
                $('#delivery_discount_percentage_field').removeClass('hidden').show();
                $('[name="discount"]').removeAttr('required').val(0);
                $('[name="max_discount"]').removeAttr('required').val(null);
            } else { // AMOUNT
                $('#max_discount').addClass('hidden').hide();
                $('#discount_field').removeClass('hidden').show();
                $('#delivery_discount_percentage_field').addClass('hidden').hide();
                $('[name="discount"]').attr('required', 'required');
                $('[name="max_discount"]').removeAttr('required');
            }
        }).trigger('change');

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
        }).trigger('change');

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