@extends('admin.layouts.master')
@section("title") Zones Management
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <span class="font-weight-bold mr-2">Editing</span>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">{{ $zone->name }}</span>
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
    <div class="col">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.updateZone') }}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="{{ $zone->id }}">
                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label">Name:</label>
                        <div class="col-lg-10">
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="Zone name"
                                required value="{{ $zone->name }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label">Description:</label>
                        <div class="col-lg-10">
                            <input type="text" name="description" class="form-control form-control-lg"
                                placeholder="Zone description" required value="{{ $zone->description }}">
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <legend class="font-weight-semibold text-uppercase font-size-sm">
                                Delivery Surcharge Settings
                            </legend>
                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label">Enable Delivery Surcharge</label>
                                <div class="col-lg-9 d-flex align-items-center">
                                    <div class="checkbox checkbox-switchery">
                                        <input value="true" type="checkbox" class="switchery-primary enableDeliverySurcharge"
                                            @if($zone->delivery_surcharge_active) checked="checked" @endif name="delivery_surcharge_active">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">Zone Delivery Surcharge Type:</label>
                                <div class="col-lg-10">
                                    <select name="delivery_surcharge_type" class="form-control form-control-lg"
                                        placeholder="Zone Delivery Surcharge Status" required>
                                        <option value="fixed" @if ($zone->delivery_surcharge_type == "fixed") selected @endif>Fixed</option>
                                        <option value="percentage" @if ($zone->delivery_surcharge_type == "percentage") selected @endif>Percentage</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">Zone Delivery Surcharge Rate:</label>
                                <div class="col-lg-10">
                                    <input type="text" name="delivery_surcharge_rate" class="form-control form-control-lg"
                                        placeholder="Zone Delivery Surcharge Rate in {{ ucfirst($zone->delivery_surcharge_type) }}" required value="{{ $zone->delivery_surcharge_rate }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">Zone Delivery Surcharge Reason:</label>
                                <div class="col-lg-10">
                                    <input type="text" name="delivery_surcharge_reason" class="form-control form-control-lg"
                                        placeholder="Zone Delivery Surcharge Reason" required value="{{ $zone->delivery_surcharge_reason }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            Update Zone
                        </button>
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    var showinrestaurant = document.querySelector('.enableDeliverySurcharge');
    new Switchery(showinrestaurant, { color: '#2196f3' });

</script>
@endsection