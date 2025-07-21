<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Store/Restaurant Name</th>
            <th>Joined on</th>
            <th>Completed Orders</th>
            <th>Cancelled Orders</th>
            <th>Total Orders</th>
            <th>Net Earnings</th>
            <th>Admin Commission</th>
            <th>Total Amount</th>
            <th>Average Completion</th>
        </tr>
    </thead>
    <tbody>
        @php $totalTimeTaking = $amountCountDisplay = $orderCountDisplay = $cancelCountDisplay = $completedCountDisplay
        = $earningCountDisplay = $adminTotalEarn = $totalTimeCount = 0; @endphp

        @foreach ($restaurants as $key => $restaurant)
        @if($restaurant->is_accepted == '1')
        <tr>
            <td>{{ $restaurant->id }}</td>
            <td>{{ $restaurant->name }}</td>
            <td>{{ $restaurant->created_at->format('d-m-Y') }}</td>
            <td>
                {{ $restaurant->completedCount }}
                @php $completedCountDisplay = ($restaurant->completedCount + $completedCountDisplay); @endphp
            </td>
            <td>
                {{ $restaurant->cancelledCount }}
                @php $cancelCountDisplay = ($restaurant->cancelledCount + $cancelCountDisplay); @endphp
            </td>
            <td>
                {{ $restaurant->totalCount }}
                @php $orderCountDisplay = ($restaurant->totalCount + $orderCountDisplay); @endphp
            </td>
            <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->totalEarningData }}
                @php $earningCountDisplay = ($restaurant->totalEarningData + $earningCountDisplay); @endphp
            </td>
            <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->adminEarning }}
                @php $adminTotalEarn = ($restaurant->adminEarning + $adminTotalEarn); @endphp
            </td>
            <td>
                {{ config('setting.currencyFormat') }}{{ $restaurant->totalAmountData }}
                @php $amountCountDisplay = ($restaurant->totalAmountData + $amountCountDisplay); @endphp
            </td>
            <td>
                @if($restaurant->deliveryTime !== 0)
                @php $totalTimeTaking = ((float)$restaurant->deliveryTime + (float)$totalTimeTaking); $totalTimeCount++;
                @endphp
                {{$restaurant->deliveryTime}} minutes
                @endif
            </td>
        </tr>
        @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>TOTAL</th>
            <th></th>
            <th></th>
            <th>{{ $completedCountDisplay }}</th>
            <th>{{ $cancelCountDisplay }}</th>
            <th>{{ $orderCountDisplay }}</th>
            <th>{{ config('setting.currencyFormat') }}{{ $earningCountDisplay }}</th>
            <th>{{ config('setting.currencyFormat') }}{{ $adminTotalEarn }}</th>
            <th>{{ config('setting.currencyFormat') }}{{ $amountCountDisplay }}</th>
            @if($totalTimeCount !== 0)
            <th>{{number_format(($totalTimeTaking / $totalTimeCount),2) }} minutes</th>
            @else
            <th>{{$totalTimeTaking}} minutes</th>
            @endif
        </tr>
</table>