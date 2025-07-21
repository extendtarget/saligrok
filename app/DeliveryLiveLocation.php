<?php

namespace App;

use Carbon\Carbon;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Database\Eloquent\Model;

class DeliveryLiveLocation extends Model
{
    protected $firebase;

    public function __construct()
    {
        $dir = base_path('service-account.json');
        $serviceAccount = ServiceAccount::fromJsonFile($dir);
        $this->firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri(config('setting.firebaseRealtimeDatabaseUrl'))
            ->create();
    }

    // public function getDeliveryLiveLocation($order)
    // {
    //     // if ($_SERVER['SERVER_NAME'] != 'zustel.com') {
    //     //     return response()->json(['message' => 'Invalid Location file configuration']);
    //     // }

    //     if (config('setting.iHaveFoodomaaDeliveryApp') != "true") {
    //         if ($order) {
    //             $deliveryUserId = $order->accept_delivery->user->id;
    //             $deliveryUser = User::with('delivery_guy_detail')->find($deliveryUserId);

    //             if ($deliveryUser->delivery_guy_detail) {
    //                 return response()->json($deliveryUser->delivery_guy_detail);
    //             }
    //         }
    //     } else {
    //         $deliveryUserId = $order->accept_delivery->user->id;
    //         $deliveryUser = User::with('delivery_guy_detail')->find($deliveryUserId);

    //         $database = $this->firebase->getDatabase();
    //         $newPost = $database
    //             ->getReference('User')
    //             ->getChild($deliveryUser->id);

    //         $newPostData = $newPost->getValue();

    //         if ($newPostData) {
    //             $latitude = $newPostData['latitude'];
    //             $longitude = $newPostData['longitude'];
    //             $heading = $newPostData['heading'] ?? 90;

    //             if ($latitude !== $deliveryUser->delivery_guy_detail->delivery_lat) {
    //                 $deliveryUser->delivery_guy_detail->delivery_lat = $latitude;
    //             }

    //             if ($longitude !== $deliveryUser->delivery_guy_detail->delivery_long) {
    //                 $deliveryUser->delivery_guy_detail->delivery_long = $longitude;
    //             }

    //             if ($heading !== $deliveryUser->delivery_guy_detail->heading) {
    //                 $deliveryUser->delivery_guy_detail->heading = $heading;
    //             }

    //             $deliveryUser->delivery_guy_detail->save();

    //             return response()->json($deliveryUser->delivery_guy_detail);
    //         }
    //     }
    //     return response()->json($deliveryUser->delivery_guy_detail);
    // }
    // aya
    public function getDeliveryLiveLocation($order)
    {
        
        if (!$order || !$order->accept_delivery) {
            \Log::warning("Order or accept_delivery is missing for order ID: " . ($order->id ?? 'unknown'));
            return response()->json(['message' => 'No delivery assigned for this order'], 400);
        }
    
        
        if (!$order->accept_delivery->user) {
            \Log::warning("User data missing for delivery of order ID: {$order->id}");
            return response()->json(['message' => 'Delivery user not found'], 400);
        }
    
        if (config('setting.iHaveFoodomaaDeliveryApp') != "true") {
            $deliveryUserId = $order->accept_delivery->user->id;
            $deliveryUser = User::with('delivery_guy_detail')->find($deliveryUserId);
    
            if ($deliveryUser->delivery_guy_detail) {
                return response()->json($deliveryUser->delivery_guy_detail);
            }
            \Log::warning("Delivery guy details not found for user ID: {$deliveryUserId}");
            return response()->json(['message' => 'Delivery guy details not found'], 400);
        } else {
            $deliveryUserId = $order->accept_delivery->user->id;
            $deliveryUser = User::with('delivery_guy_detail')->find($deliveryUserId);
    
            $database = $this->firebase->getDatabase();
            $newPost = $database
                ->getReference('User')
                ->getChild($deliveryUser->id);
    
            $newPostData = $newPost->getValue();
    
            if ($newPostData) {
                $latitude = $newPostData['latitude'];
                $longitude = $newPostData['longitude'];
                $heading = $newPostData['heading'] ?? 90;
    
                if ($latitude !== $deliveryUser->delivery_guy_detail->delivery_lat) {
                    $deliveryUser->delivery_guy_detail->delivery_lat = $latitude;
                }
    
                if ($longitude !== $deliveryUser->delivery_guy_detail->delivery_long) {
                    $deliveryUser->delivery_guy_detail->delivery_long = $longitude;
                }
    
                if ($heading !== $deliveryUser->delivery_guy_detail->heading) {
                    $deliveryUser->delivery_guy_detail->heading = $heading;
                }
    
                $deliveryUser->delivery_guy_detail->save();
    
                return response()->json($deliveryUser->delivery_guy_detail);
            }
            \Log::warning("Firebase data not found for user ID: {$deliveryUserId}");
            return response()->json(['message' => 'Firebase location data not found'], 400);
        }
    }

    public function updateDeliveryGuysLocation($deliveryGuys, $zone_id = null)
    {
        if (config('setting.iHaveFoodomaaDeliveryApp') != "true") {
            return false;
        }

        $database = $this->firebase->getDatabase();
        $updates = [];

        foreach ($deliveryGuys as $deliveryUser) {
            $newPost = $database->getReference('User')->getChild($deliveryUser->id);
            $newPostData = $newPost->getValue();

            if ($newPostData) {
                $latitude = $newPostData['latitude'];
                $longitude = $newPostData['longitude'];
                $heading = $newPostData['heading'] ?? 90;

                if ($latitude !== $deliveryUser->delivery_guy_detail->delivery_lat) {
                    $updates[$deliveryUser->id]['delivery_lat'] = $latitude;
                }

                if ($longitude !== $deliveryUser->delivery_guy_detail->delivery_long) {
                    $updates[$deliveryUser->id]['delivery_long'] = $longitude;
                }

                if ($heading !== $deliveryUser->delivery_guy_detail->heading) {
                    $updates[$deliveryUser->id]['heading'] = $heading;
                }
            }
        }

        // Flatten the multidimensional $updates array
        $flattenedUpdates = [];
        foreach ($updates as $update) {
            $flattenedUpdates = array_merge($flattenedUpdates, $update);
        }

        // Perform batch update using update() method
        if (!empty($flattenedUpdates)) {
            $deliveryGuyDetail = new DeliveryGuyDetail();
            $deliveryGuyDetail->whereIn('id', array_keys($updates))->update($flattenedUpdates);
        }

        return true;
    }
}
