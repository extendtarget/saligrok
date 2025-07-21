@extends('admin.layouts.master')
@section('title')
    Order - Dashboard
@endsection
@section('content')
    @if (config('setting.iHaveFoodomaaDeliveryApp') == 'true')
        <script src="https://www.gstatic.com/firebasejs/8.4.0/firebase.js"></script>
    @endif
    <style>
        .content-wrapper {
            overflow: hidden;
        }

        .bill-calc-table tr td {
            padding: 6px 80px;
        }

        @media (min-width: 320px) and (max-width: 767px) {
            .bill-calc-table tr td {
                padding: 6px 50px;
            }
        }

        .td-title {
            padding-left: 15px !important;
        }

        .td-data {
            padding-right: 15px !important;
        }

        @media (min-width: 1200px) {
            .container {
                max-width: 95%;
            }
        }


        .timeline-ul,
        .timeline-li {
            list-style: none;
            padding: 0;
        }

        .timeline-li {
            padding-bottom: 1.5rem;
            border-left: 1px solid #abaaed;
            position: relative;
            padding-left: 20px;
            margin-left: 10px;
        }

        .timeline-li:last-child {
            border: 0px;
            padding-bottom: 0;
        }

        .timeline-li:before {
            content: "";
            width: 15px;
            height: 15px;
            background: #fff;
            border: 3px solid #8360c3;
            box-shadow: 3px 3px 0px rgba(46, 191, 145, 40%);
            border-radius: 50%;
            position: absolute;
            left: -10px;
            top: 0px;
        }
    </style>

    <div class="content mt-2 mb-5">

        <div class="d-flex justify-content-between my-2">
            <h3></h3>
            <div>
           @role('Admin')
            @if ($order->orderstatus_id != 5 && $order->orderstatus_id != 6)
                <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mr-2" data-toggle="modal" data-target="#manageOrder">
                    <b><i class="icon-database-insert"></i></b>
                    Manage Order
                </button>
            @endif
        @else
            @can('manage_order')
                @if ($order->orderstatus_id != 5 && $order->orderstatus_id != 6)
                    <button type="button" class="btn btn-secondary btn-labeled btn-labeled-left mr-2" data-toggle="modal" data-target="#manageOrder">
                        <b><i class="icon-database-insert"></i></b>
                        Manage Order
                    </button>
                @endif
            @endcan
        @endrole
                @if (
                    \Nwidart\Modules\Facades\Module::find('ThermalPrinter') &&
                        \Nwidart\Modules\Facades\Module::find('ThermalPrinter')->isEnabled())
                    <button type="button" class="btn btn-secondary btn-labeled mr-1 thermalPrintButton" disabled="disabled"
                        title="{{ __('thermalPrinterLang.connectingToPrinterMessage') }}" data-type="kot"><i
                            class="icon-printer4 mr-1 thermalPrinterIcon"></i>
                        {{ __('thermalPrinterLang.printKotWithThermalPrinter') }}</button>
                    <button type="button" class="btn btn-secondary btn-labeled mr-2 thermalPrintButton" disabled="disabled"
                        title="{{ __('thermalPrinterLang.connectingToPrinterMessage') }}" data-type="invoice"><i
                            class="icon-printer4 mr-1 thermalPrinterIcon"></i>
                        {{ __('thermalPrinterLang.printInvoiceWithThermalPrinter') }}</button>
                    <input type="hidden" id="thermalPrinterCsrf" value="{{ csrf_token() }}">
                    <script>
                        var socket = null;
                        var socket_host = 'ws://127.0.0.1:6441';

                        initializeSocket = function() {
                            try {
                                if (socket == null) {
                                    socket = new WebSocket(socket_host);
                                    socket.onopen = function() {};
                                    socket.onmessage = function(msg) {
                                        let message = msg.data;
                                        $.jGrowl("", {
                                            position: 'bottom-center',
                                            header: message,
                                            theme: 'bg-danger',
                                            life: '5000'
                                        });
                                    };
                                    socket.onclose = function() {
                                        socket = null;
                                    };
                                }
                            } catch (e) {
                                console.log("ERROR", e);
                            }

                            var checkSocketConnecton = setInterval(function() {
                                if (socket == null || socket.readyState != 1) {
                                    $('.thermalPrintButton').attr({
                                        disabled: 'disabled',
                                        title: '{{ __('thermalPrinterLang.connectingToPrinterFailedMessage') }}'
                                    });
                                }
                                if (socket != null && socket.readyState == 1) {
                                    $('.thermalPrintButton').removeAttr('disabled').removeAttr('title');
                                }
                                clearInterval(checkSocketConnecton);
                            }, 500)
                        };

                        initializeSocket();

                        $('.thermalPrintButton').click(function(event) {
                            $('.thermalPrinterIcon').removeClass('icon-printer').addClass('icon-spinner10 spinner');
                            let printButton = $('.thermalPrintButton');
                            printButton.attr('disabled', 'disabled');
                            let printType = $(this).data("type");

                            let order_id = '{{ $order->id }}';
                            let token = $('#thermalPrinterCsrf').val();

                            $.ajax({
                                    url: '{{ route('thermalprinter.getOrderData') }}',
                                    type: 'POST',
                                    dataType: 'JSON',
                                    data: {
                                        order_id: order_id,
                                        _token: token,
                                        print_type: printType
                                    },
                                })
                                .done(function(response) {
                                    let content = {};
                                    content.type = 'print-receipt';
                                    content.data = response;
                                    let sendData = JSON.stringify(content);
                                    if (socket != null && socket.readyState == 1) {
                                        socket.send(sendData);
                                        $.jGrowl("", {
                                            position: 'bottom-center',
                                            header: '{{ __('thermalPrinterLang.printCommandSentMessage') }}',
                                            theme: 'bg-success',
                                            life: '3000'
                                        });
                                        setTimeout(function() {
                                            $('.thermalPrinterIcon').removeClass('icon-spinner10 spinner').addClass(
                                                'icon-printer');
                                            printButton.removeAttr('disabled');
                                        }, 1000);
                                    } else {
                                        initializeSocket();
                                        setTimeout(function() {
                                            socket.send(sendData);
                                            $.jGrowl("", {
                                                position: 'bottom-center',
                                                header: '{{ __('storeDashboard.printCommandSentMessage') }}',
                                                theme: 'bg-success',
                                                life: '5000'
                                            });
                                        }, 700);
                                    }
                                })
                                .fail(function() {
                                    alert("ERROR")
                                })
                        });
                    </script>
                @endif
                <a href="javascript:void(0)" id="printButton" class="btn btn-secondary btn-labeled mr-1"> Print Bill</a>
                <a href="{{ route('admin.printThermalBill', $order->unique_order_id) }}" id="printButton"
                    class="btn btn-secondary btn-labeled">Print Thermal Bill</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card" id="printThis">
                    <div class="p-3">
                        <div class="d-flex justify-content-between @if ($agent->isMobile()) flex-column @endif)">
                            <div class="form-group mb-0">
                                <h3><strong>{{ $order->restaurant->name }}</strong><br>
                                    <p style="font-size: 1rem; font-weight: 400;" class="mb-0">
                                        {{ $order->unique_order_id }}
                                    </p>
                                </h3>
                            </div>
                            <div class="form-group mb-0">
                                <label class="control-label no-margin text-semibold mr-1"><strong>Order
                                        Date:</strong></label>
                                {{ $order->created_at->format('Y-m-d - h:i A') }}
                            </div>
                        </div>
                        <hr>
                        <div>
                            <div class="row">
                                <div class="col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label class="control-label no-margin text-semibold mr-1">
                                            <strong>
                                                <h5 class="font-weight-bold">Customer Details</h5>
                                            </strong>
                                        </label>
                                        <br>
                                        <p><b>Name: </b> {{ $order->user->name }}</p>
                                        <p><b>Email: </b> {{ $order->user->email }}</p>
                                        <p><b>Contact Number: </b> {{ $order->user->phone }}<a
                                                href="https://wa.me/{{ $order->user->phone }}"
                                                class="btn btn-secondary btn-labeled mr-1 ml-3"><i
                                                    class="fa fa-whatsapp"></i> Whatsapp</a></p>
                                        @if ($order->delivery_type == 4)
                                            <p><b>Pickup Address: </b> {{ $pickup_details['address'] }}</p>
                                        @endif
                                        @if ($order->delivery_type == 4 || $order->delivery_type == 1)
                                            <p><b>Delivery Address: </b> {{ $order->address }}</p>
                                        @endif
                                        @if ($order->user->tax_number != null)
                                            <p><b>Tax Number: </b> {{ strtoupper($order->user->tax_number) }}</p>
                                        @endif
                                        @if ($order->order_comment != null)
                                            <p class="mb-0"><b>Comment/Suggestion:</b></p>
                                            <h4 class="text-danger"><b>{{ $order->order_comment }}</b></h4>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    $completedOrders = $order->user->orders->where('orderstatus_id', 5);
                                    $cancelledOrders = $order->user->orders->where('orderstatus_id', 6);
                                @endphp
                                <div class="col-md-6 col-sm-12">
                                    <div class="form-group mb-1">
                                        <div class="d-flex">
                                            <div class="col p-0 mr-2">
                                                <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                                    style="border: 1px solid #ddd;">
                                                    <div class="py-1" style="font-weight: 900;">Completed Orders</div>
                                                    <hr style="width: 100%;" class="m-0">
                                                    <div class="py-1 text-success" style="font-weight: 500;">
                                                        {{ $completedOrders->count() }} -
                                                        {{ config('setting.currencyFormat') . $completedOrders->sum('total') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col p-0">
                                                <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                                    style="border: 1px solid #ddd;">
                                                    <div class="py-1" style="font-weight: 900;">Cancelled Orders</div>
                                                    <hr style="width: 100%;" class="m-0">
                                                    <div class="py-1 text-danger" style="font-weight: 500;">
                                                        {{ $cancelledOrders->count() }} -
                                                        {{ config('setting.currencyFormat') . $cancelledOrders->sum('total') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal -->
                                    <div class="modal fade" id="orderHistoryModal" tabindex="-1" role="dialog"
                                        aria-labelledby="orderHistoryModalLabel">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title" id="deliveryGuyCurrentOrderModalLabel">Customer
                                                        Order History</h4>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body bg-white" id="orderHistoryModalBody">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button"
                                                        class="btn btn-secondary btn-labeled btn-labeled-left mr-1 mt-2"
                                                        id="printOrderHistoryBtn"><b><i class="icon-printer"></i> </b> Print
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-dark btn-labeled btn-labeled-left mt-2"
                                                        data-dismiss="modal" aria-label="Close"><b><i
                                                                class="icon-cross"></i> </b> Close </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-1">
                                        <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                            style="border: 1px solid #ddd;">
                                            <div class="py-1" style="font-weight: 900;">STATUS</div>
                                            <hr style="width: 100%;" class="m-0">
                                            <div class="py-1 text-success @if ($order->orderstatus_id == 6) text-danger @endif"
                                                style="font-weight: 500;">
                                                {{ getOrderStatusName($order->orderstatus_id) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-1 mt-2">
                                        <div class="d-flex">
                                            <div class="col p-0 mr-2">
                                                <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                                    style="border: 1px solid #ddd;">
                                                    <div class="py-1" style="font-weight: 900;">Order Type</div>
                                                    <hr style="width: 100%;" class="m-0">
                                                    <div class="py-1 text-warning" style="font-weight: 500;">
                                                        @if ($order->delivery_type == 1)
                                                            Delivery
                                                        @elseif ($order->delivery_type == 2)
                                                            Self-Pickup
                                                        @elseif ($order->delivery_type == 4)
                                                            Pickup-Drop
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                      <div class="col p-0">
                                          <!--extend by aya -->
                                           <div class="col p-0">
                                                <div class="d-flex justify-content-center align-items-center flex-column mb-1" style="border: 1px solid #ddd;">
                                                    <div class="py-1" style="font-weight: 900;">Payment Mode</div>
                                                    <hr style="width: 100%;" class="m-0">
                                                    <div class="py-1 text-warning" style="font-weight: 500;">
                                                        @if ($order->wallet_amount > 0 && $order->wallet_amount >= $order->total && $order->payment_mode === 'WALLET')
                                                            WALLET
                                                        @elseif ($order->wallet_amount > 0 && $order->wallet_amount < $order->total && $order->payment_mode === 'COD')
                                                            PARTIAL (COD + {{ config('setting.walletName') }})
                                                        @elseif ($order->payment_mode === 'COD')
                                                            COD
                                                        @else
                                                            {{ strtoupper($order->payment_mode) }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($order->delivery_charge != $order->actual_delivery_charge)
                                        <div class="form-group mb-1 mt-2">
                                            <div class="d-flex">
                                                <div class="col p-0 mr-2">
                                                    <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                                        style="border: 1px solid #ddd;">
                                                        <div class="py-1" style="font-weight: 900;">Actual DC</div>
                                                        <hr style="width: 100%;" class="m-0">
                                                        <div class="py-1 text-danger" style="font-weight: 500;">
                                                            {{ config('setting.currencyFormat') }}{{ $order->actual_delivery_charge }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col p-0">
                                                    <div class="d-flex justify-content-center align-items-center flex-column mb-1"
                                                        style="border: 1px solid #ddd;">
                                                        <div class="py-1" style="font-weight: 900;">Delivery Charge Loss
                                                        </div>
                                                        <hr style="width: 100%;" class="m-0">
                                                        <div class="py-1 text-danger" style="font-weight: 500;">
                                                            {{ config('setting.currencyFormat') }}{{ number_format($order->actual_delivery_charge - $order->delivery_charge, 2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php
                            $subTotal = 0;
                            function calculateAddonTotal($addons)
                            {
                                $total = 0;
                                foreach ($addons as $addon) {
                                    $total += $addon->addon_price;
                                }
                                return $total;
                            }
                        @endphp
              <div class="text-right mt-3">
    <div class="form-group mb-2">
        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-12 p-2 mb-3" style="background-color: #f7f8fb; float: right; text-align: left;">
                @if ($order->delivery_type != 4)
                    @foreach ($order->orderitems as $item)
                        <div>
                            <div class="d-flex mb-1 align-items-start" style="font-size: 1.2rem;">
                                <span class="badge badge-flat border-grey-800 text-default mr-2 order-item-quantity">{{ $item->quantity }}x</span>
                                <strong class="mr-1" style="width: 100%;">{{ $item->name }}</strong>
                                @php
                                    $itemTotal = ($item->price + calculateAddonTotal($item->order_item_addons)) * $item->quantity;
                                    $itemTotalwithoutAddon = $item->price * $item->quantity;
                                    $subTotal = $subTotal + $itemTotal;
                                @endphp
                                <span class="badge badge-flat border-grey-800 text-default">{{ config('setting.currencyFormat') }}{{ $itemTotalwithoutAddon }}</span>
                            </div>
                            @if (count($item->order_item_addons))
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Addon</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($item->order_item_addons as $addon)
                                                <tr>
                                                    <td>{{ $addon->addon_category_name }}</td>
                                                    <td>{{ $addon->addon_name }}</td>
                                                    <td>{{ config('setting.currencyFormat') }}{{ $addon->addon_price }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            @if (!$loop->last)
                                <div class="mb-2" style="border-bottom: 2px solid #dcdcdc;"></div>
                            @endif
                        </div>
                    @endforeach
                @else
                    @php
                        $taskDetails = json_decode($order->task_details);
                    @endphp
                    <div>
                        <div class="d-flex mb-1 align-items-start" style="font-size: 1.2rem;">
                            <span class="badge badge-flat border-grey-800 text-default mr-2 order-item-quantity">{{ $taskDetails->task }}</span>
                            <strong class="mr-1" style="width: 100%;">{{ $taskDetails->task_comment }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
<div class="float-right">
    <table class="table table-bordered table-striped bill-calc-table">
        <tr>
            <td class="text-left td-title">SubTotal</td>
            <td class="td-data">{{ config('setting.currencyFormat') }}{{ $subTotal }}</td>
        </tr>
@if ($order->coupon_name != null)
    <tr>
        <td class="text-left td-title">Coupon</td>
        <td class="td-data">
            {{ $order->coupon_name }}
            @php
                $coupon = \App\Coupon::where('code', $order->coupon_name)->first();
                $coupon_amount = $order->coupon_amount;
                $coupon_applied_on = '';
                $is_coupon_applied = true;

                // Check if coupon is valid based on min_subtotal
                if ($coupon && $coupon->discount_type === 'FIXED' && $subTotal < ($coupon->min_subtotal ?? 0)) {
                    $is_coupon_applied = false;
                    $coupon_applied_on = 'Not applied (Subtotal ' . config('setting.currencyFormat') . $subTotal . ' is below minimum ' . config('setting.currencyFormat') . ($coupon->min_subtotal ?? 0) . ')';
                } elseif ($coupon && $coupon->is_used_for_delivery) {
                    if ($order->delivery_charge == 0) {
                        $coupon_amount = $order->actual_delivery_charge;
                        $coupon_applied_on = 'Free Delivery';
                    } elseif ($order->actual_delivery_charge != $order->delivery_charge) {
                        $coupon_amount = $order->actual_delivery_charge - $order->delivery_charge;
                        $coupon_applied_on = 'Delivery Coupon Discount';
                    }
                } elseif ($coupon) {
                    if ($coupon->discount_type == 'PERCENTAGE' || $coupon->discount_type == 'FIXED') {
                        $coupon_applied_on = 'Applied on Food Only';
                    }
                } elseif ($order->coupon_name == 'FREESHIP') {
                    $coupon_amount = $order->actual_delivery_charge;
                    $coupon_applied_on = 'Free Delivery';
                }
            @endphp
            @if ($coupon_amount != null && $coupon_amount > 0 && $is_coupon_applied)
                ({{ config('setting.currencyFormat') }}{{ number_format($coupon_amount, 2) }})
                @if ($coupon_applied_on)
                    <span class="text-info" style="font-size: 0.9rem;">{{ $coupon_applied_on }}</span>
                @endif
            @elseif ($is_coupon_applied && $coupon && $coupon->is_used_for_delivery && $order->delivery_charge == 0)
                <span class="text-info" style="font-size: 0.9rem;">{{ $coupon_applied_on }}</span>
            @else
                <span class="text-warning" style="font-size: 0.9rem;">{{ $coupon_applied_on ?: 'Not applied' }}</span>
            @endif
        </td>
    </tr>
@endif
        @if ($order->restaurant_charge != null)
            <tr>    
                <td class="text-left td-title">Store Charge</td>
                <td class="td-data">{{ config('setting.currencyFormat') }}{{ $order->restaurant_charge }}</td>
            </tr>
        @endif
 <tr>
            <td class="text-left td-title">Delivery Charge</td>
            <td class="td-data">
                {{ config('setting.currencyFormat') }}{{ $order->delivery_charge }}
                @if ($order->delivery_charge == 0 && $order->is_free_delivery)
                    
                    @if ($order->coupon_name == 'FREESHIP')
                        <span class="text-warning" style="font-size: 0.9rem;">
                           
                        </span>
                    @endif
                @endif
                @if ($order->coupon_name && $order->coupon_name != 'FREESHIP')
                    @php
                        $coupon = \App\Coupon::where('code', $order->coupon_name)->first();
                    @endphp
                    @if ($coupon)
                        <span class="text-info" style="font-size: 0.9rem;">
                            
                        </span>
                        @if ($coupon->is_used_for_delivery && $order->actual_delivery_charge != $order->delivery_charge)
                            
                        @endif
                    @endif
                @elseif ($order->coupon_name == 'FREESHIP')
                    
                @endif
            </td>
        </tr>
        @if ($order->tax != null)
            <tr>
                <td class="text-left td-title">Tax</td>
                <td class="td-data">{{ $order->tax }}% @if ($order->tax_amount != null)
                        ({{ config('setting.currencyFormat') }}{{ $order->tax_amount }})
                    @endif
                </td>
            </tr>
        @endif
        @if ($order->service_charge != null)
            <tr>
                <td class="text-left td-title">Service Charge</td>
                <td class="td-data">{{ $order->service_charge }}% @if ($order->service_charge_amount != null)
                        ({{ config('setting.currencyFormat') }}{{ $order->service_charge_amount }})
                    @endif
                </td>
            </tr>
        @endif
        @if (!$order->tip_amount == null)
            <tr>
                <td class="text-left td-title">Tip</td>
                <td class="td-data">{{ config('setting.currencyFormat') }}{{ $order->tip_amount }}</td>
            </tr>
        @endif
        @if ($order->wallet_amount != null)
            <tr>
                <td class="text-left td-title">Paid With {{ config('setting.walletName') }}</td>
                <td class="td-data">{{ config('setting.currencyFormat') }}{{ $order->wallet_amount }}</td>
            </tr>
        @endif
        @if ($order->round_off_amount != null)
            <tr>
                <td class="text-left td-title">Round Off Amount</td>
                <td class="td-data">{{ config('setting.currencyFormat') }}{{ $order->round_off_amount }}</td>
            </tr>
        @endif
        <tr>
            <td class="text-left td-title"><b>Total</b></td>
            <td class="td-data">{{ config('setting.currencyFormat') }}{{ max(0, $order->total) }}</td>
        </tr>
        @if ($order->payable != null)
            <tr>
                <td class="text-left td-title">Payable</td>
                <td class="td-data"><b>{{ config('setting.currencyFormat') }}{{ max(0, $order->payable) }}</b></td>
            </tr>
        @endif
    </table>
</div>
<div class="clearfix"></div>

</div>

                    </div>
                </div>
            </div>
            <div class="col-lg-3 mb-5">
                @if ($order->rating)
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center mb-3"><b>Rating and Review</b></p>
                            <div>
                                @if ($order->delivery_type == 1)
                                    <p> <b>Delivery Rating </b> <span
                                            class="ml-1 badge badge-flat text-white {{ ratingColorClass($order->rating->rating_delivery) }}">{{ $order->rating->rating_delivery }}
                                            <i class="icon-star-full2 text-white" style="font-size: 0.6rem;"></i></span>
                                    </p>
                                    <p>{{ $order->rating->review_delivery }}</p>
                                    <hr>
                                @endif
                                <p> <b>Store Rating </b> <span
                                        class="ml-1 badge badge-flat text-white {{ ratingColorClass($order->rating->rating_store) }}">{{ $order->rating->rating_store }}
                                        <i class="icon-star-full2 text-white" style="font-size: 0.6rem;"></i></span> </p>
                                <p>{{ $order->rating->review_store }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($order->schedule_slot != null)
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center mb-0">
                                <b>
                                    Scheduled Order
                                </b>
                                <br>
                                <b>Date:</b> {{ json_decode($order->schedule_date)->day }},
                                {{ json_decode($order->schedule_date)->date }}
                                <br>
                                <b>Slot:</b> {{ json_decode($order->schedule_slot)->open }} -
                                {{ json_decode($order->schedule_slot)->close }}
                            </p>
                        </div>
                    </div>
                @endif
                @if ($order->payment_mode == 'RAZORPAY' && $order->razorpay_data)
                    <div class="card">
                        <div class="card-body">
                            <p class="text-left mb-0">
                                <b>Razorpay ID:</b> <a
                                    href="https://dashboard.razorpay.com/app/orders/{{ $order->razorpay_data->razorpay_order_id_first }}"
                                    target="_blank">{{ $order->razorpay_data->razorpay_order_id_first }}
                                </a>
                                @if ($order->razorpay_data->razorpay_payment_id != null)
                                    <br>
                                    <b>Razorpay Payment ID:</b>
                                    <a href="https://dashboard.razorpay.com/app/payments/{{ $order->razorpay_data->razorpay_payment_id }}"
                                        target="_blank">
                                        {{ $order->razorpay_data->razorpay_payment_id }}</a>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-center mb-0"><b>Delivery Pin<br />
                                        <h3 class="text-success text-center">{{ $order->delivery_pin }}</h3>
                                    </b></p>
                            </div>
                            @if ($order->distance != null)
                                <div class="col-6">
                                    <p class="text-center"><b>
                                            Distance from Store to Customer:<br />
                                            {{ number_format((float) $order->distance, 2, '.', '') }} km
                                        </b></p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <!--Ayaaaa-->
                             @role('Admin')
                        @if ($order->orderstatus_id == 5)
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center"> <strong> Order Financials </strong> </h3>
                                    <hr class="mt-1">
                                    <table class="table table-striped">
                                        @if (
                                            $order->commission_amount != null ||
                                            $order->driver_salary != null ||
                                            $order->driver_delivery_charge_earning != null ||
                                            $order->driver_delivery_tip_earning != null ||
                                            $order->final_profit != null ||
                                            $order->restaurant_net_amount != null)
                                            @if ($order->commission_amount != null && $order->commission_amount > 0)
                                                <tr>
                                                    <th>Commission Amount</th>
                                                    <td class="text-center mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->commission_amount }}
                                                        ({{ $order->commission_rate }}%)</td>
                                                </tr>
                                            @endif
                                            @if ($order->driver_salary != null && $order->driver_salary > 0)
                                                <tr>
                                                    <th>Driver Salary</th>
                                                    <td class="text-center mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->driver_salary }}</td>
                                                </tr>
                                            @endif
                                            @if ($order->driver_delivery_charge_earning != null && $order->driver_delivery_charge_earning > 0)
                                                <tr>
                                                    <th>Driver Commission Amount</th>
                                                    <td class="text-center mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->driver_delivery_charge_earning }}
                                                        ({{ $order->driver_delivery_charge_rate }}%)</td>
                                                </tr>
                                            @endif
                                            @if ($order->driver_delivery_tip_earning != null && $order->driver_delivery_tip_earning > 0)
                                                <tr>
                                                    <th>Driver Tip Amount</th>
                                                    <td class="text-center mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->driver_delivery_tip_earning }}
                                                        ({{ $order->driver_delivery_tip_rate }}%)</td>
                                                </tr>
                                            @endif
                                            @if ($order->driver_penalty_amount != null && $order->driver_penalty_amount > 0)
                                                <tr>
                                                    <th>Driver Penalty</th>
                                                    <td class="text-center mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->driver_penalty_amount }}
                                                        ({{ $order->driver_penalty_rate }}%)</td>
                                                </tr>
                                            @endif
                                      @if ($order->final_profit != null)
                                                    <tr>
                                                        <th>Final Profit</th>
                                                        @if ($order->final_profit > 0)
                                                            <th class="text-center text-success mb-0">
                                                                {{ config('setting.currencyFormat') }}{{ $order->final_profit }}
                                                            </th>
                                                        @else
                                                            <th class="text-center text-danger mb-0">
                                                                {{ config('setting.currencyFormat') }}{{ $order->final_profit }}
                                                                @if ($order->delivery_charge == 0)
                                                                    <span class="text-warning" style="font-size: 0.9rem;">(Adjusted for free delivery)</span>
                                                                @endif
                                                            </th>
                                                        @endif
                                                    </tr>
                                                @endif
                                            @if ($order->restaurant_net_amount != null)
                                                <tr>
                                                    <th>Restaurant Net Amount</th>
                                                    <th class="text-center text-danger mb-0">
                                                        {{ config('setting.currencyFormat') }}{{ $order->restaurant_net_amount }}
                                                    </th>
                                                </tr>
                                            @endif
                                        @else
                                            <h5 class="text-center"><i class="icon-exclamation"></i> No records found</h5>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        @endif
                    @else
                        @can('view_order_financials')
                            @if ($order->orderstatus_id == 5)
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="text-center"> <strong> Order Financials </strong> </h3>
                                        <hr class="mt-1">
                                        <table class="table table-striped">
                                            @if (
                                                $order->commission_amount != null ||
                                                $order->driver_salary != null ||
                                                $order->driver_delivery_charge_earning != null ||
                                                $order->driver_delivery_tip_earning != null ||
                                                $order->final_profit != null ||
                                                $order->restaurant_net_amount != null)
                                                @if ($order->commission_amount != null && $order->commission_amount > 0)
                                                    <tr>
                                                        <th>Commission Amount</th>
                                                        <td class="text-center mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->commission_amount }}
                                                            ({{ $order->commission_rate }}%)</td>
                                                    </tr>
                                                @endif
                                                @if ($order->driver_salary != null && $order->driver_salary > 0)
                                                    <tr>
                                                        <th>Driver Salary</th>
                                                        <td class="text-center mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->driver_salary }}</td>
                                                    </tr>
                                                @endif
                                                @if ($order->driver_delivery_charge_earning != null && $order->driver_delivery_charge_earning > 0)
                                                    <tr>
                                                        <th>Driver Commission Amount</th>
                                                        <td class="text-center mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->driver_delivery_charge_earning }}
                                                            ({{ $order->driver_delivery_charge_rate }}%)</td>
                                                    </tr>
                                                @endif
                                                @if ($order->driver_delivery_tip_earning != null && $order->driver_delivery_tip_earning > 0)
                                                    <tr>
                                                        <th>Driver Tip Amount</th>
                                                        <td class="text-center mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->driver_delivery_tip_earning }}
                                                            ({{ $order->driver_delivery_tip_rate }}%)</td>
                                                    </tr>
                                                @endif
                                                @if ($order->driver_penalty_amount != null && $order->driver_penalty_amount > 0)
                                                    <tr>
                                                        <th>Driver Penalty</th>
                                                        <td class="text-center mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->driver_penalty_amount }}
                                                            ({{ $order->driver_penalty_rate }}%)</td>
                                                    </tr>
                                                @endif
                                                @if ($order->final_profit != null)
                                                    <tr>
                                                        <th>Final Profit</th>
                                                        @if ($order->final_profit > 0)
                                                            <th class="text-center text-success mb-0">
                                                                {{ config('setting.currencyFormat') }}{{ $order->final_profit }}</th>
                                                        @else
                                                            <th class="text-center text-danger mb-0">
                                                                {{ config('setting.currencyFormat') }}{{ $order->final_profit }}
                                                            </th>
                                                        @endif
                                                    </tr>
                                                @endif
                                                @if ($order->restaurant_net_amount != null)
                                                    <tr>
                                                        <th>Restaurant Net Amount</th>
                                                        <th class="text-center text-danger mb-0">
                                                            {{ config('setting.currencyFormat') }}{{ $order->restaurant_net_amount }}
                                                        </th>
                                                    </tr>
                                                @endif
                                            @else
                                                <h5 class="text-center"><i class="icon-exclamation"></i> No records found</h5>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endcan
                    @endrole
          

                @if ($order->cash_change_amount != null && $order->cash_change_amount > 0)
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center mb-0">Cash Change Requested:
                                {{ config('setting.currencyFormat') }}{{ $order->cash_change_amount }}</p>
                        </div>
                    </div>
                @endif


                <!-- START added by qusay -->
                @php
                    $accepted_activity = Spatie\Activitylog\Models\Activity::where('subject_id', $order->id)
                    ->where('description', 'Order accepted')
                    ->first(); 
                @endphp
                
                @if ( $order->restaurant->show_time_on_order_accept && $accepted_activity )
                
                    @php
                        
                        $start = new DateTime($accepted_activity->created_at);
                        $end = new DateTime($order->delay_before_driver_visibility);
                        
                        $diff = $start->diff($end);
                        $minutes = ($diff->h * 60) + $diff->i;
                    
                        $text_message = $order->orderstatus_id == 5 ? 'The time the driver waited before receiving the order' : 'The time the driver waits before receiving the order';
                    @endphp
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center flex-column mb-1">
                                <div class="py-1" style="font-weight: 900;">{{ $text_message }}</div>
                                <hr style="width: 100%;" class="m-0">
                                <div class="py-1 text-primary" style="font-weight: 500;">
                                    {{ $minutes + 15 }} minutes
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                 <!-- END added by qusay -->
            
                            @if ($order->orderstatus_id != 5 && $order->orderstatus_id != 6)
                            <!--Extend By Aya-->
                           <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center"> <strong> Order Actions </strong> </h3>
                                    <hr class="mt-1">
                                    <div class="form-group d-flex justify-content-center">
                                        @if( $order->restaurant->is_order_need_approval_by_admin && !$order->is_accepted_by_admin )
                                            <form action="{{ route('admin.acceptOrderAsAdmin') }}" class="mr-1" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $order->id }}">
                                                <button class="btn btn-primary btn-labeled btn-labeled-left mr-1"> <b><i class="icon-checkmark3 ml-1"></i> </b> Accept as Admin </button>
                                            </form>
                                        @else
                                            @if (($order->orderstatus_id == 1 || $order->orderstatus_id == 11) && !$order->restaurant->show_time_on_order_accept)
                                                <form id="formAcceptOrder" action="{{ route('admin.acceptOrderFromAdmin') }}" class="mr-1" method="POST">
                                                    <input type="hidden" name="id" value="{{ $order->id }}">
                                                    @csrf
                                                    <button class="btn btn-primary btn-labeled btn-labeled-left mr-1"> <b><i class="icon-checkmark3 ml-1"></i> </b> Accept Order </button>
                                                </form>
                                            @endif
                                            @if ( ($order->orderstatus_id == 1 || $order->orderstatus_id == 11) && $order->restaurant->show_time_on_order_accept)
                                                <button class="btn btn-primary btn-labeled btn-labeled-left mr-1" data-toggle="modal" data-target="#set_time_of_delay_before_driver_visibility"> <b><i class="icon-checkmark3 ml-1"></i> </b> Accept Order </button>
                                                <div class="modal fade" id="set_time_of_delay_before_driver_visibility" tabindex="-1" role="dialog" aria-labelledby="set_time_of_delay_before_driver_visibilityLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form id="formAcceptOrder" action="{{ route('admin.acceptOrderFromAdmin') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="id" value="{{ $order->id }}">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="cancelModalLabel">Accept Order</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">×</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Enter the time to wait before showing the request to drivers:</p>
                                                                    <select id="delay_before_driver_visibility" name="delay_before_driver_visibility" class="form-control">
                                                                        <option value="15">15 Minutes</option>
                                                                        <option value="30">30 Minutes</option>
                                                                        <option value="45">45 Minutes</option>
                                                                        <option value="60">1 Hour</option>
                                                                        <option value="other">other</option>
                                                                    </select>
                                                                    <input type="number" id="customTime" min="15" name="custom_time" class="form-control mt-2 customTime" placeholder="Enter the time (in minutes)" style="display:none;" >
                                                                    <label style="color: #d66464; display:none;" class="customTime">Minimum 15 minutes</label>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                                    <button type="button" id="submitFormAcceptOrder" class="btn btn-primary">Accept Order</button>
                                                                    <button type="submit" style="visibility: hidden"></button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <script>
                                                    $(document).ready(function() {
                                                        $('#delay_before_driver_visibility').on('change', function() {
                                                            if ($(this).val() === 'other') {
                                                                $('.customTime').show();
                                                            } else {
                                                                $('.customTime').hide();
                                                            }
                                                        });
                                                        let restaurant_id = <?= $order->restaurant_id ?>;
                                                        $('#submitFormAcceptOrder').on('click', function(e) {
                                                            if ( $('#delay_before_driver_visibility').val() == 'other' && $('#customTime').val() < 15 ) {
                                                                alert('Minimum 15 minutes');
                                                                return;
                                                            }
                                                            $('#formAcceptOrder #submitFormAcceptOrder').prop('disabled', 'disabled');
                                                            if ( $("#formAcceptOrder").find('#delay_before_driver_visibility').val() == 'other' ) {
                                                                if ( $('#formAcceptOrder').find('#customTime').val() == '' ) {
                                                                    $('#formAcceptOrder #submitFormAcceptOrder').prop('disabled', '');
                                                                    alert('Enter the time (in minutes).');
                                                                    return;
                                                                }
                                                            }
                                                            $('#formAcceptOrder').off('submit').submit();
                                                        });  
                                                    });
                                                </script>
                                            @endif
                                        @endif
                                        @if ($order->orderstatus_id == 10)
                                            <a href="{{ route('admin.confirmScheduledOrder', $order->id) }}" class="mr-2 btn btn-lg confirmOrderBtn btn-success"> Confirm Order <i class="icon-checkmark3 ml-1"></i></a>
                                        @endif
                                        @if ($order->orderstatus_id == 1 || $order->orderstatus_id == 2 || $order->orderstatus_id == 3 || $order->orderstatus_id == 4 || $order->orderstatus_id == 7 || $order->orderstatus_id == 8 || $order->orderstatus_id == 9 || $order->orderstatus_id == 10 || $order->orderstatus_id == 11)
                                            <a href="javascript:void(0)" class="btn btn-danger btn-labeled" data-toggle="modal" data-target="#cancelModal">
                                                Cancel Order
                                            </a>
                                            <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.cancelOrderFromAdmin') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="cancelModalLabel">Cancel Order</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">×</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label for="refund_type">Select Refund Type:</label>
                                                                    <select class="form-control" name="refund_type" id="refund_type" required>
                                                                        <option value="NOREFUND">No Refund</option>
                                                                        <option value="FULL">Full Refund</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="cancel_reason">Reason for Cancellation:</label>
                                                                    <select class="form-control selectCancelReason" name="cancel_reason" id="cancel_reason" required>
                                                                        <option value="" disabled selected>Select Cancel Reason</option>
                                                                        @foreach ($cancelReasons as $reason)
                                                                            <option value="{{ $reason->cancel_reason }}">{{ $reason->cancel_reason }}</option>
                                                                        @endforeach
                                                                        <option value="other">Others</option>
                                                                    </select>
                                                                    <textarea class="form-control mt-2 d-none" name="cancel_reason" id="cancel_reason_ta" disabled></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-danger">Cancel Order</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($order->orderstatus_id == 8 || $order->orderstatus_id == 9)
                                            <div>
                                                <a href="javascript:void(1)" class="btn btn-secondary btn-labeled dropdown-toggle ml-3" data-toggle="dropdown">
                                                    Approve Payment
                                                </a>
                                                <div class="dropdown-menu">
                                                    <form action="{{ route('admin.approvePaymentOfOrder', [$order->id, $order->payment_mode]) }}" method="GET">
                                                        @csrf
                                                        <button class="dropdown-item" type="submit">
                                                            Approve Payment as {{ $order->payment_mode }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.approvePaymentOfOrder', [$order->id, 'COD']) }}" method="GET">
                                                        @csrf
                                                        <button class="dropdown-item" type="submit">
                                                            Approve Payment as COD
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- Assign Delivery Modal -->
                            <div class="modal fade" id="assignDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="assignDeliveryModalLabel">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="assignDeliveryModalLabel">Assign Delivery Guy</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('admin.assignDeliveryFromAdmin') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                <input type="hidden" name="customer_id" value="{{ $order->user->id }}">
                                                <div class="form-group">
                                                    <label for="user_id">Select Delivery Guy:</label>
                                                    <select class="form-control select" data-fouc name="user_id" required="required">
                                                        <option></option>
                                                        @foreach ($users as $user)
                                                            <option value="{{ $user->id }}"
                                                                @if (!$user->delivery_guy_detail) disabled="disabled" @endif>
                                                                {{ $user->name }} @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                    (Offline)
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="text-right">
                                                    <button type="submit" class="btn btn-primary">Assign</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Re-Assign Delivery Modal -->
                                    <div class="modal fade" id="reAssignDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="reAssignDeliveryModalLabel">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="reAssignDeliveryModalLabel">Re-Assign Delivery Guy</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('admin.reAssignDeliveryFromAdmin') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                        <input type="hidden" name="customer_id" value="{{ $order->user->id }}">
                                                        <div class="form-group">
                                                            <label for="user_id">Select Delivery Guy:</label>
                                                            <select class="form-control select" data-fouc name="user_id" required="required">
                                                                <option></option>
                                                                @foreach ($users as $user)
                                                                    <option value="{{ $user->id }}">{{ $user->name }}
                                                                        @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                            (Offline)
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="text-right">
                                                            <button type="submit" class="btn btn-primary">Re-Assign</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        <!-- Assign Delivery Modal -->
                                            <div class="modal fade" id="assignDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="assignDeliveryModalLabel">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="assignDeliveryModalLabel">Assign Delivery Guy</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="{{ route('admin.assignDeliveryFromAdmin') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                                <input type="hidden" name="customer_id" value="{{ $order->user->id }}">
                                                                <div class="form-group">
                                                                    <label for="user_id">Select Delivery Guy:</label>
                                                                    <select class="form-control select" data-fouc name="user_id" required="required">
                                                                        <option></option>
                                                                        @foreach ($users as $user)
                                                                            <option value="{{ $user->id }}"
                                                                                @if (!$user->delivery_guy_detail) disabled="disabled" @endif>
                                                                                {{ $user->name }} @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                                    (Offline)
                                                                                @endif
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="text-right">
                                                                    <button type="submit" class="btn btn-primary">Assign</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="reAssignDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="reAssignDeliveryModalLabel">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="reAssignDeliveryModalLabel">Re- FINAssign Delivery Guy</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="{{ route('admin.reAssignDeliveryFromAdmin') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                                <input type="hidden" name="customer_id" value="{{ $order->user->id }}">
                                                                <div class="form-group">
                                                                    <label for="user_id">Select Delivery Guy:</label>
                                                                    <select class="form-control select" data-fouc name="user_id" required="required">
                                                                        <option></option>
                                                                        @foreach ($users as $user)
                                                                            <option value="{{ $user->id }}">{{ $user->name }}
                                                                                @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                                    (Offline)
                                                                                @endif
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="text-right">
                                                                    <button type="submit" class="btn btn-primary">Re-Assign</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                                @if ($order->delivery_type != 2)
                                @role('Admin')
                                    @if ($order->orderstatus_id == 1 || $order->orderstatus_id == 2)
                                        <div class="card">
                                            <div class="card-body">
                                                <label class="control-label no-margin text-semibold mr-1"><strong>Assign Delivery Guy</strong></label>
                                                <form action="{{ route('admin.assignDeliveryFromAdmin') }}" method="POST">
                                                    <input type="text" hidden value="{{ $order->id }}" name="order_id">
                                                    <input type="text" hidden value="{{ $order->user->id }}" name="customer_id">
                                                    @csrf
                                                    <div class="form-group row mb-0">
                                                        <div class="col-lg-12 mb-2"> 
                                                            <select class="form-control select" data-fouc name="user_id" required="required">
                                                                <option></option>
                                                                @foreach ($users as $user)
                                                                    <option value="{{ $user->id }}"
                                                                        @if (!$user->delivery_guy_detail) disabled="disabled" @endif>
                                                                        {{ $user->name }} @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                            (Offline)
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <br>
                                                        <div class="col-lg-12 mt-1 text-right float-right p-0">
                                                            <button type="submit" class="btn btn-secondary mr-1">
                                                                Assign Delivery
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($order->orderstatus_id == 3 || $order->orderstatus_id == 4)
                                        <div class="card">
                                            <div class="card-body">
                                                @if ($order->accept_delivery && $order->accept_delivery->user && $order->accept_delivery->user->name)
                                                    <p class="text-center mb-2"> <strong>Assigned Delivery Guy:
                                                            {{ $order->accept_delivery->user->name }}
                                                            @if ($order->accept_delivery->user->delivery_guy_detail->status == 0)
                                                                <span class="text-danger"> (Offline) </span>
                                                            @endif
                                                        </strong>
                                                    </p>
                                                @endif
                                                <form action="{{ route('admin.reAssignDeliveryFromAdmin') }}" method="POST">
                                                    <input type="text" hidden value="{{ $order->id }}" name="order_id">
                                                    <input type="text" hidden value="{{ $order->user->id }}" name="customer_id">
                                                    @csrf
                                                    <div class="form-group row"> 
                                                        <div class="col-lg-12">
                                                            <select class="form-control select" data-fouc name="user_id" required="required">
                                                                <option></option>
                                                                @foreach ($users as $user)
                                                                    <option value="{{ $user->id }}">{{ $user->name }}
                                                                        @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                            (Offline)
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-12 mt-2 text-center">
                                                            <button type="submit" class="btn btn-secondary">
                                                                Re-Assign Delivery
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    @can('assign_delivery_guy')
                                        @if ($order->orderstatus_id == 1 || $order->orderstatus_id == 2)
                                            <div class="card">
                                                <div class="card-body">
                                                    <label class="control-label no-margin text-semibold mr-1"><strong>Assign Delivery Guy</strong></label>
                                                    <form action="{{ route('admin.assignDeliveryFromAdmin') }}" method="POST">
                                                        <input type="text" hidden value="{{ $order->id }}" name="order_id">
                                                        <input type="text" hidden value="{{ $order->user->id }}" name="customer_id">
                                                        @csrf
                                                        <div class="form-group row mb-0">
                                                            <div class="col-lg-12 mb-2"> 
                                                                <select class="form-control select" data-fouc name="user_id" required="required">
                                                                    <option></option>
                                                                    @foreach ($users as $user)
                                                                        <option value="{{ $user->id }}"
                                                                            @if (!$user->delivery_guy_detail) disabled="disabled" @endif>
                                                                            {{ $user->name }} @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                                (Offline)
                                                                            @endif
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <br>
                                                            <div class="col-lg-12 mt-1 text-right float-right p-0">
                                                                <button type="submit" class="btn btn-secondary mr-1">
                                                                    Assign Delivery
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($order->orderstatus_id == 3 || $order->orderstatus_id == 4)
                                            <div class="card">
                                                <div class="card-body">
                                                    @if ($order->accept_delivery && $order->accept_delivery->user && $order->accept_delivery->user->name)
                                                        <p class="text-center mb-2"> <strong>Assigned Delivery Guy:
                                                                {{ $order->accept_delivery->user->name }}
                                                                @if ($order->accept_delivery->user->delivery_guy_detail->status == 0)
                                                                    <span class="text-danger"> (Offline) </span>
                                                                @endif
                                                            </strong>
                                                        </p>
                                                    @endif
                                                    <form action="{{ route('admin.reAssignDeliveryFromAdmin') }}" method="POST">
                                                        <input type="text" hidden value="{{ $order->id }}" name="order_id">
                                                        <input type="text" hidden value="{{ $order->user->id }}" name="customer_id">
                                                        @csrf
                                                        <div class="form-group row"> 
                                                            <div class="col-lg-12">
                                                                <select class="form-control select" data-fouc name="user_id" required="required">
                                                                    <option></option>
                                                                    @foreach ($users as $user)
                                                                        <option value="{{ $user->id }}">{{ $user->name }}
                                                                            @if ($user->delivery_guy_detail && $user->delivery_guy_detail->status == 0)
                                                                                (Offline)
                                                                            @endif
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-lg-12 mt-2 text-center">
                                                                <button type="submit" class="btn btn-secondary">
                                                                    Re-Assign Delivery
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endcan
                                @endrole
                            @endif
            </div>


            <div class="col-lg-3 mb-5">
                @if ($order->orderstatus_id == 5)
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><b>Order Completed in:</b></h4>
                            <span>{{ timeStampDiffFormatted($order->created_at, $order->updated_at) }}</span>
                        </div>
                    </div>
                @elseif($order->orderstatus_id == 6)
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><b>Order cancelled in:</b></h4>
                            <span>{{ timeStampDiffFormatted($order->created_at, $order->updated_at) }}</span>
                        </div>
                    </div>
                @elseif($order->orderstatus_id == 9)
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><b>Payment Failed in:</b></h4>
                            <span>{{ timeStampDiffFormatted($order->created_at, $order->updated_at) }}</span>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><b>Ongoing order</b></h4>
                            <span class="liveTimerNonCompleteOrder"></span>
                        </div>
                    </div>
                @endif

                @if ($order->accept_delivery && $order->accept_delivery && !is_null($order->accept_delivery->location_data))
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="mb-0"><b>Delivery Assigned Location</b></h4>
                            <div class="row mt-2">
                                <div class="col-5 text-left">
                                    <span>Latitude</span><br>
                                    <span>Longitude</span><br>
                                    <span>Store Distance</span><br>
                                    <span>Customer Distance</span><br>
                                    <span>Total Travel</span>
                                    @if (isset(json_decode($order->accept_delivery->location_data)->condition))
                                        <br><span>Auto-Assign CD</span>
                                    @endif
                                </div>
                                <div class="col-5">
                                    @php
                                        $accept_location_data = json_decode($order->accept_delivery->location_data);
                                        $customer_location = json_decode($order->location);
                                        $storeDistance =
                                            $accept_location_data->store_distance ??
                                            getOsmDistance(
                                                $order->restaurant->latitude,
                                                $order->restaurant->longitude,
                                                $accept_location_data->lat,
                                                $accept_location_data->long
                                            );
                                        $customerDistance =
                                            $accept_location_data->customer_distance ??
                                            getOsmDistance(
                                                $accept_location_data->lat,
                                                $accept_location_data->long,
                                                $customer_location->lat,
                                                $customer_location->lng
                                            );
                                        $totalTravelDistance = $order->distance + $storeDistance;
                                    @endphp
                                    <span>{{ $accept_location_data->lat }}</span><br>
                                    <span>{{ $accept_location_data->long }}</span><br>
                                    <span>{{ $storeDistance }} KM</span><br>
                                    <span>{{ $customerDistance }} KM</span><br>
                                    <span>{{ $totalTravelDistance }} KM</span>
                                    @if (isset(json_decode($order->accept_delivery->location_data)->condition))
                                        <br><span>{{ json_decode($order->accept_delivery->location_data)->condition }}</span>
                                    @endif
                                </div>
                                <div class="col-1">
                                    <a class="btn btn-sm btn-dark"
                                        href="http://maps.google.com/maps?q={{ json_decode($order->accept_delivery->location_data)->lat }},{{ json_decode($order->accept_delivery->location_data)->long }}"
                                        target="_blank"><i class="icon-pin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($order->orderstatus_id == 6)
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-center"> <strong> Cancel Reason </strong> </h3>
                            <hr class="mt-1">
                            <div class="form-group mx-2 justify-content-center text-center">
                                <div class="row mb-2">
                                    <div class="col-3 font-weight-bold">Refund</div>
                                    <div class="col-9 text-danger font-weight-bold">{{ $order->refund_type }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-3 font-weight-bold">Reason</div>
                                    <div class="col-9 text-danger">{{ $order->cancel_reason }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                
                
              @if ($order->delivery_type != 2)
                    @role('Admin')
                        @if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6)
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center"> <strong> Get Nearest Delivery Guy </strong> </h3>
                                    <hr class="mt-1">
                                    <div class="form-group d-flex justify-content-center" id="getDeliveryGuyButton">
                                        <button type="button" class="btn btn-primary btn-labeled btn-labeled-left mr-1" id="getDeliveryGuys"><b><i class="icon-inbox ml-1"></i> </b> Get Data </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        @can('get_nearest_delivery_guy')
                            @if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6)
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="text-center"> <strong> Get Nearest Delivery Guy </strong> </h3>
                                        <hr class="mt-1">
                                        <div class="form-group d-flex justify-content-center" id="getDeliveryGuyButton">
                                            <button type="button" class="btn btn-primary btn-labeled btn-labeled-left mr-1" id="getDeliveryGuys"><b><i class="icon-inbox ml-1"></i> </b> Get Data </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endcan
                    @endrole
                @endif
                @if (count($activities) > 0)
                    <div class="card">
                        <div class="card-body">
                            <div>
                                <ul class="timeline-ul">
                                    @foreach ($activities as $activity)
                                        <li class="timeline-li">
                                            <div class="small" data-popup="tooltip" data-placement="left"
                                                title="{{ $activity->created_at->format('Y-m-d - h:i:s A') }}">
                                                <b>{{ $activity->created_at->format('h:i A') }}</b>
                                            </div>
                                            {{ $activity->description }}
                                            @if ($activity->causer && $activity->properties['type'] != 'Order_Accepted_Auto')
                                                <span> by </span>
                                                <a href="{{ route('admin.get.editUser', $activity->causer->id) }}"
                                                    class="text-default">
                                                    <b>{{ $activity->causer->name }}</b>
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                        </div>
                    </div>
                @endif
                    <!--riad-->
                <!--@if (config('setting.iHaveFoodomaaDeliveryApp') == 'true')-->
                <!--    <div class="card">-->
                <!--        <div class="card-body p-1">-->
                <!--            <div id="map" style="height: 280px"></div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--@endif-->
            </div>
        </div>
            </div>
            </div>
        <div id="manageOrder" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">Manage Order
                        (#{{ $order->unique_order_id }})</span></h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-header">
                <h5 class="modal-title"><span class="font-weight-bold">{{ $order->restaurant->name }}</span></h5>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.updateOrder', $order->id) }}" method="POST" id="order-update-form">
                    <div class='col-lg-12 form-group row'>
                        <div class='col-lg-4'>
                            <label class="col-form-label">Order Type:</label>
                        </div>
                        <div class='col-lg-4'>
                            <select name="delivery_type" class="form-control form-control-lg">
                                <option value="1" @if ($order->delivery_type == 1) selected @endif>Delivery</option>
                                <option value="2" @if ($order->delivery_type == 2) selected @endif>Self-Pickup</option>
                            </select>
                        </div>
                    </div>
                    <!--<div class='col-lg-12 form-group row'>-->
                    <!--    <div class='col-lg-4'>-->
                    <!--        <label class="col-form-label">Free Delivery:</label>-->
                    <!--    </div>-->
                    <!--    <div class='col-lg-4'>-->
                    <!--        <input value="true" type="checkbox" class="switchery-primary" name="free_delivery" id="free_delivery">-->
                    <!--        <p class="text-muted small mt-1">Activating this applies a 100% free delivery coupon.</p>-->
                    <!--    </div>-->
                    <!--</div>-->
                    <script>
                        $(document).ready(function() {
                            new Switchery(document.querySelector('.switchery-primary'), {
                                color: '#2196f3'
                            });
                            $('#free_delivery').change(function(e) {
                                var isChecked = $(this).prop('checked');
                                if (isChecked) {
                                    $(this).attr('checked', 'checked').val('true');
                                    $('#deliveryChargeRow').addClass('d-none');
                                    $('#editDeliveryCharge').prop('checked', false).trigger('change');
                                } else {
                                    $(this).removeAttr('checked').val('false');
                                    $('#deliveryChargeRow').removeClass('d-none');
                                }
                            });
                        });
                    </script>
                  <!--ayaaa-->
                  <div class='col-lg-12 form-group row' id="deliveryChargeRow">
                <div class='col-lg-4'>
                    <label class="col-form-label" style="margine-bottom: 0px; padding-bottom: 0px;">Delivery
                        Charge:</label>
                </div>
                <div class='col-lg-4'>
                    <input value="true" type="checkbox" class="switchery-primary switch"
                        name="editDeliveryCharge" id="editDeliveryCharge">
                    {{-- <input value="{{ $order->delivery_charge }}" name="delivery_charge" type='number'
                        class='form-control  form-control-lg text-right' placeholder='0'> --}}
                    @php
                        $chargeValues = [ '9999', '10499', '10999', '11499', '11999', '12499', '12999', '13499', '13999', '14499', '14999', '15499', '15999', '16499', '16999', '17499', '17999', '18499', '18999', '19499', '19999', '20499', '20999', '21499', '21999', '22499', '22999', '23499', '23999', '24499', '24999', '25499', '25999', '26499', '26999', '27499', '27999', '28499', '28999', '29499', '29999', '30499', '30999', '31499', '31999', '32499', '32999', '33499', '33999', '34499', '34999', '35499', '35999', '36499', '36999', '37499', '37999', '38499', '38999', '39499', '39999', '40499', '40999', '41499', '41999', '42499', '42999', '43499', '43999', '44499', '44999', '45499', '45999', '46499', '46999', '47499', '47999', '48499', '48999', '49499', '49999', '50499', '50999', '51499', '51999', '52499', '52999', '53499', '53999', '54499', '54999', '55499', '55999', '56499', '56999', '57499', '57999', '58499', '58999', '59499', '59999', '60499', '60999', '61499', '61999', '62499', '62999', '63499', '63999', '64499', '64999', '65499', '65999', '66499', '66999', '67499', '67999', '68499', '68999', '69499', '69999', '70499', '70999', '71499', '71999', '72499', '72999', '73499', '73999', '74499', '74999', '75499', '75999', '76499', '76999', '77499', '77999', '78499', '78999', '79499', '79999', '80499', '80999', '81499', '81999', '82499', '82999', '83499', '83999', '84499', '84999', '85499', '85999', '86499', '86999', '87499', '87999', '88499', '88999', '89499', '89999', '90499', '90999', '91499', '91999', '92499', '92999', '93499', '93999', '94499', '94999', '95499', '95999', '96499', '96999', '97499', '97999', '98499', '98999', '99499'];
                    @endphp
                    <select class='form-control form-control-lg select d-none' name="delivery_charge"
                        id="selectDeliveryCharge" disabled="true">
                        @foreach ($chargeValues as $value)
                            <option value="{{ $value }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    <script>
                        $(document).ready(function() {
                            new Switchery(document.querySelector('.switch'), {
                                color: '#2196f3'
                            });
                            $('#editDeliveryCharge').change(function(e) {
                                // Check if checkbox is checked
                                var isChecked = $(this).prop('checked');

                                // Toggle checked attribute and value
                                if (isChecked) {
                                    $(this).attr('checked', 'checked').val('true');
                                } else {
                                    $(this).removeAttr('checked').val('false');
                                }

                                // Toggle disabled attribute for #selectDeliveryCharge
                                if (!isChecked) {
                                    $('#selectDeliveryCharge').prop('disabled', true);
                                    $('#selectDeliveryCharge').addClass('d-none');
                                } else {
                                    $('#selectDeliveryCharge').prop('disabled', false);
                                    $('#selectDeliveryCharge').removeClass('d-none');
                                    $('.select').select2();
                                }
                            });
                        });
                    </script>
                </div>
            </div>
                     <!--=================================-->
                    <div class='col-lg-12 form-group row'>
                        <div class='col-lg-4'>
                            <label class="col-form-label">Restaurant Charge:</label>
                        </div>
                        <div class='col-lg-4'>
                            <input value="{{ $order->restaurant_charge }}" name="restaurant_charge" type='number'
                                class='form-control form-control-lg text-right' placeholder='0'>
                        </div>
                    </div>
                    <div class='col-lg-12 form-group row'>
                        <div class='col-lg-4'>
                            <label class="col-form-label">Item Name</label>
                        </div>
                        <div class='col-lg-2'>
                            <label class="col-form-label">Qty</label>
                        </div>
                        <div class='col-lg-2'>
                            <label class="col-form-label">Price</label>
                        </div>
                        <div class='col-lg-2'>
                            <label class="col-form-label">Subtotal</label>
                        </div>
                    </div>
                 @foreach ($order->orderitems as $item)
    <div class='form-group row'>
        <div class='col-lg-4'>
            <input value="{{ $item->name }}" type='text' disabled
                class='form-control form-control-lg' placeholder='Item Name'>
            <input type='hidden' name='existing_items[{{ $item->id }}][name]' value="{{ $item->name }}">
        </div>
        <div class='col-lg-2'>
            <input value="{{ $item->quantity }}" type='number' min='1'
                class='form-control form-control-lg text-right' placeholder='0'
                name='existing_items[{{ $item->id }}][quantity]'>
        </div>
        <div class='col-lg-2'>
            <input value="{{ $item->price }}" type='number' min='0' step='0.01'
                class='form-control form-control-lg text-right' placeholder='0'
                name='existing_items[{{ $item->id }}][price]'>
        </div>
        <div class='col-lg-2'>
            <input type='text' value="{{ number_format($item->quantity * $item->price, 2) }}"
                disabled class='form-control form-control-lg text-right line-total' placeholder='0'>
        </div>
        <div class='col-lg-2'>
            <button type="button" class='remove btn btn-danger'
                data-id="{{ $item->id }}"><i class='icon-cross2'></i></button>
        </div>
        @if (count($item->order_item_addons))
            <div class='col-lg-12 form-group row'>
                <div class='col-lg-5'>
                    <label class="col-lg-5 col-form-label">Addon Category:</label>
                </div>
                <div class='col-lg-3'>
                    <label class="col-lg-3 col-form-label">Addon:</label>
                </div>
                <div class='col-lg-2'>
                    <label class="col-lg-2 col-form-label">Price:</label>
                </div>
            </div>
            @foreach ($item->order_item_addons as $addon)
                <div class='col-lg-12 form-group row'>
                    <div class='col-lg-5'>
                        <input value="{{ $addon->addon_category_name }}" type='text' disabled
                            class='form-control form-control-lg' placeholder='Addon Category Name'>
                    </div>
                    <div class='col-lg-3'>
                        <input value="{{ $addon->addon_name }}" type='text' disabled
                            class='form-control form-control-lg' placeholder='Addon Name'>
                    </div>
                    <div class='col-lg-2'>
                        <input value="{{ $addon->addon_price }}" type='text' disabled
                            class='form-control form-control-lg' placeholder='Addon Name'>
                    </div>
                </div>
            @endforeach
        @endif
        @if (!$loop->last)
            <div class="mb-2" style="border-bottom: 2px solid #dcdcdc;"></div>
        @endif
    </div>
@endforeach
                    <div id="addon" class="mt-4">
                        <legend class="font-weight-semibold text-uppercase font-size-sm hidden" id="addonsLegend">
                            <i class="icon-list2 mr-2"></i> Add New Item
                        </legend>
                        <div id="lineRowHtmlResult"></div>
                    </div>
                    <div id="lineRowHtml" style="display: none;">
                        <div class='form-group row'>
                            <div class='col-lg-4'>
                                <input type='text' class='form-control form-control-lg'
                                    placeholder='e.g. Item Name' name='name[]'>
                            </div>
                            <div class='col-lg-2'>
                                <input type='number' class='form-control form-control-lg text-right'
                                    placeholder='Qty' name='quantity[]'>
                            </div>
                            <div class='col-lg-2'>
                                <input type='number' class='form-control form-control-lg text-right'
                                    placeholder='Price' name='price[]'>
                            </div>
                            <div class='col-lg-2'>
                                <input type='number' class='form-control form-control-lg text-right' placeholder='0'
                                    disabled name='line_total[]'>
                            </div>
                            <div class='col-lg-2'>
                                <button type="button" class='remove btn btn-danger' data-id=""><i
                                        class='icon-cross2'></i></button>
                            </div>
                        </div>
                    </div>
                    <a href="javascript:void(0)" onclick="add(this)"
                        class="btn btn-secondary btn-labeled btn-labeled-left mt-3"> <b><i
                                class="icon-plus22"></i></b>Add New Item</a>
                    @csrf
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            SAVE
                            <i class="icon-database-insert ml-1"></i>
                        </button>
                    </div>
                    <input type="hidden" name="item_ids" />
                </form>
            </div>
        </div>
    </div>
</div>
            <!-- Modal -->
            <div class="modal fade" id="deliveryGuyCurrentOrderModal" tabindex="-1" role="dialog"
                aria-labelledby="deliveryGuyCurrentOrderModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="deliveryGuyCurrentOrderModalLabel">Delivery Guy Current Orders</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body" id="deliveryGuyCurrentOrderModalBody">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" id="previousModal">Go Back</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="nearestDeliveryGuyModal" tabindex="-1" role="dialog"
                aria-labelledby="nearestDeliveryGuyModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="nearestDeliveryGuyModalLabel">Nearest Delivery Guys</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <center>
                                <div class="d-none" id="getDeliveryGuyButtonLoading">
                                    <h5><b><i class="icon-spinner2 spinner ml-1"></i> </b>Loading...</h5>
                                </div>
                            </center>
                            <div id="nearestDeliveryGuyModalBody"></div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $('#refund_type').select2();
                $('.selectCancelReason').select2();
        
                $('#cancel_reason').change(function() {
                    if ($(this).val() === "other") {
                        $('#cancel_reason_ta').prop('disabled', false);
                        $('#cancel_reason_ta').removeClass('d-none');
                        $('#cancel_reason_ta').prop('required', true);
                    } else {
                        $('#cancel_reason_ta').prop('disabled', true);
                        $('#cancel_reason_ta').addClass('d-none');
                        $('#cancel_reason_ta').prop('required', false);
                    }
                });
        
                function processLocation(places, vectorSource) {
                    var features = [];
                    for (var i = 0; i < places.length; i++) {
                        var iconFeature = new ol.Feature({
                            geometry: new ol.geom.Point(ol.proj.transform([places[i][0], places[i][1]], 'EPSG:4326',
                                'EPSG:3857')),
                        });
        
                        var iconStyle = new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                src: places[i][2],
                                crossOrigin: 'anonymous',
                                scale: 0.5
                            })
                        });
                        iconFeature.setStyle(iconStyle);
                        vectorSource.addFeature(iconFeature);
                    }
                }
        
                $(function() {
                    var orderCreatedData = "{{ $order->created_at }}";
                    var startDateTime = new Date(orderCreatedData);
                    var startStamp = startDateTime.getTime();
        
                    var newDate = new Date();
                    var newStamp = newDate.getTime();
        
                    var timer; // for storing the interval (to stop or pause later if needed)
        
                    function updateClock() {
                        newDate = new Date();
                        newStamp = newDate.getTime();
                        var diff = Math.round((newStamp - startStamp) / 1000);
        
                        var d = Math.floor(diff / (24 * 60 *
                            60)); /* though I hope she won't be working for consecutive days :) */
                        diff = diff - (d * 24 * 60 * 60);
                        var h = Math.floor(diff / (60 * 60));
                        diff = diff - (h * 60 * 60);
                        var m = Math.floor(diff / (60));
                        diff = diff - (m * 60);
                        var s = diff;
                        var checkDay = d > 0 ? true : false;
                        var checkHour = h > 0 ? true : false;
                        var checkMin = m > 0 ? true : false;
                        var checkSec = s > 0 ? true : false;
                        var formattedTime = checkDay ? d + " day" : "";
                        formattedTime += checkHour ? " " + h + " hr" : "";
                        formattedTime += checkMin ? " " + m + " min" : "";
                        formattedTime += checkSec ? " " + s + " sec" : "";
        
                        $('.liveTimerNonCompleteOrder').text(formattedTime);
                    }
        
                    timer = setInterval(updateClock, 1000);
        
                    $('#printButton').on('click', function() {
                        $('#printThis').printThis();
                    });
        
                    $('.select').select2({
                        placeholder: 'Select Delivery Guy',
                        allowClear: true,
                    });
        
                    $('body').on("click", ".approvePayment", function(e) {
                        return false;
                    });
        
                    $('body').on("dblclick", ".approvePayment", function(e) {
                        window.location = this.href;
                        return false;
                    });
        
                    @if ($order->delivery_type != 2)
                        var deliveryGuyMarker =
                            "{{ substr(url('/'), 0, strrpos(url('/'), '/')) }}/assets/backend/images/marker-orange.png";
                        var storeMarker =
                            "{{ substr(url('/'), 0, strrpos(url('/'), '/')) }}/assets/backend/images/store-marker.png";
                        var customerMarker =
                            "{{ substr(url('/'), 0, strrpos(url('/'), '/')) }}/assets/backend/images/customer-marker.png";
                        var orderStatus = "{{ $order->orderstatus_id }}";
                        var customerLat = "{{ json_decode($order->location, true)['lat'] }}";
                        var customerLng = "{{ json_decode($order->location, true)['lng'] }}";
                        var storeLat = "{{ $order->restaurant->latitude }}";
                        var storeLng = "{{ $order->restaurant->longitude }}";
                        var orderStatus = "{{ $order->orderstatus_id }}";
        
                        var vectorSource = new ol.source.Vector({});
        
                        @if (config('setting.iHaveFoodomaaDeliveryApp') == 'true' && $eagleViewData != null)
        
                            var places = [
                                [customerLng, customerLat, customerMarker],
                                [storeLng, storeLat, storeMarker],
                            ];
        
                            var features = [];
                            for (var i = 0; i < places.length; i++) {
                                var iconFeature = new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.transform([places[i][0], places[i][1]],
                                        'EPSG:4326', 'EPSG:3857')),
                                });
        
                                var iconStyle = new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1],
                                        src: places[i][2],
                                        crossOrigin: 'anonymous',
                                        scale: 0.5
                                    })
                                });
                                iconFeature.setStyle(iconStyle);
                                vectorSource.addFeature(iconFeature);
                            }
        
                            var vectorLayer = new ol.layer.Vector({
                                source: vectorSource,
                                updateWhileAnimating: true,
                                updateWhileInteracting: true,
                            });
        
        
                            var fullScreenControl = new ol.control.FullScreen();
                            var map = new ol.Map({
                                target: 'map',
                                controls: ol.control.defaults({
                                    attribution: false
                                }).extend([fullScreenControl]),
                                layers: [new ol.layer.Tile({
                                    source: new ol.source.OSM()
                                }), vectorLayer],
                                loadTilesWhileAnimating: true,
                            });
                            // map.getView().fit(vectorSource.getExtent());
                            var extent = vectorLayer.getSource().getExtent();
                            map.getView().fit(extent, {
                                size: map.getSize(),
                                maxZoom: 12
                            })
        
        
                            @if (
                                ($order->orderstatus_id == 3 || $order->orderstatus_id == 4) &&
                                    $order->accept_delivery &&
                                    $order->accept_delivery->user != null)
                                var deliveryGuyId = "{{ $order->accept_delivery->user->id }}";
                                var config = {
                                    apiKey: "{{ $eagleViewData['project_number'] }}",
                                    databaseURL: "{{ $eagleViewData['firebase_url'] }}",
                                    storageBucket: "{{ $eagleViewData['storage_bucket'] }}",
                                };
                                var firebaseApp = firebase.initializeApp(config);
        
                                var centerBound = false;
        
                                var ref = "/User/" + deliveryGuyId;
                                firebaseApp
                                    .database()
                                    .ref(ref)
                                    .on("value", function(snapshot) {
        
                                        var deliveryGuy = snapshot.val();
        
                                        console.log(deliveryGuy);
                                        console.log(Object.keys(deliveryGuy).length);
        
                                        if (Object.keys(deliveryGuy).length == 0) {
                                            return;
                                        }
        
                                        var newDeliveryGuyLat = deliveryGuy.latitude;
                                        var newDeliveryGuyLng = deliveryGuy.longitude;
        
                                        if (2 in places) {
                                            places[2][0] = newDeliveryGuyLng;
                                            places[2][1] = newDeliveryGuyLat
                                        } else {
                                            var newEntry = [newDeliveryGuyLng, newDeliveryGuyLat, deliveryGuyMarker];
                                            places[2] = newEntry;
                                        }
        
                                        vectorSource.clear();
                                        processLocation(places, vectorSource);
                                    });
                            @endif
                        @endif
                    @endif
                });
            </script>
            <script>
                $('body').tooltip({
                    selector: '[data-popup="tooltip"]'
                });
        
                function add(data) {
                    $('#addonsLegend').removeClass('hidden');
                    var newAddon = document.createElement("div");
                    //    newAddon.innerHTML = "<div class='form-group row'> <div class='col-lg-5'><input type='text' class='form-control  form-control-lg' placeholder='Item Name' name='item_names[]' required> </div> <div class='col-lg-5'> <input type='text' class='form-control  form-control-lg' name='addon_prices[]' placeholder='Addon Price'  required> </div> <div class='col-lg-2'><button class='remove btn btn-danger' data-popup='tooltip' data-placement='right' title='Remove Addon'><i class='icon-cross2'></i></button></div></div>";
                    var newLineItem = $('#lineRowHtml').clone();
                    $('#lineRowHtmlResult').append($(newLineItem).html());
                    // $('#lineRowHtmlResult').html($newLineRow);
                    //    document.getElementById('addon').appendChild(newLineRow);
                }
        
                $(document).on('click', "#order-update-form .remove", function() {
                    var item_id = $(this).attr('data-id');
                    if (item_id) {
                        var item_ids = $('#order-update-form [name=item_ids]').val();
        
                        if (item_ids) {
                            item_ids += ',' + item_id;
                        } else {
                            item_ids += item_id;
                        }
        
                        $('#order-update-form [name=item_ids]').val(item_ids);
                    }
        
                    $(this).closest('.form-group').remove();
                });
        
                // $(document).on('change', "#order-update-form [name='price[]'], #order-update-form [name='quantity[]']", function() {
                //     var line_price = $(this).closest('.form-group').find('[name="price[]"]').val();
                //     var line_qty = $(this).closest('.form-group').find('[name="quantity[]"]').val();
                //     var line_total = line_price * line_qty;
                //     $(this).closest('.form-group').find('[name="line_total[]"]').val(line_total.toFixed(2));
                // });
                // Extend By Aya 
                $(document).on('change', "[name*='quantity'], [name*='price']", function() {
                var $row = $(this).closest('.form-group');
                var price = parseFloat($row.find('[name*="[price]"]').val()) || 0;
                var quantity = parseFloat($row.find('[name*="[quantity]"]').val()) || 0;
                var lineTotal = price * quantity;
                $row.find('.line-total').val(lineTotal.toFixed(2));
            });
                
                
            </script>
           <script>
$(document).ready(function() {
    // Function to update the state of the SAVE button based on valid items
    function updateSaveButtonState() {
        var existingItems = $('#order-update-form .form-group input[disabled]').length > 0 ? $('#order-update-form .form-group:has(input[disabled])').length : 0;
        var newItems = $('#lineRowHtmlResult .form-group').length;
        var validNewItems = true;

        $('#lineRowHtmlResult .form-group').each(function() {
            var name = $(this).find('input[name="name[]"]').val();
            var quantity = parseFloat($(this).find('input[name="quantity[]"]').val()) || 0;
            var price = parseFloat($(this).find('input[name="price[]"]').val()) || 0;
            if (!name || name.trim() === '' || quantity <= 0 || price < 0) {
                validNewItems = false;
                return false;
            }
        });

        if (existingItems > 0 || (newItems > 0 && validNewItems)) {
            $('#order-update-form button[type="submit"]').prop('disabled', false);
        } else {
            $('#order-update-form button[type="submit"]').prop('disabled', true);
        }
    }

    // Update button state on page load
    updateSaveButtonState();

    // Update button state when any field changes
    $(document).on('input change', '[name*="name"], [name*="quantity"], [name*="price"]', function() {
        var $row = $(this).closest('.form-group');
        var price = parseFloat($row.find('[name*="price"]').val()) || 0;
        var quantity = parseFloat($(this).find('input[name="quantity[]"]').val()) || 0;
        var lineTotal = price * quantity;
        $row.find('[name*="line_total"]').val(lineTotal.toFixed(2));
        updateSaveButtonState();
    });

    // Update button state when adding or removing an item
    $(document).on('click', '#order-update-form .remove, #order-update-form a[onclick="add(this)"]', function() {
        setTimeout(updateSaveButtonState, 100);
    });

    // Confirmation before removing the last item
    $(document).on('click', '#order-update-form .remove', function() {
        var remainingItems = $('#order-update-form .form-group').length;
        if (remainingItems === 1) {
            if (!confirm('Removing this item will prevent saving the order. Do you want to proceed?')) {
                return false;
            }
        }
        $(this).closest('.form-group').remove();
        setTimeout(updateSaveButtonState, 100);
    });

    // Validate before submitting the form
    $('#order-update-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Submitting order update form...');
        
        var existingItems = $('#order-update-form .form-group input[disabled]').length > 0 ? $('#order-update-form .form-group:has(input[disabled])').length : 0;
        var newItems = $('#lineRowHtmlResult .form-group').length;
        var validNewItems = true;

        $('#lineRowHtmlResult .form-group').each(function() {
            var name = $(this).find('input[name="name[]"]').val();
            var quantity = parseFloat($(this).find('input[name="quantity[]"]').val()) || 0;
            var price = parseFloat($(this).find('input[name="price[]"]').val()) || 0;
            if (!name || name.trim() === '' || quantity <= 0 || price < 0) {
                validNewItems = false;
                return false;
            }
        });

        if (existingItems === 0 && (newItems === 0 || !validNewItems)) {
            console.log('Validation failed: No valid items.');
            $.jGrowl('Please add at least one valid item with a non-empty name, quantity greater than 0, and non-negative price before saving the order.', {
                position: 'bottom-center',
                header: 'Error',
                theme: 'bg-danger',
                life: 5000
            });
            return false;
        }

        console.log('Validation passed, sending AJAX request to:', $(this).attr('action'));
        // Submit the form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: $(this).serialize(),
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    console.log('Order update successful, closing modal...');
                    $('#manageOrder').modal('hide');
                    $.jGrowl(response.message, {
                        position: 'bottom-center',
                        header: 'Success',
                        theme: 'bg-success',
                        life: 5000
                    });
                    // Delay page reload by 1 seconds
                    console.log('Scheduling page reload in 1 seconds...');
                    setTimeout(function() {
                        console.log('Reloading page...');
                        location.reload();
                    }, 1000);
                } else {
                    console.log('Order update failed:', response.message);
                    $.jGrowl(response.message, {
                        position: 'bottom-center',
                        header: 'Error',
                        theme: 'bg-danger',
                        life: 5000
                    });
                }
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr);
                var message = 'An error occurred while saving the order.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 419) {
                    message = 'Session expired. Please refresh the page and try again.';
                }
                $.jGrowl(message, {
                    position: 'bottom-center',
                    header: 'Error',
                    theme: 'bg-danger',
                    life: 5000
                });
            }
        });
    });

    // Remove duplicate Switchery elements
    $('.switchery').remove();

    // Initialize Switchery for free_delivery and editDeliveryCharge
    if ($('#free_delivery').length === 1 && !$('#free_delivery').data('switchery-initialized')) {
        new Switchery(document.querySelector('#free_delivery'), { color: '#2196f3' });
        $('#free_delivery').data('switchery-initialized', true);
    }

    if ($('#editDeliveryCharge').length === 1 && !$('#editDeliveryCharge').data('switchery-initialized')) {
        new Switchery(document.querySelector('#editDeliveryCharge'), { color: '#2196f3' });
        $('#editDeliveryCharge').data('switchery-initialized', true);
    }

    // Prevent duplicate events for free_delivery
    $('#free_delivery').off('change').on('change', function(e) {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
            $(this).attr('checked', 'checked').val('true');
            $('#deliveryChargeRow').addClass('d-none');
            $('#editDeliveryCharge').prop('checked', false).trigger('change');
        } else {
            $(this).removeAttr('checked').val('false');
            $('#deliveryChargeRow').removeClass('d-none');
        }
    });

    // Prevent duplicate events for editDeliveryCharge
    $('#editDeliveryCharge').off('change').on('change', function(e) {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
            $(this).attr('checked', 'checked').val('true');
            $('#selectDeliveryCharge').prop('disabled', false);
            $('#selectDeliveryCharge').removeClass('d-none');
            $('.select').select2();
        } else {
            $(this).removeAttr('checked').val('false');
            $('#selectDeliveryCharge').prop('disabled', true);
            $('#selectDeliveryCharge').addClass('d-none');
        }
    });

    // Add new item
    function add(data) {
        $('#addonsLegend').removeClass('hidden');
        var newLineItem = $('#lineRowHtml').clone();
        $('#lineRowHtmlResult').append($(newLineItem).html());
        updateSaveButtonState();
    }

    // Remove item
    $(document).on('click', "#order-update-form .remove", function() {
        var item_id = $(this).attr('data-id');
        if (item_id) {
            var item_ids = $('#order-update-form [name=item_ids]').val();
            if (item_ids) {
                item_ids += ',' + item_id;
            } else {
                item_ids = item_id;
            }
            $('#order-update-form [name=item_ids]').val(item_ids);
        }
        $(this).closest('.form-group').remove();
        updateSaveButtonState();
    });

    // Fetch delivery guys' current orders
    $(document).on('click', '.deliveryGuyCurrentOrdersButton', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('admin.getDeliveryGuyCurrentOrders', ':id') }}".replace(':id', id),
            type: 'GET',
            success: function(data) {
                if (data.length > 0) {
                    var table = '<div class="table-responsive"><table class="table table-striped">';
                    table += '<thead><tr><th>Order ID</th><th>Customer Name</th><th>Restaurant Name</th><th>Customer Address</th></thead><tbody>';
                    data.forEach(function(order) {
                        var order_url = "{{ route('admin.viewOrder', ':order_id') }}".replace(':order_id', order.unique_order_id);
                        table += '<tr>';
                        table += '<td><strong><a href="' + order_url + '" target="_blank">' + order.unique_order_id + '</a></strong></td>';
                        table += '<td>' + order.user.name + '</td>';
                        table += '<td>' + order.restaurant.name + '</td>';
                        table += '<td>' + order.address + '</td>';
                        table += '</tr>';
                    });
                    table += '</tbody></table></div>';
                    $('#deliveryGuyCurrentOrderModalBody').html(table);
                } else {
                    $('#deliveryGuyCurrentOrderModalBody').html('<h6>No current orders for this delivery guy.</h6>');
                }
                $('#deliveryGuyCurrentOrderModal').modal('show');
                $('#nearestDeliveryGuyModal').modal('hide');
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    $('#getDeliveryGuys').click(function() {
        $('#getDeliveryGuyButtonLoading').removeClass('d-none', true);
        $('#nearestDeliveryGuyModal').modal('show');
        $.ajax({
            type: "GET",
            url: "{{ route('admin.getNearestDeliveryGuys', $order->id) }}",
            success: function(response) {
                $('#getDeliveryGuyButtonLoading').addClass('d-none', true);
                console.log(response);
                var table = '<div class="table-responsive" style="padding: 0.75rem"><table id="deliveryGuysTable" class="table table-sm"><thead><tr><th class="d-none">ID</th><th>Name</th><th>Distance</th><th>Max Order Limit</th><th>Orders</th><th>Action</th></tr></thead><tbody>';
                var length = response.length > 10 ? 10 : response.length;
                for (var i = 0; i < length; i++) {
                    var deliveryGuy = response[i];
                    var id = deliveryGuy.delivery_guy_id;
                    var assignUrl = '{{ route('admin.assignDeliveryFromAdmin') }}';
                    table += '<tr>';
                    table += '<td class="d-none">' + deliveryGuy.delivery_guy_id + '</td><td>' + deliveryGuy.name + '</td><td>' + deliveryGuy.distance + ' km</td><td>' + deliveryGuy.max_order_limit + '</td><td><button type="button" class="deliveryGuyCurrentOrdersButton btn btn-sm btn-dark" data-id="' + deliveryGuy.delivery_guy_id + '">' + deliveryGuy.current_orders_count + '</button></td>';
                    table += '</tr>';
                }
                table += '</tbody></table></div>';
                $('#nearestDeliveryGuyModalBody').html("");
                $('#nearestDeliveryGuyModalBody').append(table);
            }
        });
    });

    $('#previousModal').click(function() {
        $('#deliveryGuyCurrentOrderModal').modal('hide');
        $('#nearestDeliveryGuyModal').modal('show');
    });
});
</script>
        @endsection
