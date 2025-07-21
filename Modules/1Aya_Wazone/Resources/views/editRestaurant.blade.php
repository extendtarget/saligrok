@extends('admin.layouts.master')
@section("title") Edit Store - Wazone
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Editing</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $restaurant->name }}</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
        <?php
            $wazone = new \Modules\Wazone\Http\Controllers\WazoneController;
            if ($wazone->validate() == false) { echo '<span style="color:#f44242;text-align:left;">NON-ACTIVE</span>'; }
        ?>
    </div>
</div>

<div class="content">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body" style="min-height: 75vh;">
                <form action="{{ route('Wazone.updateRestaurant') }}" method="POST" enctype="multipart/form-data" id="storeMainForm" style="min-height: 75vh;">
                    @csrf
                    <input type="hidden" name="window_redirect_hash" value="">
                    <input type="hidden" name="id" value="{{ $restaurant->id }}">

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg btnUpdateStore">
                        <b><i class="icon-database-insert ml-1"></i></b>
                        Update Store
                        </button>
                    </div>

                    <div class="d-lg-flex justify-content-lg-left">
                        <ul class="nav nav-pills flex-column mr-lg-3 wmin-lg-250 mb-lg-0">
                            <li class="nav-item">
                                <a href="{{ route('Wazone.settings') }}" class="nav-link active">
                                <i class="icon-store2 mr-2"></i>
                                Back to wazone
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" style="width: 100%; padding: 0 25px;">
                            <div class="tab-pane fade show active" id="generalSettings">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    General Settings
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label"><span class="text-danger">*</span>Store Name:</label>
                                    <div class="col-lg-9">
                                        <input value="{{ $restaurant->name }}" type="text" class="form-control form-control-lg" name="name"
                                            placeholder="Store Name" required>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="phone" value="{{ $restaurant->phone }}" 
                                            placeholder="Enter Phone Number" required autocomplete="new-phone">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Status:</label>
                                    <div class="col-lg-9">
                                    <a href="{{ route('Wazone.saveRestaurantNotifiable', $restaurant->id) }}"
                                        class="@if($restaurant->is_notifiable) bbtn btn-lg btn-success ml-1 @else bbtn btn-lg btn-danger ml-1 @endif" data-popup="tooltip"
                                        title="@if($restaurant->is_notifiable) Notification is ON @else Notification is OFF @endif" data-placement="bottom"> 
                                        <i class="icon-comment"></i> @if($restaurant->is_notifiable) Notification is ON @else Notification is OFF @endif </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-right mt-5">
                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg btnUpdateStore">
                        <b><i class="icon-database-insert ml-1"></i></b>
                        Update Store
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
</div>

<script>

    function readURL(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('.slider-preview-image')
                    .removeClass('hidden')
                    .attr('src', e.target.result)
                    .width(160)
                   .height(117)
                   .css('borderRadius', '0.275rem');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function add(data) {
        var para = document.createElement("div");
        let day = data.getAttribute("data-day")
        para.innerHTML ="<div class='form-group row'> <div class='col-lg-5'><label class='col-form-label'>Opening Time</label><input type='text' class='form-control clock form-control-lg' name='"+day+"[]' required> </div> <div class='col-lg-5'> <label class='col-form-label'>Closing Time</label><input type='text' class='form-control clock form-control-lg' name='"+day+"[]'  required> </div> <div class='col-lg-2'> <label class='col-form-label text-center' style='width: 43px'></span><i class='icon-circle-down2'></i></label><br><button class='remove btn btn-danger' data-popup='tooltip' data-placement='right' title='Remove Time Slot'><i class='icon-cross2'></i></button></div></div>";
        document.getElementById(day).appendChild(para);
        $('.clock').bootstrapMaterialDatePicker({
            shortTime: true,
            date: false,
            time: true,
            format: 'HH:mm'
        });
    }
    
    $(function () {
        
        $('input[name=store_url]').keyup(function(event) {
            let slug = $(this).val();
            slug = slug.toLowerCase();
            slug = slug.replace(/[^a-zA-Z0-9]+/g,'-');
            $(this).val(slug);
            $('#storeURL').html(slug);
        });

        $('body').tooltip({
            selector: 'button'
        });
    
        $('.clock').bootstrapMaterialDatePicker({
            shortTime: true,
            date: false,
            time: true,
            format: 'HH:mm'
        });
        $(document).on("click", ".remove", function() {
            $(this).tooltip('hide')
            $(this).parent().parent().remove();
        });
    
        $('.select').select2({
            minimumResultsForSearch: Infinity,
        });
        
        $('.selectRestaurantCategory').select2({
            closeOnSelect: false
        })
    
      if (Array.prototype.forEach) {
               var elems = Array.prototype.slice.call(document.querySelectorAll('.switchery-primary'));
               elems.forEach(function(html) {
                   var switchery = new Switchery(html, { color: '#2196F3' });
               });
           }
           else {
               var elems = document.querySelectorAll('.switchery-primary');
               for (var i = 0; i < elems.length; i++) {
                   var switchery = new Switchery(elems[i], { color: '#2196F3' });
               }
           }
    
       $('.form-control-uniform').uniform();
    
       $('.rating').numeric({allowThouSep:false,  min: 1, max: 5, maxDecimalPlaces: 1 });
       $('.delivery_time').numeric({allowThouSep:false});
       $('.price_range').numeric({allowThouSep:false});
       $('.latitude').numeric({allowThouSep:false});
       $('.longitude').numeric({allowThouSep:false});
       $('.restaurant_charges').numeric({ allowThouSep:false, maxDecimalPlaces: 2 });
       $('.delivery_charges').numeric({ allowThouSep:false, maxDecimalPlaces: 2 });
       $('.commission_rate').numeric({ allowThouSep:false, maxDecimalPlaces: 2, max: 100 });
    
       $('.base_delivery_charge').numeric({ allowThouSep:false, maxDecimalPlaces: 2, allowMinus: false });
        $('.base_delivery_distance').numeric({ allowThouSep:false, maxDecimalPlaces: 0, allowMinus: false });
        $('.extra_delivery_charge').numeric({ allowThouSep:false, maxDecimalPlaces: 2, allowMinus: false });
        $('.extra_delivery_distance').numeric({ allowThouSep:false, maxDecimalPlaces: 0, allowMinus: false });
        
        $('.min_order_price').numeric({ allowThouSep:false, maxDecimalPlaces: 2, allowMinus: false });
        
    
        @if($restaurant->delivery_charge_type == "FIXED")
            $('#dynamicChargeDiv').addClass('hidden');
        @else
            $('#deliveryCharge').addClass('hidden');
        @endif
       
        $("[name='delivery_charge_type']").change(function(event) {
             if ($(this).val() == "FIXED") {
                 $('#dynamicChargeDiv').addClass('hidden');
                 $('#deliveryCharge').removeClass('hidden')
             } else {
                 $('#deliveryCharge').addClass('hidden');
                 $('#dynamicChargeDiv').removeClass('hidden')
             }
         });

        $('#schedulingSettings').click(function(event) {
            var targetOffset = $('#autoSchedulingBlock').offset().top - 70;
            $('html, body').animate({scrollTop: targetOffset}, 500);
        });

        $('#payoutDetails').click(function(event) {
            var targetOffset = $('#payoutDetailsBlock').offset().top - 70;
            $('html, body').animate({scrollTop: targetOffset}, 500);
        });
   

        $('.summernote-editor').summernote({
           height: 200,
           popover: {
               image: [],
               link: [],
               air: []
             }
        });

        /* Navigate with hash */
        var hash = window.location.hash;
        $("[name='window_redirect_hash']").val(hash);
        hash && $('ul.nav a[href="' + hash + '"]').tab('show');
        $('.nav-pills a').click(function (e) {
            $(this).tab('show');
            var scrollmem = $('body').scrollTop();
            window.location.hash = this.hash;
            $("[name='window_redirect_hash']").val(this.hash);
            $('html, body').scrollTop(scrollmem);
        });

        $('.btnUpdateStore').click(function () {
            $('input:invalid').each(function () {
                // Find the tab-pane that this element is inside, and get the id
                var $closest = $(this).closest('.tab-pane');
                var id = $closest.attr('id');

                // Find the link that corresponds to the pane and have it show
                $('ul.nav a[href="#' + id + '"]').tab('show');

                var hash = '#'+id;
                window.location.hash = hash;
                $("[name='window_redirect_hash']").val(hash);

                return false;
            });
        });

     });
</script>
@endsection