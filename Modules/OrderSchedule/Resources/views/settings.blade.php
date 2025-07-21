@extends('admin.layouts.master')
@section("title") Settings - Order Schedule
@endsection
@section('content')

<div class="page-header">
	<div class="page-header-content header-elements-md-inline">
		<div class="page-title d-flex">
			<h4>
				<span class="font-weight-bold mr-2">Modules</span>
				<i class="icon-circle-right2 mr-2"></i>
				<span class="font-weight-bold mr-2">Order Schedule</span>
			</h4>
			<a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
		</div>
	</div>
</div>

<div class="content">
	<div class="card">
		<div class="card-body">
			<form action="{{ route('orderschedule.saveSettings') }}" method="POST" enctype="multipart/form-data">

				<div class="d-flex justify-content-between align-items-center">
					<div>
						<h3 class="font-weight-semibold text-uppercase font-size-sm mb-0">
							<i class="icon-calendar2 mr-1"></i> Order Schedule Settings
						</h3>
					</div>
					<div>
						<div class="float-right">
							<a href="https://docs.foodomaa.com/premium-modules/order-schedule-module" target="_blank"
								class="btn btn-warning btn-md">
								<i class="icon-file-text2 mr-1"></i> Read Documentation
							</a>
						</div>
					</div>
				</div>
				<hr>

				<div class="form-group row">
					<label class="col-lg-3 col-form-label"><strong>Order Scheduling
						</strong></label>
					<div class="col-lg-9">
						<div class="checkbox checkbox-switchery mt-2">
							<label>
								<input value="true" type="checkbox" class="switchery-primary"
									@if(config('setting.enableOrderSchedulingOnCustomer')=="true" ) checked="checked"
									@endif name="enableOrderSchedulingOnCustomer">
							</label>
							<br>
							<small><mark>This needs to be enabled to enable order scheduling globally.</mark></small>
						</div>
					</div>
				</div>

				<hr>

				<div class="form-group row">
					<label class="col-lg-3 col-form-label"><strong>Minutes before order processed</strong></label>
					<div class="col-lg-9">
						<input type="text" class="form-control form-control-lg minsBeforeScheduleOrderProcessed"
							name="minsBeforeScheduleOrderProcessed"
							value="{{ config('setting.minsBeforeScheduleOrderProcessed') }}" required="required">
						<p class="mt-1"><small><mark>Set the minutes before which the scheduled order will be processed
									automatically.</mark></small></p>
						<p>
							<b>Example</b><br>
							If the Order was scheduled for <b>1:00pm - 1:30pm</b>, and if you set the value of the above
							field to <b>30</b> minutes, <br>
							<b>Then: </b><br>
							<ul>
								<li>
									if the scheduled order is already Confirmed by the store, then at <b>12:30pm</b>,
									the order will be Marked as Preparing and will be forwared to all the Delivery Guys
									assigned to that particular store.
								</li>
								<br>
								<li>
									if the scheduled order is NOT confirmed by the store, then at <b>12:30pm</b>, the
									order will be Marked as New Order and will shown to the Store Onwer to Accept the
									order.
								</li>
								<br>
								<li>
									if the scheduled order is NOT confirmed by the store, then at <b>12:30pm</b>, and if
									the Store has Auto Accept Order settings Enabled, the order will be Marked as
									Preparing and will be forwared to all the Delivery Guys assigned to that particular
									store.
								</li>
							</ul>
						</p>
					</div>
				</div>

				<div class="form-group row">
					<label class="col-lg-3 col-form-label"><strong>Custom number of days
						</strong></label>
					<div class="col-lg-9">
						<div class="checkbox checkbox-switchery mt-2">
							<label>
								<input value="true" type="checkbox" class="switchery-primary"
									@if(config('setting.enFixedNumberOfDays')=="true" ) checked="checked" @endif
									name="enFixedNumberOfDays">
							</label>
							<br>
							<small><mark>If disabled, 7 days future dates is taken by default</mark></small>
						</div>
					</div>
				</div>

				@if(config('setting.enFixedNumberOfDays') == "true")
				<div class="form-group row">
					<label class="col-lg-3 col-form-label"><strong>Number of future days: </strong></label>
					<div class="col-lg-9">
						<input type="text" class="form-control form-control-lg orderSchedulingFutureDays"
							name="orderSchedulingFutureDays" value="{{ config('setting.orderSchedulingFutureDays') }}"
							required="required">
						<p class="mt-1"><small><mark>Max 8 days can be set</mark></small></p>
					</div>
				</div>
				@endif

				@csrf
				<div class="text-right mt-5">
					<button type="submit" class="btn btn-primary btn-labeled btn-labeled-left btn-lg">
						<b><i class="icon-database-insert ml-1"></i></b>
						{{ __('thermalPrinterLang.saveSettings') }}
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

		$('.select').select2({
			minimumResultsForSearch: -1,
		});

		$('.orderSchedulingFutureDays').numeric({
			allowThouSep:false, 
			maxDecimalPlaces: 0, 
			allowMinus: false, 
			min: 1, 
			max: 8 
		});
		$('.minsBeforeScheduleOrderProcessed').numeric({
			allowThouSep:false,
			maxDecimalPlaces: 0,
			allowMinus: false,
			min: 1,
			max: 1440
		});
	})
</script>
@endsection