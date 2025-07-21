@extends('admin.layouts.master')
@section('title')
Delivery Guy Earnings By Date - Reports
@endsection
@section('content')
<style>
    /* General Styling */
    .page-header {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-title h4 {
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #333;
    }

    /* Filter Form */
    .filter-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .filter-label {
        font-size: 0.9rem;
        font-weight: 500;
        margin: 0 0.5rem;
        color: #555;
    }

    .form-control-sm, .btn-sm {
        border-radius: 0.25rem;
        font-size: 0.85rem;
    }

    .btn-primary, .btn-secondary {
        transition: background-color 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #005f99;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    /* Table Styling */
    .table {
        background-color: #fff;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .table th, .table td {
        vertical-align: middle;
        font-size: 0.9rem;
        padding: 0.75rem;
        text-align: center;
    }

    .table thead {
        background-color: #343a40;
        color: #fff;
    }

    .table tbody tr:hover {
        background-color: #f1f3f5;
    }

    .table tfoot th {
        background-color: #e9ecef;
        font-weight: 600;
    }

    /* Select2 Customization */
    .select2-container--default .select2-selection--single {
        border-radius: 0.25rem;
        height: 31px;
        display: flex;
        align-items: center;
    }

    .select2-selection__rendered {
        padding: 0 0.875rem !important;
        font-size: 0.85rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .filter-container {
            grid-template-columns: 1fr;
        }

        .page-title h4 {
            font-size: 1.1rem;
        }

        .table th, .table td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
    }
</style>

<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4>
                <i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold">Delivery Guy Earnings By Date Report</span>
                <span class="badge badge-primary badge-pill animated flipInX"></span>
            </h4>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.deliveryGuyEarningsByDateReport') }}" method="GET">
                <div class="filter-container">
                    <div>
                        <select class="form-control form-control-sm selectDB" name="deliver_by_id">
                            <option value="">Select Delivery Boy</option>
                            @foreach ($delivery_details as $delivery_boy)
                                <option value="{{ $delivery_boy->id }}" @if(app('request')->input('deliver_by_id') == $delivery_boy->id) selected @endif class="text-capitalize">
                                    {{ $delivery_boy->id }} - {{ $delivery_boy->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="filter-label">From</label>
                        <input type="text" class="form-control-sm form-control daterange-single" name="report_start_date"
                            value="{{ @$search_data['start_date'] }}" placeholder="Start Date" />
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="filter-label">To</label>
                        <input type="text" class="form-control-sm form-control daterange-single" name="report_end_date"
                            value="{{ @$search_data['end_date'] }}" placeholder="End Date" />
                    </div>
                    <div>
                        <select class="form-control form-control-sm" name="record_length">
                            <option value="25" @if (@$search_data['record_length'] == 25) selected @endif>25</option>
                            <option value="50" @if (@$search_data['record_length'] == 50) selected @endif>50</option>
                            <option value="100" @if (@$search_data['record_length'] == 100) selected @endif>100</option>
                            <option value="250" @if (@$search_data['record_length'] == 250) selected @endif>250</option>
                            <option value="500" @if (@$search_data['record_length'] == 500) selected @endif>500</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="icon-search4 mr-1"></i> Search
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearFilters()">Clear</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="printButton" class="btn btn-sm btn-secondary"><i class="icon-printer"></i> Print</button>
                        <a href="{{ route('admin.exportReport', 'delivery_details') }}?deliver_by_id={{ @$search_data['deliver_by_id'] }}&report_start_date={{ @$search_data['start_date'] }}&report_end_date={{ @$search_data['end_date'] }}"
                            class="btn btn-sm btn-secondary"><i class="icon-file-excel"></i> Export</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Driver ID</th>
                            <th>Driver Name</th>
                            <th>Total Earnings</th>
                            <th>Commission Earnings</th>
                            <th>Tip Earnings</th>
                            <th>Salary Earnings</th>
                            <th>Motobox Wallet Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalEarningsNet = 0;
                            $totalCommissionNet = 0;
                            $totalTipNet = 0;
                            $totalSalaryNet = 0;
                            $totalMotoboxWalletNet = 0;
                        @endphp
                        @foreach ($drivers as $driver)
                            @php
                                $totalEarningsNet += $driver['total_earnings'];
                                $totalCommissionNet += $driver['total_commission'];
                                $totalTipNet += $driver['total_tip'];
                                $totalSalaryNet += $driver['total_salary'];
                                $totalMotoboxWalletNet += $driver['motobox_wallet'];
                            @endphp
                            <tr>
                                <td>{{ $driver['id'] }}</td>
                                <td>{{ $driver['name'] }}</td>
                                <td>{{ config('setting.currencyFormat') }}{{ number_format($driver['total_earnings'], 2) }}</td>
                                <td>{{ config('setting.currencyFormat') }}{{ number_format($driver['total_commission'], 2) }}</td>
                                <td>{{ config('setting.currencyFormat') }}{{ number_format($driver['total_tip'], 2) }}</td>
                                <td>{{ config('setting.currencyFormat') }}{{ number_format($driver['total_salary'], 2) }}</td>
                                <td>{{ config('setting.currencyFormat') }}{{ number_format($driver['motobox_wallet'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>TOTAL</th>
                            <th></th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($totalEarningsNet, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($totalCommissionNet, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($totalTipNet, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($totalSalaryNet, 2) }}</th>
                            <th>{{ config('setting.currencyFormat') }}{{ number_format($totalMotoboxWalletNet, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
                <div class="mt-3">
                    {{ $drivers->appends($_GET)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#printButton').on('click', function () {
        $('.table').printThis();
    });

    $('.daterange-single').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    $('.selectDB').select2({
        placeholder: 'Select Delivery Guy',
        allowClear: true,
    });

    function clearFilters() {
        document.querySelector('input[name="report_start_date"]').value = '';
        document.querySelector('input[name="report_end_date"]').value = '';
        document.querySelector('select[name="deliver_by_id"]').value = '';
        document.querySelector('select[name="record_length"]').value = '50';
        document.querySelector('form').submit();
    }
</script>
@endsection