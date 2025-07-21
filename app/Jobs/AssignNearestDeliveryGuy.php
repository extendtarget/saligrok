<?php

namespace App\Jobs;

use App\Sms;
use App\User;
use App\Order;
use Carbon\Carbon;
use App\PushNotify;
use App\SocketPush;
use App\AcceptDelivery;
use App\DeliveryCollection;
use App\DeliveryLiveLocation;
use Illuminate\Bus\Queueable;
use App\Jobs\SendWazoneMessage;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\AdminController;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Wazone\Http\Controllers\MessageController;
use Spatie\Activitylog\Models\Activity as ModelsActivity;

class AssignNearestDeliveryGuy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = Order::where('id', $order->id)->with('restaurant.zone')->first();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order->orderstatus_id == 2 && $this->order->delivery_type == 1) {
            $deliveryGuyData = (new AdminController)->getNearestDeliveryGuys($this->order->id, "job");
            $order_id = $this->order->id;
            $deliveryUserId = null;
            $condition = null;

            $rejectedLogs = ModelsActivity::where('subject_type', 'App\Order')->where('subject_id', $this->order->id)->where('description', 'LIKE', "%Delivery Rejected%")->get();

            if ($rejectedLogs) {
                foreach ($rejectedLogs as $log) {
                    $rejectDeliveryGuy = $log->causer_id;
                    foreach ($deliveryGuyData as $key => $deliveryGuy) {
                        if ($deliveryGuy['delivery_guy_id'] == $rejectDeliveryGuy) {
                            unset($deliveryGuyData[$key]);
                            break;
                        }
                    }
                }
            }

            // PRIORITY IN DISTRIBUTION 1
            // Driver ONLINE + Driver available + ROUTE (not radius) from Driver to Restaurant is not more than 2 km
            foreach ($deliveryGuyData as $deliveryGuy) {
                if ($deliveryGuy['current_orders_count'] == 0 && (float)$deliveryGuy['distance'] <= 2) {
                    if ($this->checkUserInLimit($deliveryGuy['delivery_guy_id'])) {
                        $deliveryUserId = $deliveryGuy['delivery_guy_id'];
                        $condition = 1;
                        break;
                    }
                }
            }

            // PRIORITY IN DISTRIBUTION 2
            // Driver ONLINE + Driver has an order in "Picked up" status + ROUTE (not radius) from -->
            // <-- Customer's Location in the current order to Restaurant of the next order is not more than 2 km
            if (is_null($deliveryUserId)) {
                foreach ($deliveryGuyData as $deliveryGuy) {
                    if ($deliveryGuy['current_orders_count'] == 1) {
                        $activeOrder = $deliveryGuy['current_orders'][0];
                        $activeOrderLocationData = json_decode($activeOrder->location, true);
                        $distance = getOsmDistance($this->order->restaurant->latitude, $this->order->restaurant->longitude, $activeOrderLocationData['lat'], $activeOrderLocationData['lng']);
                        $distance = (float) number_format($distance, 1);
                        if ($distance <= 1) {
                            if ($this->checkUserInLimit($deliveryGuy['delivery_guy_id'])) {
                                $deliveryUserId = $deliveryGuy['delivery_guy_id'];
                                $condition = 2;
                                break;
                            }
                        }
                    }
                }
            }

            // PRIORITY IN DISTRIBUTION 3
            // Nearest available driver within 4km and below max order limit
            if (is_null($deliveryUserId)) {
                foreach ($deliveryGuyData as $deliveryGuy) {
                    if (!isset($deliveryGuy['max_order_limit'])) {
                        $deliveryGuy['max_order_limit'] = 1;
                    }
                    if (($deliveryGuy['max_order_limit'] > $deliveryGuy['current_orders_count']) && (float)$deliveryGuy['distance'] <= 5) {
                        if ($this->checkUserInLimit($deliveryGuy['delivery_guy_id'])) {
                            $deliveryUserId = $deliveryGuy['delivery_guy_id'];
                            $condition = 3;
                            break;
                        }
                    }
                }
            }

            if (is_null($deliveryUserId)) {
                // sendPushNotificationToDelivery($this->order->restaurant->id, $this->order);
                // sendSmsToDelivery($this->order->restaurant->id);
                AssignNearestDeliveryGuy::dispatch($this->order)->delay(60);
                return;
            }

            $deliveryUser = User::where('id', $deliveryUserId)->first();
            if (!$deliveryUser) {
                Log::error("Delivery Guy not found");
                return;
            }

            DB::beginTransaction();
            try {

                $assignment = new AcceptDelivery;
                $assignment->order_id = $this->order->id;
                $assignment->user_id = $deliveryUser->id;
                $assignment->customer_id = $this->order->user_id;
                $assignment->is_complete = 0;
                $assignment->created_at = Carbon::now();
                $assignment->updated_at = Carbon::now();
                $assignment->save();

                $location_data = (new DeliveryLiveLocation)->getDeliveryLiveLocation($this->order)->getContent();
                $customer_location = json_decode($this->order->location);
                $assignment->location_data = json_encode(
                    [
                        'lat' => json_decode($location_data)->delivery_lat,
                        'long' => json_decode($location_data)->delivery_long,
                        'heading' => json_decode($location_data)->heading,
                        'store_distance' => getOsmDistance($this->order->restaurant->latitude, $this->order->restaurant->longitude, json_decode($location_data)->delivery_lat, json_decode($location_data)->delivery_long),
                        'customer_distance' => getOsmDistance(json_decode($location_data)->delivery_lat, json_decode($location_data)->delivery_long, $customer_location->lat, $customer_location->lng),
                        'condition' => $condition,
                    ]
                );
                $assignment->save();

                SendWazoneMessage::dispatch([$deliveryUser->id], null, (new MessageController), getOrderParams($this->order), $this->order->orderstatus_id);

                $this->order->orderstatus_id = 3;
                $this->order->save();

                activity()
                    ->performedOn($this->order)
                    ->causedBy(User::find(1))
                    ->withProperties(['type' => 'Order_Assigned'])->log('Order auto-assigned to Nearest Delivery Guy');

                DB::commit();

                if (config('setting.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('3', $this->order->user_id, $this->order->unique_order_id);
                }

                // Send SMS Notification to Delivery Guy
                if (config('setting.smsDeliveryNotify') == 'true') {
                    $message = config('setting.defaultSmsDeliveryMsg');
                    $otp = null;
                    $smsnotify = new Sms();
                    $smsnotify->processSmsAction('OD_NOTIFY', $deliveryUser->phone, $otp, $message);
                }

                // Send Push Notification to Delivery Guy
                if (config('setting.enablePushNotificationOrders') == 'true') {
                    if (config('setting.hasSocketPush') != 'true') {
                        $notify = new PushNotify();
                        $notify->sendPushNotification('TO_DELIVERY', $deliveryUser->id, $this->order->unique_order_id);
                    } else {
                        if (config('setting.iHaveFoodomaaDeliveryApp') == "true") {
                            stopPlayingNotificationSoundDeliveryAppHelper($this->order);
                            $deliveryGuyIds = [$deliveryUser->id];
                            $notify = new SocketPush();
                            $notify->pushNewOrder($this->order->unique_order_id, $deliveryGuyIds);
                        }
                    }
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e->getMessage());
            } catch (\Throwable $th) {
                DB::rollBack();
                Log::error($th->getMessage());
            }
        }
    }

    private function checkUserInLimit($deliveryUserId)
    {
        //Check Order Limit and Cash in Hand Limit
        $deliveryUser = User::where('id', $deliveryUserId)->first();
        if (!$deliveryUser) {
            Log::error("Delivery Guy not found");
            return;
        }

        $delivery_collection = DeliveryCollection::where('user_id', $deliveryUser->id)->first();

        $inhand_cash = $delivery_collection ? $delivery_collection->amount : 0;
        $cash_limit = $deliveryUser->delivery_guy_detail->cash_limit;

        $max_accept_delivery_limit = $deliveryUser->delivery_guy_detail->max_accept_delivery_limit;

        $nonCompleteOrders = AcceptDelivery::where('user_id', $deliveryUser->id)
            ->where('is_complete', 0)
            ->with('order', 'order.restaurant')
            ->whereHas('order', function ($query) {
                $query->whereNotIn('orderstatus_id', [5, 6]);
            })
            ->get();

        $countNonCompleteOrders = count($nonCompleteOrders);

        $isUnderQueueLimit = false;
        if ($countNonCompleteOrders < $max_accept_delivery_limit) {
            $isUnderQueueLimit = true;
        }

        if ($cash_limit == 0) {
            $is_in_limit = true;
        } else {
            $is_in_limit = $inhand_cash < $cash_limit ?  true : false;
        }

        if ($is_in_limit || $isUnderQueueLimit) {
            return true;
        } else {
            return false;
        }
    }
}
