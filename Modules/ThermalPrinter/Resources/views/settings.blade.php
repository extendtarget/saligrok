@extends('admin.layouts.master')
@section("title") Settings - Thermal Printer
@endsection
@section('content')

<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Modules</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Thermal Printer</span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
</div>

<div class="content">
    <div class="card">
        <div class="card-body">
        	<form action="{{ route('thermalprinter.saveSettings') }}" method="POST" enctype="multipart/form-data" class="my-3">
        		<legend class="font-weight-semibold text-uppercase font-size-sm">
                    <i class="icon-printer2 mr-1"></i> {{ __('thermalPrinterLang.printerConnectionSettingsTitle') }}
                </legend>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.connectorTypeLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	            <select name="connector_type" class="form-control form-control-lg select">
        	            	<option value="windows" @if(!empty($data->connector_type)) @if($data->connector_type == "windows") selected="selected" @endif @endif>Windows</option>
        	            	<option value="cups" @if(!empty($data->connector_type)) @if($data->connector_type == "cups") selected="selected" @endif @endif>Linux or MacOS</option>
        	            	<option value="network" @if(!empty($data->connector_type)) @if($data->connector_type == "network") selected="selected" @endif @endif>Network</option>
        	            </select>
        	        </div>
        	    </div>
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.connectorDescriptorLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="connector_descriptor"
                            required value="@if(!empty($data->connector_descriptor)){{ $data->connector_descriptor }}@endif">
                            @if(\Lang::get('thermalPrinterLang.connectorDescriptorHelpMessage') != "NULL")
                            <span class="text-muted">
                            	{{ (__('thermalPrinterLang.connectorDescriptorHelpMessage')) }}
                            </span>
                            @else
                            <span class="text-muted">Enter printer name if your Connector Type is <b>Windows/Linux/MacOS</b></span>
                            <br>
                            <span class="text-muted">Enter the IP address or Samba URI, <b>e.g: smb://192.168.1.12/PrinterName</b> if your Connector Type is <b>Network</b></span><br>
                            <span>
			        	    	<strong class="text-danger">Note: This connection settings is only for Admin. Every Store Owner have their own printer connection settings.</strong>
			        	    </span>
			        	    @endif
        	        </div>
        	    </div>
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.printerWidthLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	            <select name="print_width" class="form-control form-control-lg select">
        	            	<option value="3" @if(!empty($data->print_width)) @if($data->print_width == "3") selected="selected" @endif @endif>3 Inch (80mm)</option>
        	            	<option value="2" @if(!empty($data->print_width)) @if($data->print_width == "2") selected="selected" @endif @endif>2 Inch (58mm)</option>
        	            </select>
        	        </div>
        	    </div>
        	    
        	    <legend class="font-weight-semibold text-uppercase font-size-sm mt-5">
                    <i class="icon-printer4 mr-1"></i> {{ __('thermalPrinterLang.invoiceSettingsTitle') }}
                </legend>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.printKotLabel') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->print_kot)) @if($data->print_kot == "true") checked="checked" @endif @endif
                            name="print_kot">
                            </label>
                        </div>
                    </div>
                </div>

                @if(Auth::user()->hasRole('Admin'))
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.adminInvoiceTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="invoice_title" value="@if(!empty($data->invoice_title)){{ $data->invoice_title }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.adminInvoiceSubTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="invoice_subtitle" value="@if(!empty($data->invoice_subtitle)){{ $data->invoice_subtitle }}@endif">
        	        </div>
        	    </div>
        	    @endif

        	    @if(Auth::user()->hasRole('Store Owner'))
                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.automaticPrintingLabel') }}: <i class="icon-question3 ml-1" data-popup="tooltip" title="{{ __('thermalPrinterLang.automaticPrintingInfo') }}" data-placement="top"></i></strong></label>
                    <div class="col-lg-9">
                        <select name="automatic_printing" class="form-control form-control-lg select">
                        	<option value="OFF" @if(!empty($data->automatic_printing)) @if($data->automatic_printing == "OFF") selected="selected" @endif @endif>{{ __('thermalPrinterLang.automaticPrintingDisabled') }}</option>
                        	<option value="ONLYINVOICE" @if(!empty($data->automatic_printing)) @if($data->automatic_printing == "ONLYINVOICE") selected="selected" @endif @endif>{{ __('thermalPrinterLang.automaticPrintingOnlyInvoice') }}</option>
                            <option value="BOTH" @if(!empty($data->automatic_printing)) @if($data->automatic_printing == "BOTH") selected="selected" @endif @endif>{{ __('thermalPrinterLang.automaticPrintingBothInvoiceAndKot') }}</option>
                        </select>
                    </div>
                </div>
                @endif


        	    <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showStoreNameLabel') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_store_name)) @if($data->show_store_name == "true") checked="checked" @endif @endif
                            name="show_store_name">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showStoreAddressLabel') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_store_address)) @if($data->show_store_address == "true") checked="checked" @endif @endif
                            name="show_store_address">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showOrderIdLabel') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_order_id)) @if($data->show_order_id == "true") checked="checked" @endif @endif
                            name="show_order_id">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showDateLabel') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_order_date)) @if($data->show_order_date == "true") checked="checked" @endif @endif
                            name="show_order_date">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.orderIdLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="order_id_label" value="@if(!empty($data->order_id_label)){{ $data->order_id_label }}@endif">
        	        </div>
        	    </div>
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.orderDateLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="order_date_label" value="@if(!empty($data->order_date_label)){{ $data->order_date_label }}@endif">
        	        </div>
        	    </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.customerDetailsTitle') }}: </strong></label>
                    <div class="col-lg-9">
                         <input type="text" class="form-control form-control-lg" name="customer_details_title" value="@if(!empty($data->customer_details_title)){{ $data->customer_details_title }}@endif">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showCustomerName') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_customer_name)) @if($data->show_customer_name == "true") checked="checked" @endif @endif
                            name="show_customer_name">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showCustomerPhone') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_customer_phone)) @if($data->show_customer_phone == "true") checked="checked" @endif @endif
                            name="show_customer_phone">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showDeliveryType') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_delivery_type)) @if($data->show_delivery_type == "true") checked="checked" @endif @endif
                            name="show_delivery_type">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.deliveryLabel') }}: </strong></label>
                    <div class="col-lg-9">
                         <input type="text" class="form-control form-control-lg" name="delivery_label" value="@if(!empty($data->delivery_label)){{ $data->delivery_label }}@endif">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.selfPickupLabel') }}: </strong></label>
                    <div class="col-lg-9">
                         <input type="text" class="form-control form-control-lg" name="selfpickup_label" value="@if(!empty($data->selfpickup_label)){{ $data->selfpickup_label }}@endif">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.showDeliveryAddress') }}: 
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_delivery_address)) @if($data->show_delivery_address == "true") checked="checked" @endif @endif
                            name="show_delivery_address">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.quantityLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="quantity_label" value="@if(!empty($data->quantity_label)){{ $data->quantity_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.itemLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="item_label" value="@if(!empty($data->item_label)){{ $data->item_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.priceLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="price_label" value="@if(!empty($data->price_label)){{ $data->price_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.totalLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="total_label" value="@if(!empty($data->total_label)){{ $data->total_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.storeChargeLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="store_charge_label" value="@if(!empty($data->store_charge_label)){{ $data->store_charge_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.deliveryChargeLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="delivery_charge_label" value="@if(!empty($data->delivery_charge_label)){{ $data->delivery_charge_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.taxLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="tax_label" value="@if(!empty($data->tax_label)){{ $data->tax_label }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.couponLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="coupon_label" value="@if(!empty($data->coupon_label)){{ $data->coupon_label }}@endif">
        	        </div>
        	    </div>

        	    @if(Auth::user()->hasRole('Admin'))
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.adminFooterTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="footer_title" value="@if(!empty($data->footer_title)){{ $data->footer_title }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.adminFooterSubTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	              <textarea class="form-control form-control-lg" name="footer_sub_title"rows="5"
            				placeholder="Hit Enter to break into multiple lines">@if(!empty($data->footer_sub_title)){{ $data->footer_sub_title }}@endif</textarea>
        	        </div>
        	    </div>

        	    
        	    <div class="form-group row">
                    <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.adminAllowCustomFooterLabel') }}:
                    </strong></label>
                    <div class="col-lg-9">
                        <div class="checkbox checkbox-switchery mt-2">
                            <label>
                            <input value="true" type="checkbox" class="switchery-primary"
                            @if(!empty($data->show_custom_store_footer)) @if($data->show_custom_store_footer == "true") checked="checked" @endif @endif
                            name="show_custom_store_footer">
                            </label>
                        </div>
                    </div>
                </div>
                @endif


                @if(Auth::user()->hasRole('Store Owner'))
                @if(!empty($adminData->show_custom_store_footer))
        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.customFooterTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	             <input type="text" class="form-control form-control-lg" name="store_footer_title" value="@if(!empty($data->store_footer_title)){{ $data->store_footer_title }}@endif">
        	        </div>
        	    </div>

        	    <div class="form-group row">
        	        <label class="col-lg-3 col-form-label"><strong>{{ __('thermalPrinterLang.customFooterSubTitleLabel') }}: </strong></label>
        	        <div class="col-lg-9">
        	              <textarea class="form-control form-control-lg" name="store_footer_subtitle"rows="5"
            				placeholder="{{ __('thermalPrinterLang.customFooterSubTitlePlaceholder') }}">@if(!empty($data->store_footer_subtitle)){{ $data->store_footer_subtitle }}@endif</textarea>
        	        </div>
        	    </div>
        	    @endif

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
	})
</script>
@endsection
