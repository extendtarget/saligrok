@extends('admin.layouts.master')
@section("title") Edit User - Wazone
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4><i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Editing</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $user->name }}</span>
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
            <div class="card-body" style="min-height: 60vh;">
                <form action="{{ route('Wazone.updateUser') }}" method="POST" enctype="multipart/form-data" id="storeMainForm" style="min-height: 60vh;">
                    @csrf
                    <input type="hidden" name="window_redirect_hash" value="">
                    <input type="hidden" name="id" value="{{ $user->id }}">

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg btnUpdateUser">
                        <b><i class="icon-database-insert ml-1"></i></b>
                        Update User
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

                            <div class="tab-pane fade show active" id="userDetails">
                                <legend class="font-weight-semibold text-uppercase font-size-sm">
                                    User Details
                                </legend>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Name:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="name"
                                            value="{{ $user->name }}" placeholder="Enter Full Name" required
                                            autocomplete="new-name">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Phone:</label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control form-control-lg" name="phone" value="{{ $user->phone }}" 
                                            placeholder="Enter Phone Number" required autocomplete="new-phone">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label">Notification Status:</label>
                                    <div class="col-lg-9">
                                    <a href="{{ route('Wazone.saveUserNotifiable', $user->id) }}"
                                        class="@if($user->is_notifiable) bbtn btn-lg btn-success ml-1 @else bbtn btn-lg btn-danger ml-1 @endif" data-popup="tooltip"
                                        title="@if($user->is_notifiable) Notification is ON @else Notification is OFF @endif" data-placement="bottom"> 
                                        <i class="icon-comment"></i> @if($user->is_notifiable) Notification is ON @else Notification is OFF @endif </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="text-right mt-5">
                            <button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg btnUpdateUser">
                            <b><i class="icon-database-insert ml-1"></i></b>
                            Update User
                            </button>
                        </div>
                </form>                
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {

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

        $("#showPassword").click(function (e) { 
            $("#passwordInput").attr("type", "text");
        });
        $('.select').select2({
            minimumResultsForSearch: Infinity,
            placeholder: 'Select Role (Old role will be revoked and the new role will be applied)',
        });
        $('.balance').numeric({allowThouSep:false, maxDecimalPlaces: 2 });

        $("#addAmountButton").click(function(event) {
            $('#addAmountButton').hide();
            $('#substractAmountButton').hide();
            $("#addAmountForm").removeClass('hidden');
            $("#substractAmountForm").addClass('hidden');
        });

        $("#substractAmountButton").click(function(event) {
            $('#addAmountButton').hide();
            $('#substractAmountButton').hide();
            $("#addAmountForm").addClass('hidden');
            $("#substractAmountForm").removeClass('hidden');
        });

        $("#viewTransactions").click(function(event) {
            var targetOffset = $('#tansactionsDiv').offset().top - 70;
            $('html, body').animate({scrollTop: targetOffset}, 500);
        });

        $('.commission_rate').numeric({ allowThouSep:false, maxDecimalPlaces: 2, max: 100, allowMinus: false });
        $('.max_orders').numeric({ allowThouSep:false, maxDecimalPlaces: 0, max: 99999, allowMinus: false });

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

        $('#walletBalance').click(function(event) {
            var targetOffset = $('#walletBalanceBlock').offset().top - 70;
            $('html, body').animate({scrollTop: targetOffset}, 500);
        });

        $('.btnUpdateUser').click(function () {
            $('input:invalid').each(function () {
                // Find the tab-pane that this element is inside, and get the id
                var $closest = $(this).closest('.tab-pane');
                var id = $closest.attr('id');

                // Find the link that corresponds to the pane and have it show
                $('ul.nav a[href="#' + id + '"]').tab('show');

                // var hash = '#'+id;
                // window.location.hash = hash;
                // console.log("hash: ", hash)
                // $("[name='window_redirect_hash']").val(hash);

                return false;
            });
        });
    });
</script>
@endsection