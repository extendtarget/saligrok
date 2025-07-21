<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Joined on</th>
            <th>Completed Orders</th>
            <th>Cancelled Orders</th>
            <th>Total Orders</th>
            <th>Total Tip</th>
            <th>Total Amount</th>
            <th>Average Completion</th>
            <th>Most Ordered Item</th>
            <th>Most Ordered Store</th>
            <th>Suggested Action</th>
        </tr>
    </thead>
    <tbody>
        @php
        $grandTotalDeliveredOrders = 0;   
        $grandTotalCancelledOrders = 0;   
        
        $grandTotalTipAmount = 0;   
        $grandTotalOrdersAmount = 0;   
        $grandTotalDeliveryTime = 0;

        $uniqueUserOrderDeliveries = 0;
        @endphp
    @foreach($users as $user)
        @php
        $deliveredOrders = $user->orders->where('orderstatus_id', 5)->count();
        $cancelledOrders = $user->orders->where('orderstatus_id', 6)->count();
        $totalOrders = $deliveredOrders + $cancelledOrders;

        $orders = $user->orders;

        $tipAmount = 0;
        $orderTotal = 0;
        $deliveryTime = 0;
        
        $orderItems = [];

        foreach($orders as $order) {
            $tipAmount += $order->tip_amount;
            if($order->orderstatus_id === 5) {
            $orderTotal += ($order->total - ($order->delivery_charges + $order->tip_amount));
            $deliveryTime += $order->updated_at->diffInMinutes($order->created_at);
            }
        }

        $averageDeliveryTime = $deliveryTime && $deliveredOrders ? ($deliveryTime / $deliveredOrders) : 0;
        $averageDeliveryTime = number_format((float)$averageDeliveryTime, 2, '.', '');

        $grandTotalDeliveredOrders += $deliveredOrders;
        $grandTotalCancelledOrders += $cancelledOrders;
        $grandTotalTipAmount += $tipAmount;

        $grandTotalOrdersAmount += $orderTotal;
        $grandTotalDeliveryTime += $averageDeliveryTime;

        if($deliveredOrders) {
            $uniqueUserOrderDeliveries += 1;
        }
        @endphp
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->created_at->format('d-m-Y') }}</td>
            <td>{{ $deliveredOrders }}</td>
            <td>{{ $cancelledOrders }}</td>
            <td>{{ $totalOrders }}</td>
            <td>{{ $tipAmount }}</td>
            <td>{{ $orderTotal }}</td>
            <td>{{ $averageDeliveryTime ? $averageDeliveryTime . ' minutes' : 'N/A' }}</td>
            <td>{{ !empty($user->favItemText) ? $user->favItemText : 'N/A' }}</td>
            <td>{{ !empty($user->favRestaurantText) ? $user->favRestaurantText : 'N/A' }}</td>
            <td>
            @if (($cancelledOrders > $deliveredOrders) && (($cancelledOrders - $deliveredOrders) > 15 ))
            BAN | {{ $user->email }} | {{ $user->phone }}
            @elseif (($cancelledOrders > $deliveredOrders) && (($cancelledOrders - $deliveredOrders) > 7 ))
            PAY ATTENTION
            @elseif (($deliveredOrders > $cancelledOrders) && (($deliveredOrders - $cancelledOrders) > 12 ))
            GIVE OFFER
            @else
            No Action Suggested
            @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>TOTAL</th>
            <th></th>
            <th></th>
            <th>{{ $grandTotalDeliveredOrders }}</th>
            <th>{{ $grandTotalCancelledOrders }}</th>
            <th>{{ $grandTotalDeliveredOrders + $grandTotalCancelledOrders }}</th>
            <th>{{ $grandTotalTipAmount }}</th>
            <th>{{ $grandTotalOrdersAmount }}</th>
            <th>{{ $grandTotalDeliveryTime && $uniqueUserOrderDeliveries ? number_format((float) ($grandTotalDeliveryTime / $uniqueUserOrderDeliveries), 2) : 0 }} minutes</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </tfoot>
</table>