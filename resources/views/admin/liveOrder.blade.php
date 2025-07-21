
@extends('admin.layouts.master')
@section('title')
Live Order - Dashboard
@endsection
@section('content')
<style>
    .order a {
        font-weight: bold;
    }
    .order a:hover {
        color: black !important;
        font-size: 16.5px;
        font-weight: bold;
    }
    .order-amount {
        color: #49b875;
        font-size: 1.0rem;
        font-weight: 700;
    }
    .block-bg-5 {
        background-color: rgb(251 156 42 / 21%);
    }
    .color-orange {
        color: #ff7200;
    }
    .no-data-text {
        font-weight: bold;
        font-size: 1.2rem;
        color: #dbdbdb;
    }
    .no-data-img {
        width: 20%;
        opacity: 12%;
    }
    .accept-time {
        color: #ff7200;
        font-weight: bold;
    }
    .wait-time {
        color: #2196f3;
        font-weight: bold;
    }
</style>

<div class="content mb-5" id="liveorders">
    
    <div class="d-flex justify-content-between mt-4 mb-0">
        <div>
            <h3><b>Live Orders</b></h3>
        </div>
        <div class="float-right">
            <span id="current-time-now" data-start="{{ time() }}" class="float-right color-green mx-2" style="font-size: 1.5rem; font-weight: 700;"></span>
        </div>
    </div>

    <div class="clearfix"></div>
    @role('Admin')
    <div class="row">
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $completedCount }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Completed Orders</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-4">
                                <i class="dashboard-display-icon icon-checkmark color-green"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $cancelledCount }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Cancelled Orders</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-3">
                                <i class="dashboard-display-icon icon-cross color-red"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $onGoingOrders }}</div>
                            <div class="font-size-sm text-uppercase text-muted">On Going Orders</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <i class="dashboard-display-icon icon-truck color-purple"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ $onlineDrivers }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Online Drivers</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <i class="dashboard-display-icon icon-truck color-purple"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ number_format($driverOrderRate, 2) }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Order Load Per Driver</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <i class="dashboard-display-icon icon-truck color-purple"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-2 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default" href="javascript:void(0)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">{{ config('setting.currencyFormat') }} {{ number_format($todayOrderRevenue, 2) }}</div>
                            <div class="font-size-sm text-uppercase text-muted">Total Sales Today</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-2">
                                <i class="dashboard-display-icon icon-coin-dollar color-cyan"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endrole
    <hr>
    <div class="row">
        <div class="col-12 col-xl-3 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">New</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <span class="dashboard-display-icon color-purple"><b>{{ count($newOrders) }}</b></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive" style="max-height: 400px;">
                        @if (count($newOrders) == 0)
                            <div class="align-items-center text-center">
                                <i class="icon-exclamation no-data-img"></i>
                                <span class="no-data-text"><br>No data to show</span>
                            </div>
                        @else
                            @foreach ($newOrders as $nO)
                                <div class="order row">
                                    <div class="col-8 col-xl-9">
                                        <a href="{{ route('admin.viewOrder', $nO->unique_order_id) }}" target="_blank">
                                            <span style="font-size: 15px;">#{{ substr($nO->unique_order_id, -7) }}</span>
                                        </a>
                                        <br>
                                        @if ($nO->user)
                                            {{ optional($nO->user)->name ?? 'غير متوفر' }} - {{ optional($nO->user)->phone ?? 'غير متوفر' }}
                                        @else
                                            User not available
                                        @endif
                                        <br>
                                        <b>{{ optional($nO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                        {{ $nO->updated_at->diffForHumans() }}
                                    </div>
                                    <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                        {{ config('setting.currencyFormat') }}{{ $nO->total ?? '0.00' }}<br>
                                        <span class="badge badge-color-5">{{ $nO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                        @if ($nO->delivery_type == 1 && $nO->location)
                                            <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($nO->location)->lat ?? '' }},{{ json_decode($nO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                        @endif
                                    </div>
                                </div>
                                <hr>
                            @endforeach
                        @endif
                    </div>
                </a>
            </div>
        </div>
 <div class="col-12 col-xl-3 mb-2 mt-2">
    <div class="col-xl-12 dashboard-display p-3">
        <a class="block block-link-shadow text-left text-default">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="dashboard-display-number">Preparing</div>
                </div>
                <div class="d-none d-sm-block">
                    <div class="dashboard-display-icon-block block-bg-1">
                        <span class="dashboard-display-icon color-purple"><b>{{ count($preparingOrders) }}</b></span>
                    </div>
                </div>
            </div>
            <hr>
            <div class="table-responsive" style="max-height: 400px;">
                @if (count($preparingOrders) == 0)
                    <div class="align-items-center text-center">
                        <i class="icon-exclamation no-data-img"></i>
                        <span class="no-data-text"><br>No data to show</span>
                    </div>
                @else
                    @foreach ($preparingOrders as $pO)
                        @php
                            $accepted_activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $pO->id)
                                ->where('description', 'Order accepted')
                                ->first();
                        @endphp
                        <div class="order row">
                            <div class="col-8 col-xl-9">
                                <a href="{{ route('admin.viewOrder', $pO->unique_order_id) }}" target="_blank">
                                    <span style="font-size: 15px;">#{{ substr($pO->unique_order_id, -7) }}</span>
                                </a>
                                <br>
                                @if ($pO->user)
                                    {{ optional($pO->user)->name ?? 'Not available' }} - {{ optional($pO->user)->phone ?? 'Not available' }}
                                @else
                                    User not available
                                @endif
                                <br>
                                <b>{{ optional($pO->restaurant)->name ?? 'Not available' }}</b><br>
                                {{ $pO->updated_at->diffForHumans() }}<br>
                                @if ($pO->delivery_type == '1')
                                    @if ($pO->accept_delivery && optional($pO->accept_delivery->user)->name)
                                        Delivery Guy: <b>{{ optional($pO->accept_delivery->user)->name ?? 'Not available' }}</b><br>
                                    @else
                                        Delivery Guy: <b>Not available</b><br>
                                    @endif
                                    @if ($pO->restaurant->show_time_on_order_accept && $accepted_activity && $pO->delay_before_driver_visibility)
                                        @php
                                            $start = new DateTime($accepted_activity->created_at);
                                            $end = new DateTime($pO->delay_before_driver_visibility);
                                            $diff = $start->diff($end);
                                            $minutes = ($diff->h * 60) + $diff->i;
                                            $waitTimeMessage = 'Time';
                                        @endphp
                                        <span class="wait-time">{{ $waitTimeMessage }}: <b>{{ $minutes + 15 }} minutes</b></span>
                                    @else
                                        <span class="wait-time text-danger">
                                            Not available: 
                                            @if (!$pO->restaurant->show_time_on_order_accept)
                                                Show time on order accept feature is disabled
                                            @elseif (!$accepted_activity)
                                                Order acceptance activity not found
                                            @elseif (!$pO->delay_before_driver_visibility)
                                                Wait time is not set
                                            @endif
                                        </span>
                                    @endif
                                @else
                                    Delivery Guy: <b>Not available</b><br>
                                @endif
                                </br>
                                Ready by: <b>{{ $pO->prep_time ? Carbon\Carbon::parse($pO->prep_time)->format('h:i A') : 'Not defined' }}</b>
                            </div>
                            <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                {{ config('setting.currencyFormat') }}{{ $pO->total ?? '0.00' }}<br>
                                <span class="badge badge-color-5">{{ $pO->payment_mode ?? 'Not specified' }}</span><br><br>
                                @if ($pO->delivery_type == 1 && $pO->location)
                                    <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($pO->location)->lat ?? '' }},{{ json_decode($pO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                @endif
                            </div>
                        </div>
                        <hr>
                    @endforeach
                @endif
            </div>
        </a>
    </div>
</div>
        <div class="col-12 col-xl-3 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">Delivery Assigned</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <span class="dashboard-display-icon color-purple"><b>{{ count($deliveryAssignedOrders) }}</b></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive" style="max-height: 400px;">
                        @if (count($deliveryAssignedOrders) == 0)
                            <div class="align-items-center text-center">
                                <i class="icon-exclamation no-data-img"></i>
                                <span class="no-data-text"><br>No data to show</span>
                            </div>
                        @else
                            @foreach ($deliveryAssignedOrders as $dAO)
                                @php
                                    $order = \App\Order::where('unique_order_id', $dAO->unique_order_id)->first();
                                    $accepted_activity = null;
                                    if ($order) {
                                        $accepted_activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $order->id)
                                            ->where('description', 'Order accepted')
                                            ->first();
                                    }
                                @endphp
                                <div class="order row">
                                    <div class="col-8 col-xl-9">
                                        <a href="{{ route('admin.viewOrder', $dAO->unique_order_id) }}" target="_blank">
                                            <span style="font-size: 15px;">#{{ substr($dAO->unique_order_id, -7) }}</span>
                                        </a>
                                        <br>
                                        @if ($dAO->user)
                                            {{ optional($dAO->user)->name ?? 'غير متوفر' }} - {{ optional($dAO->user)->phone ?? 'غير متوفر' }}
                                        @else
                                            User not available
                                        @endif
                                        <br>
                                        <b>{{ optional($dAO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                        {{ $dAO->updated_at->diffForHumans() }}<br>
                                        @if ($dAO->delivery_type == '1' && $dAO->accept_delivery && optional($dAO->accept_delivery->user)->name)
                                            Delivery Guy: <b>{{ optional($dAO->accept_delivery->user)->name ?? 'غير متوفر' }}</b><br>
                                            @if ($accepted_activity)
                                                Order Accepted: <b class="accept-time">{{ Carbon\Carbon::parse($accepted_activity->created_at)->format('h:i A') }}</b><br>
                                                @if ($dAO->restaurant->show_time_on_order_accept && $dAO->delay_before_driver_visibility)
                                                    @php
                                                        $start = new DateTime($accepted_activity->created_at);
                                                        $end = new DateTime($dAO->delay_before_driver_visibility);
                                                        $diff = $start->diff($end);
                                                        $minutes = ($diff->h * 60) + $diff->i;
                                                        $text_message = $dAO->orderstatus_id == 5 ? 'Time' : 'Time';
                                                    @endphp
                                                    <span class="wait-time">{{ $text_message }}: <b>{{ $minutes + 15 }} minutes</b></span>
                                                @endif
                                            @endif
                                        @else
                                            Delivery Guy: <b>غير متوفر</b><br>
                                        @endif
                                        </br>
                                        Ready by: <b>{{ $dAO->prep_time ? Carbon\Carbon::parse($dAO->prep_time)->format('h:i A') : 'غير محدد' }}</b><br>
                                    </div>
                                    <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                        {{ config('setting.currencyFormat') }}{{ $dAO->total ?? '0.00' }}<br>
                                        <span class="badge badge-color-5">{{ $dAO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                        @if ($dAO->delivery_type == 1 && $dAO->location)
                                            <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($dAO->location)->lat ?? '' }},{{ json_decode($dAO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                        @endif
                                    </div>
                                </div>
                                <hr>
                            @endforeach
                        @endif
                    </div>
                </a>
            </div>
        </div>
        <div class="col-12 col-xl-3 mb-2 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left text-default">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="dashboard-display-number">Picked Up</div>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="dashboard-display-icon-block block-bg-1">
                                <span class="dashboard-display-icon color-purple"><b>{{ count($pickedUpOrders) }}</b></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive" style="max-height: 400px;">
                        @if (count($pickedUpOrders) == 0)
                            <div class="align-items-center text-center">
                                <i class="icon-exclamation no-data-img"></i>
                                <span class="no-data-text"><br>No data to show</span>
                            </div>
                        @else
                            @foreach ($pickedUpOrders as $pUO)
                                @php
                                    $order = \App\Order::where('unique_order_id', $pUO->unique_order_id)->first();
                                    $accepted_activity = null;
                                    if ($order) {
                                        $accepted_activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $order->id)
                                            ->where('description', 'Order accepted')
                                            ->first();
                                    }
                                @endphp
                                <div class="order row">
                                    <div class="col-8 col-xl-9">
                                        <a href="{{ route('admin.viewOrder', $pUO->unique_order_id) }}" target="_blank">
                                            <span style="font-size: 15px;">#{{ substr($pUO->unique_order_id, -7) }}</span>
                                        </a>
                                        <br>
                                        @if ($pUO->user)
                                            {{ optional($pUO->user)->name ?? 'غير متوفر' }} - {{ optional($pUO->user)->phone ?? 'غير متوفر' }}
                                        @else
                                            User not available
                                        @endif
                                        <br>
                                        <b>{{ optional($pUO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                        {{ $pUO->updated_at->diffForHumans() }}<br>
                                        @if ($pUO->delivery_type == '1' && $pUO->accept_delivery && optional($pUO->accept_delivery->user)->name)
                                            Delivery Guy: <b>{{ optional($pUO->accept_delivery->user)->name ?? 'غير متوفر' }}</b><br>
                                            @if ($accepted_activity)
                                                Order Accepted: <b class="accept-time">{{ Carbon\Carbon::parse($accepted_activity->created_at)->format('h:i A') }}</b><br>
                                                @if ($pUO->restaurant->show_time_on_order_accept && $pUO->delay_before_driver_visibility)
                                                    @php
                                                        $start = new DateTime($accepted_activity->created_at);
                                                        $end = new DateTime($pUO->delay_before_driver_visibility);
                                                        $diff = $start->diff($end);
                                                        $minutes = ($diff->h * 60) + $diff->i;
                                                        $text_message = $pUO->orderstatus_id == 5 ? 'Time' : 'Time';
                                                    @endphp
                                                    <span class="wait-time">{{ $text_message }}: <b>{{ $minutes + 15 }} minutes</b></span>
                                                @endif
                                            @endif
                                        @else
                                            Delivery Guy: <b>غير متوفر</b>
                                        @endif
                                    </div>
                                    <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                        {{ config('setting.currencyFormat') }}{{ $pUO->total ?? '0.00' }}<br>
                                        <span class="badge badge-color-5">{{ $pUO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                        @if ($pUO->delivery_type == 1 && $pUO->location)
                                            <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($pUO->location)->lat ?? '' }},{{ json_decode($pUO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                        @endif
                                    </div>
                                </div>
                                <hr>
                            @endforeach
                        @endif
                    </div>
                </a>
            </div>
        </div>
        @if(config('setting.enSPU')=="true")
            <div class="col-12 col-xl-3 mb-2 mt-2">
                <div class="col-xl-12 dashboard-display p-3">
                    <a class="block block-link-shadow text-left text-default">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="dashboard-display-number">Ready for Pickup</div>
                                <sub>(Self-Pickup Orders)</sub>
                            </div>
                            <div class="d-none d-sm-block">
                                <div class="dashboard-display-icon-block block-bg-1">
                                    <span class="dashboard-display-icon color-purple"><b>{{ count($pickupReadyOrders) }}</b></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive" style="max-height: 400px;">
                            @if (count($pickupReadyOrders) == 0)
                                <div class="align-items-center text-center">
                                    <i class="icon-exclamation no-data-img"></i>
                                    <span class="no-data-text"><br>No data to show</span>
                                </div>
                            @else
                                @foreach ($pickupReadyOrders as $pRO)
                                    <div class="order row">
                                        <div class="col-8 col-xl-9">
                                            <a href="{{ route('admin.viewOrder', $pRO->unique_order_id) }}" target="_blank">
                                                <span style="font-size: 15px;">#{{ substr($pRO->unique_order_id, -7) }}</span>
                                            </a>
                                            <br>
                                            @if ($pRO->user)
                                                {{ optional($pRO->user)->name ?? 'غير متوفر' }} - {{ optional($pRO->user)->phone ?? 'غير متوفر' }}
                                            @else
                                                User not available
                                            @endif
                                            <br>
                                            <b>{{ optional($pRO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                            {{ $pRO->updated_at->diffForHumans() }}
                                        </div>
                                        <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                            {{ config('setting.currencyFormat') }}{{ $pRO->total ?? '0.00' }}<br>
                                            <span class="badge badge-color-5">{{ $pRO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                            @if ($pRO->delivery_type == 1 && $pRO->location)
                                                <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($pRO->location)->lat ?? '' }},{{ json_decode($pRO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        @endif
        @if(\Nwidart\Modules\Facades\Module::find('OrderSchedule') && \Nwidart\Modules\Facades\Module::find('OrderSchedule')->isEnabled())
            <div class="col-12 col-xl-3 mb-2 mt-2">
                <div class="col-xl-12 dashboard-display p-3">
                    <a class="block block-link-shadow text-left text-default">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="dashboard-display-number">Scheduled</div>
                            </div>
                            <div class="d-none d-sm-block">
                                <div class="dashboard-display-icon-block block-bg-1">
                                    <span class="dashboard-display-icon color-purple"><b>{{ count($scheduledOrders) }}</b></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive" style="max-height: 400px;">
                            @if (count($scheduledOrders) == 0)
                                <div class="align-items-center text-center">
                                    <i class="icon-exclamation no-data-img"></i>
                                    <span class="no-data-text"><br>No data to show</span>
                                </div>
                            @else
                                @foreach ($scheduledOrders as $sO)
                                    <div class="order row">
                                        <div class="col-8 col-xl-9">
                                            <a href="{{ route('admin.viewOrder', $sO->unique_order_id) }}" target="_blank">
                                                <span style="font-size: 15px;">#{{ substr($sO->unique_order_id, -7) }}</span>
                                            </a>
                                            <br>
                                            @if ($sO->user)
                                                {{ optional($sO->user)->name ?? 'غير متوفر' }} - {{ optional($sO->user)->phone ?? 'غير متوفر' }}
                                            @else
                                                User not available
                                            @endif
                                            <br>
                                            <b>{{ optional($sO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                            {{ $sO->updated_at->diffForHumans() }}<br>
                                            <b>Date:</b> {{ json_decode($sO->schedule_date)->day ?? 'غير محدد' }},
                                                {{ json_decode($sO->schedule_date)->date ?? 'غير محدد' }}
                                            <br>
                                            <b>Slot:</b> {{ json_decode($sO->schedule_slot)->open ?? 'غير محدد' }} -
                                                {{ json_decode($sO->schedule_slot)->close ?? 'غير محدد' }}
                                        </div>
                                        <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                            {{ config('setting.currencyFormat') }}{{ $sO->total ?? '0.00' }}<br>
                                            <span class="badge badge-color-5">{{ $sO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                            @if ($sO->delivery_type == 1 && $sO->location)
                                                <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($sO->location)->lat ?? '' }},{{ json_decode($sO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-12 col-xl-3 mb-2 mt-2">
                <div class="col-xl-12 dashboard-display p-3">
                    <a class="block block-link-shadow text-left text-default">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="dashboard-display-number">Confirmed</div>
                                <sub>by store for Scheduled Order</sub>
                            </div>
                            <div class="d-none d-sm-block">
                                <div class="dashboard-display-icon-block block-bg-1">
                                    <span class="dashboard-display-icon color-purple"><b>{{ count($scheduleConfirmedOrders) }}</b></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive" style="max-height: 400px;">
                            @if (count($scheduleConfirmedOrders) == 0)
                                <div class="align-items-center text-center">
                                    <i class="icon-exclamation no-data-img"></i>
                                    <span class="no-data-text"><br>No data to show</span>
                                </div>
                            @else
                                @foreach ($scheduleConfirmedOrders as $sCO)
                                    <div class="order row">
                                        <div class="col-8 col-xl-9">
                                            <a href="{{ route('admin.viewOrder', $sCO->unique_order_id) }}" target="_blank">
                                                <span style="font-size: 15px;">#{{ substr($sCO->unique_order_id, -7) }}</span>
                                            </a>
                                            <br>
                                            @if ($sCO->user)
                                                {{ optional($sCO->user)->name ?? 'غير متوفر' }} - {{ optional($sCO->user)->phone ?? 'غير متوفر' }}
                                            @else
                                                User not available
                                            @endif
                                            <br>
                                            <b>{{ optional($sCO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                            {{ $sCO->updated_at->diffForHumans() }}<br>
                                            <b>Date:</b> {{ json_decode($sCO->schedule_date)->day ?? 'غير محدد' }},
                                                {{ json_decode($sCO->schedule_date)->date ?? 'غير محدد' }}
                                            <br>
                                            <b>Slot:</b> {{ json_decode($sCO->schedule_slot)->open ?? 'غير محدد' }} -
                                                {{ json_decode($sCO->schedule_slot)->close ?? 'غير محدد' }}
                                        </div>
                                        <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                            {{ config('setting.currencyFormat') }}{{ $sCO->total ?? '0.00' }}<br>
                                            <span class="badge badge-color-5">{{ $sCO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                            @if ($sCO->delivery_type == 1 && $sCO->location)
                                                <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($sCO->location)->lat ?? '' }},{{ json_decode($sCO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        @endif
        @role('Admin')
            <div class="col-12 col-xl-3 mb-2 mt-2">
                <div class="col-xl-12 dashboard-display p-3">
                    <a class="block block-link-shadow text-left text-default">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="dashboard-display-number">Awaiting Payment</div>
                            </div>
                            <div class="d-none d-sm-block">
                                <div class="dashboard-display-icon-block block-bg-5">
                                    <span class="dashboard-display-icon color-orange"><b>{{ count($awaitingOrders) }}</b></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive" style="max-height: 400px;">
                            @if (count($awaitingOrders) == 0)
                                <div class="align-items-center text-center">
                                    <i class="icon-exclamation no-data-img"></i>
                                    <span class="no-data-text"><br>No data to show</span>
                                </div>
                            @else
                                @foreach ($awaitingOrders as $aO)
                                    <div class="order row">
                                        <div class="col-8 col-xl-9">
                                            <a href="{{ route('admin.viewOrder', $aO->unique_order_id) }}" target="_blank">
                                                <span style="font-size: 15px;">#{{ substr($aO->unique_order_id, -7) }}</span>
                                            </a>
                                            <br>
                                            @if ($aO->user)
                                                {{ optional($aO->user)->name ?? 'غير متوفر' }} - {{ optional($aO->user)->phone ?? 'غير متوفر' }}
                                            @else
                                                User not available
                                            @endif
                                            <br>
                                            <b>{{ optional($aO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                            {{ $aO->updated_at->diffForHumans() }}
                                        </div>
                                        <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                            {{ config('setting.currencyFormat') }}{{ $aO->total ?? '0.00' }}<br>
                                            {{ $aO->payment_mode ?? 'غير محدد' }}
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        @endrole
        @role('Admin')
            <div class="col-12 col-xl-3 mb-2 mt-2">
                <div class="col-xl-12 dashboard-display p-3">
                    <a class="block block-link-shadow text-left text-default">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="dashboard-display-number">Completed</div>
                                <sub>upto recent 25 orders only</sub>
                            </div>
                            <div class="d-none d-sm-block">
                                <div class="dashboard-display-icon-block block-bg-4">
                                    <span class="dashboard-display-icon color-green"><b>{{ count($completedOrders) }}</b></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive" style="max-height: 400px;">
                            @if (count($completedOrders) == 0)
                                <div class="align-items-center text-center">
                                    <i class="icon-exclamation no-data-img"></i>
                                    <span class="no-data-text"><br>No data to show</span>
                                </div>
                            @else
                                @foreach ($completedOrders->reverse()->take(25) as $cO)
                                    @php
                                        $order = \App\Order::where('unique_order_id', $cO->unique_order_id)->first();
                                        $accepted_activity = null;
                                        if ($order) {
                                            $accepted_activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $order->id)
                                                ->where('description', 'Order accepted')
                                                ->first();
                                        }
                                    @endphp
                                    <div class="order row">
                                        <div class="col-8 col-xl-9">
                                            <a href="{{ route('admin.viewOrder', $cO->unique_order_id) }}" target="_blank">
                                                <span style="font-size: 15px;">#{{ substr($cO->unique_order_id, -7) }}</span>
                                            </a>
                                            <br>
                                            @if ($cO->user)
                                                {{ optional($cO->user)->name ?? 'غير متوفر' }} - {{ optional($cO->user)->phone ?? 'غير متوفر' }}
                                            @else
                                                User not available
                                            @endif
                                            <br>
                                            <b>{{ optional($cO->restaurant)->name ?? 'غير متوفر' }}</b><br>
                                            {{ $cO->updated_at->diffForHumans() }}<br>
                                            @if ($cO->delivery_type == '1' && $cO->accept_delivery && optional($cO->accept_delivery->user)->name)
                                                Delivery Guy: <b>{{ optional($cO->accept_delivery->user)->name ?? 'غير متوفر' }}</b><br>
                                                @if ($accepted_activity)
                                                    Order Accepted: <b class="accept-time">{{ Carbon\Carbon::parse($accepted_activity->created_at)->format('h:i A') }}</b><br>
                                                    @if ($cO->restaurant->show_time_on_order_accept && $cO->delay_before_driver_visibility)
                                                        @php
                                                            $start = new DateTime($accepted_activity->created_at);
                                                            $end = new DateTime($cO->delay_before_driver_visibility);
                                                            $diff = $start->diff($end);
                                                            $minutes = ($diff->h * 60) + $diff->i;
                                                            $text_message = $cO->orderstatus_id == 5 ? 'Time' : 'Time';
                                                        @endphp
                                                        <span class="wait-time">{{ $text_message }}: <b>{{ $minutes + 15 }} minutes</b></span>
                                                    @endif
                                                @endif
                                            @else
                                                Delivery Guy: <b>غير متوفر</b>
                                            @endif
                                        </div>
                                        <div class="col-4 col-xl-3 text-right align-items-center order-amount">
                                            {{ config('setting.currencyFormat') }}{{ $cO->total ?? '0.00' }}<br>
                                            <span class="badge badge-color-5">{{ $cO->payment_mode ?? 'غير محدد' }}</span><br><br>
                                            @if ($cO->delivery_type == 1 && $cO->location)
                                                <a class="btn btn-sm btn-dark" href="http://maps.google.com/maps?q={{ json_decode($cO->location)->lat ?? '' }},{{ json_decode($cO->location)->lng ?? '' }}" target="_blank"><i class="icon-pin"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        @endrole
    </div>
</div>
<input type="hidden" id="server-timestamp" value="{{ Carbon\Carbon::now()->timestamp }}">
<script>
    $(document).ready(function(){
        setInterval(function(){
            console.log("page reloaded");
            $("#liveorders").load(window.location.href + " #liveorders", function(response, status, xhr) {
                if (xhr.status == 401) {
                    window.location.href = "{{ url('/admin/login') }}";
                }
            });
        }, 15000);
    });

    var serverTime = "{{ $serverTime }}";
    var serverTimezone = "{{ $serverTimezone }}";
    var freshTime = new Date(serverTime);

    function updateServerTime() {
        freshTime.toLocaleString("en-US", { timeZone: serverTimezone });
        $("#current-time-now").text(formatTime(freshTime));
        freshTime.setSeconds(freshTime.getSeconds() + 1);
    }

    function formatTime(date) {
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var seconds = date.getSeconds();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        return hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    }

    setInterval(updateServerTime, 1000);
    updateServerTime();

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endsection
