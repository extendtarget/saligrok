<?php

namespace App\Http\Controllers;

use Cache;
use App\Item;
use Carbon\Carbon;
use App\Restaurant;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Collection;
use Modules\SuperCache\SuperCache;
use Nwidart\Modules\Facades\Module;
use Modules\DeliveryAreaPro\DeliveryArea;

class RestaurantController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getDeliveryRestaurants(Request $request)
    {
        // Cache::forget('stores-delivery-active');
        // Cache::forget('stores-delivery-inactive');
        // die();

        // get all active restauants doing delivery
        if (Cache::has('stores-delivery-active')) {
            $restaurants = Cache::get('stores-delivery-active');
        } else {
            $restaurants = Restaurant::where('is_accepted', '1')
                ->where('is_active', 1)
                ->whereIn('delivery_type', [1, 3])
                ->with('delivery_areas', 'ratings')
                ->with(['coupons' => function ($query) {
                    $query->where('is_active', 1)
                        ->where('show_in_home', 1)
                        ->where('expiry_date', '>', Carbon::now())
                        ->whereColumn('count', '<', 'max_count');
                }])
                ->ordered()
                ->get();
            $this->processSuperCache('stores-delivery-active', $restaurants);
        }

        //Create a new Laravel collection from the array data
        $nearMe = new Collection();

        foreach ($restaurants as $restaurant) {

            if ($restaurant->coupons->isEmpty()) {
                $restaurant->highest_discount_coupon = null; // No coupon available
            } else {

                $highestCoupon = $restaurant->coupons->max(function ($coupon) {
                    if ($coupon->type === 'fixed') {
                        return $coupon->discount;
                    } elseif ($coupon->type === 'percentage' && !is_null($coupon->max_limit)) {
                        return $coupon->max_limit;
                    } else {
                        return $coupon->discount;
                    }
                });

                $restaurant->highest_discount_coupon = $restaurant->coupons->first(function ($coupon) use ($highestCoupon) {
                    if ($coupon->type === 'fixed') {
                        return $coupon->discount === $highestCoupon;
                    } elseif ($coupon->type === 'percentage' && !is_null($coupon->max_limit)) {
                        return $coupon->max_limit === $highestCoupon;
                    } else {
                        return $coupon->discount === $highestCoupon;
                    }
                });
            }
            $restaurant->couponCount = count($restaurant->coupons);

            $distance = getDistance($request->latitude, $request->longitude, $restaurant->latitude, $restaurant->longitude);
            $restaurant->distance = $distance;
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $nearMe->push($restaurant);
            }
        }

        $nearMe = $nearMe->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'distance', 'custom_featured_name', 'custom_message_on_list', 'couponCount', 'coupons', 'highest_discount_coupon']);
        });

        $nearMe = $nearMe->toArray();

        if (config('setting.randomizeStores') == 'true') {
            shuffle($nearMe);
            usort($nearMe, function ($left, $right) {
                return $right['is_featured'] - $left['is_featured'];
            });
        }

        if (config('setting.sortDeliveryStoresByDistance') == 'true') {
            $nearMe = collect($nearMe)->sortBy('distance')->toArray();
        }

        if (Cache::has('stores-delivery-inactive')) {
            $inactiveRestaurants = Cache::get('stores-delivery-inactive');
        } else {
            $inactiveRestaurants = Restaurant::where('is_accepted', '1')
                ->where('is_active', 0)
                ->whereIn('delivery_type', [1, 3])
                ->with('delivery_areas', 'ratings')
                ->ordered()
                ->get();
            $this->processSuperCache('stores-delivery-inactive', $inactiveRestaurants);
        }

        $nearMeInActive = new Collection();
        foreach ($inactiveRestaurants as $inactiveRestaurant) {
            $distance = getDistance($request->latitude, $request->longitude, $inactiveRestaurant->latitude, $inactiveRestaurant->longitude);
            $inactiveRestaurant->distance = $distance;
            $check = $this->checkOperation($request->latitude, $request->longitude, $inactiveRestaurant);
            if ($check) {
                $nearMeInActive->push($inactiveRestaurant);
            }
        }
        $nearMeInActive = $nearMeInActive->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'distance', 'custom_featured_name', 'custom_message_on_list']);
        });
        $nearMeInActive = $nearMeInActive->toArray();

        if (config('setting.sortDeliveryStoresByDistance') == 'true') {
            $nearMeInActive = collect($nearMeInActive)->sortBy('distance')->toArray();
        }

        $merged = array_merge($nearMe, $nearMeInActive);

        return response()->json($merged);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getSelfPickupRestaurants(Request $request)
    {
        // sleep(500);
        // get all active restauants doing selfpickups
        if (Cache::has('stores-selfpickup-active')) {
            $restaurants = Cache::get('stores-selfpickup-active');
        } else {
            $restaurants = Restaurant::where('is_accepted', '1')
                ->where('is_active', 1)
                ->whereIn('delivery_type', [2, 3])
                ->with('delivery_areas', 'ratings')
                ->with(['coupons' => function ($query) {
                    $query->where('is_active', 1)
                        ->where('show_in_home', 1)
                        ->where('expiry_date', '>', Carbon::now())
                        ->whereColumn('count', '<', 'max_count');
                }])
                ->ordered()
                ->get();
            $this->processSuperCache('stores-selfpickup-active', $restaurants);
        }

        //Create a new Laravel collection from the array data
        $nearMe = new Collection();

        foreach ($restaurants as $restaurant) {
            if ($restaurant->coupons->isEmpty()) {
                $restaurant->highest_discount_coupon = null; // No coupon available
            } else {

                $highestCoupon = $restaurant->coupons->max(function ($coupon) {
                    if ($coupon->type === 'fixed') {
                        return $coupon->discount;
                    } elseif ($coupon->type === 'percentage' && !is_null($coupon->max_limit)) {
                        return $coupon->max_limit;
                    } else {
                        return $coupon->discount;
                    }
                });

                $restaurant->highest_discount_coupon = $restaurant->coupons->first(function ($coupon) use ($highestCoupon) {
                    if ($coupon->type === 'fixed') {
                        return $coupon->discount === $highestCoupon;
                    } elseif ($coupon->type === 'percentage' && !is_null($coupon->max_limit)) {
                        return $coupon->max_limit === $highestCoupon;
                    } else {
                        return $coupon->discount === $highestCoupon;
                    }
                });
            }
            $restaurant->couponCount = count($restaurant->coupons);

            $distance = getDistance($request->latitude, $request->longitude, $restaurant->latitude, $restaurant->longitude);
            // if ($distance <= $restaurant->delivery_radius) {
            //     $nearMe->push($restaurant);
            // }
            $restaurant->distance = $distance;
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $nearMe->push($restaurant);
            }
        }

        $nearMe = $nearMe->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'distance', 'custom_featured_name', 'custom_message_on_list', 'coupons', 'couponCount', 'highest_discount_coupon']);
        });

        $nearMe = $nearMe->toArray();
        if (config('setting.randomizeStores') == 'true') {
            shuffle($nearMe);
            usort($nearMe, function ($left, $right) {
                return $right['is_featured'] - $left['is_featured'];
            });
        }

        if (config('setting.sortSelfpickupStoresByDistance') == 'true') {
            $nearMe = collect($nearMe)->sortBy('distance')->toArray();
        }

        if (Cache::has('stores-selfpickup-inactive')) {
            $inactiveRestaurants = Cache::get('stores-selfpickup-inactive');
        } else {
            $inactiveRestaurants = Restaurant::where('is_accepted', '1')
                ->where('is_active', 0)
                ->whereIn('delivery_type', [2, 3])
                ->with('delivery_areas')
                ->ordered()
                ->get();
            $this->processSuperCache('stores-selfpickup-inactive', $inactiveRestaurants);
        }

        $nearMeInActive = new Collection();
        foreach ($inactiveRestaurants as $inactiveRestaurant) {
            $distance = getDistance($request->latitude, $request->longitude, $inactiveRestaurant->latitude, $inactiveRestaurant->longitude);
            // if ($distance <= $inactiveRestaurant->delivery_radius) {
            //     $nearMeInActive->push($inactiveRestaurant);
            // }
            $inactiveRestaurant->distance = $distance;
            $check = $this->checkOperation($request->latitude, $request->longitude, $inactiveRestaurant);
            if ($check) {
                $nearMeInActive->push($inactiveRestaurant);
            }
        }
        $nearMeInActive = $nearMeInActive->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'distance', 'custom_featured_name', 'custom_message_on_list']);
        });
        $nearMeInActive = $nearMeInActive->toArray();

        if (config('setting.sortSelfpickupStoresByDistance') == 'true') {
            $nearMeInActive = collect($nearMeInActive)->sortBy('distance')->toArray();
        }

        $merged = array_merge($nearMe, $nearMeInActive);

        return response()->json($merged);
    }

    /**
     * @param $slug
     */
    public function getRestaurantInfo($slug)
    {
        $restaurantInfo = Restaurant::where('slug', $slug)
            ->with(['coupons' => function ($query) {
                $query->where('is_active', 1)
                    ->where('show_in_restaurant', 1)
                    ->where('expiry_date', '>', Carbon::now())
                    ->whereColumn('count', '<', 'max_count');
            }, 'zone'])->first();
        $restaurantInfo->avgRating = storeAvgRating($restaurantInfo->ratings);
        $restaurantInfo->countRating = $restaurantInfo->ratings->count('rating_store');
        if (!$restaurantInfo->is_accepted) {
            $restaurantInfo->is_active = 0;
        }
        $restaurantInfo->makeHidden(['delivery_areas', 'ratings', 'commission_rate']);
        $restaurantInfo->is_favorited = false;
        return response()->json($restaurantInfo);
    }

    /**
     * @param $slug
     * @param Request $request
     */
    public function getRestaurantInfoWithFavourite($slug, Request $request)
    {
        $restaurantInfo = Restaurant::where('slug', $slug)->with(['coupons' => function ($query) {
            $query->where('is_active', 1)
                ->where('show_in_restaurant', 1)
                ->where('expiry_date', '>', Carbon::now())
                ->whereColumn('count', '<', 'max_count');
        }, 'zone'])->first();

        $restaurantInfo->avgRating = storeAvgRating($restaurantInfo->ratings);
        $restaurantInfo->countRating = $restaurantInfo->ratings->count('rating_store');
        $restaurantInfo->couponCount = count($restaurantInfo->coupons);

        if (!$restaurantInfo->is_accepted) {
            $restaurantInfo->is_active = 0;
        }

        $restaurantInfo->makeHidden(['delivery_areas', 'ratings', 'commission_rate']);
        $restaurantInfo->is_favorited = $restaurantInfo->isFavorited();
        return response()->json($restaurantInfo);
    }
    /**
     * @param $id
     */
    public function getRestaurantInfoById($id)
    {
        $restaurant = Restaurant::where('id', $id)->with(['coupons' => function ($query) {
            $query->where('is_active', 1)
                ->where('show_in_restaurant', 1)
                ->where('expiry_date', '>', Carbon::now())
                ->whereColumn('count', '<', 'max_count');
        }, 'zone'])->first();
        $restaurant->avgRating = storeAvgRating($restaurant->ratings);
        $restaurant->countRating = $restaurant->ratings->count('rating_store');
        $restaurant->couponCount = count($restaurant->coupons);

        if (!$restaurant->is_accepted) {
            $restaurant->is_active = 0;
        }

        $restaurant->makeHidden(['delivery_areas', 'ratings', 'commission_rate']);

        return response()->json($restaurant);
    }

    /**
     * @param $slug
     */
    public function getRestaurantItems($slug)
    {
        // Cache::forget('store-info-' . $slug);
        Cache::forever('items-cache', 'true');
        if (Cache::has('store-info-' . $slug)) {
            $restaurant = Cache::get('store-info-' . $slug);
        } else {
            $restaurant = Restaurant::where('slug', $slug)->first();
            $this->processSuperCache('store-info-' . $slug, $restaurant);
        }

        // Cache::forget('items-recommended-' . $restaurant->id);
        // Cache::forget('items-all-' . $restaurant->id);

        if (Cache::has('items-recommended-' . $restaurant->id) && Cache::has('items-all-' . $restaurant->id)) {
            $recommended = Cache::get('items-recommended-' . $restaurant->id);
            $array = Cache::get('items-all-' . $restaurant->id);
        } else {
            if (config('setting.showInActiveItemsToo') == 'true') {
                $recommended = Item::where('restaurant_id', $restaurant->id)->where('is_recommended', '1')
                    ->whereHas('item_category', function ($q) {
                        $q->where('is_enabled', '1');
                    })
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get();

                $items = Item::where('restaurant_id', $restaurant->id)
                    ->join('item_categories', function ($join) {
                        $join->on('items.item_category_id', '=', 'item_categories.id');
                        $join->where('is_enabled', '1');
                    })
                    ->orderBy('item_categories.order_column', 'asc')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get(array('items.*', 'item_categories.name as category_name'));
            } else {
                $recommended = Item::where('restaurant_id', $restaurant->id)->where('is_recommended', '1')
                    ->whereHas('item_category', function ($q) {
                        $q->where('is_enabled', '1');
                    })
                    ->where('is_active', '1')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get();

                $items = Item::where('restaurant_id', $restaurant->id)
                    ->join('item_categories', function ($join) {
                        $join->on('items.item_category_id', '=', 'item_categories.id');
                        $join->where('is_enabled', '1');
                    })
                    ->orderBy('item_categories.order_column', 'asc')
                    ->where('is_active', '1')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get(array('items.*', 'item_categories.name as category_name'));
            }

            $items = json_decode($items, true);

            $array = [];
            foreach ($items as $item) {
                $array[$item['category_name']][] = $item;
            }

            $this->processSuperCache('items-recommended-' . $restaurant->id, $recommended);
            $this->processSuperCache('items-all-' . $restaurant->id, $array);
        }

        return response()->json(array(
            'recommended' => $recommended,
            'items' => $array,
        ));
    }
    
    
    public function getRestaurantItemsWithDetails($slug)
    {
        // Cache::forget('store-info-' . $slug);
        Cache::forever('items-cache', 'true');
        if (Cache::has('store-info-' . $slug)) {
            $restaurant = Cache::get('store-info-' . $slug);
        } else {
            $restaurant = Restaurant::where('slug', $slug)->first();
            $this->processSuperCache('store-info-' . $slug, $restaurant);
        }

        // Cache::forget('items-recommended-' . $restaurant->id);
        // Cache::forget('items-all-' . $restaurant->id);

        if (Cache::has('items-recommended-' . $restaurant->id) && Cache::has('items-all-' . $restaurant->id)) {
            $recommended = Cache::get('items-recommended-' . $restaurant->id);
            $array = Cache::get('items-all-' . $restaurant->id);
        } else {
            if (config('setting.showInActiveItemsToo') == 'true') {
                $recommended = Item::where('restaurant_id', $restaurant->id)->where('is_recommended', '1')
                    ->whereHas('item_category', function ($q) {
                        $q->where('is_enabled', '1');
                    })
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get();

                $items = Item::where('restaurant_id', $restaurant->id)
                    ->join('item_categories', function ($join) {
                        $join->on('items.item_category_id', '=', 'item_categories.id');
                        // $join->where('is_enabled', '1');
                    })
                    ->orderBy('item_categories.order_column', 'asc')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get(array('items.*', 'item_categories.name as category_name'));
            } else {
                $recommended = Item::where('restaurant_id', $restaurant->id)->where('is_recommended', '1')
                    ->whereHas('item_category', function ($q) {
                        // $q->where('is_enabled', '1');
                    })
                    ->where('is_active', '1')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get();

                $items = Item::where('restaurant_id', $restaurant->id)
                    ->join('item_categories', function ($join) {
                        $join->on('items.item_category_id', '=', 'item_categories.id');
                        // $join->where('is_enabled', '1');
                    })
                    ->orderBy('item_categories.order_column', 'asc')
                    ->where('is_active', '1')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->ordered()
                    ->get(array('items.*', 'item_categories.name as category_name', 'item_categories.is_enabled as is_enabled', 'item_categories.user_id as user_id'));
            }

            $items = json_decode($items, true);


            $array = [];
            foreach ($items as $item) {
            
                $data['id'] = $item['item_category_id'];
                $data['category_name'] = $item['category_name'];
                $data['is_enabled'] = $item['is_enabled'];
                $data['user_id'] = $item['user_id'];
                $data['user_id'] = $item['user_id'];
                
                // $array['id'] = $item['item_category_id'];
                // $array[$item['category_name']]['id'] = $item['item_category_id'];
                // $array[$item['category_name']]['is_enabled'] = $item['is_enabled'];
                // $array[$item['category_name']]['user_id'] = $item['user_id'];
                // $array[$item['category_name']]['addons'][] = $item;
                // $array[$item['category_name']][] = $item;
                
                array_push($array, $data);
            }

            $this->processSuperCache('items-recommended-' . $restaurant->id, $recommended);
            $this->processSuperCache('items-all-' . $restaurant->id, $array);
        }

        return response()->json(array(
            'recommended' => $recommended,
            'items' => $array,
        ));
    }
    

    /**
     * @param Request $request
     */
    public function searchRestaurants(Request $request)
    {
        //get lat and lng and query from user...
        // get all active restauants doing delivery & selfpickup
        $restaurants = Restaurant::where('is_accepted', '1')
            ->where('is_active', '1')
            ->with('restaurant_categories')
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', "%$request->q%")
                    ->orWhereHas('restaurant_categories', function ($q) use ($request) {
                        $q->where('name', 'LIKE', "%$request->q%");
                    });
            })
            ->with(['coupons' => function ($query) {
                $query->where('is_active', 1)
                    ->where('show_in_home', 1)
                    ->where('expiry_date', '>', Carbon::now())
                    ->whereColumn('count', '<', 'max_count');
            }])
            ->take(10)
            ->get();

        //Create a new Laravel collection from the array data
        $nearMeRestaurants = new Collection();

        foreach ($restaurants as $restaurant) {
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $restaurant->couponCount = count($restaurant->coupons);
                $restaurant->avgRating = storeAvgRating($restaurant->ratings);
                $restaurant->countRating = $restaurant->ratings->count('rating_store');
                $nearMeRestaurants->push($restaurant);
            }
        }

        $nearMeRestaurants = $nearMeRestaurants->toArray();

        shuffle($nearMeRestaurants);
        usort($nearMeRestaurants, function ($left, $right) {
            return $right['is_featured'] - $left['is_featured'];
        });

        $nearMeRestaurants = collect($nearMeRestaurants)->sortBy('distance')->toArray();

        $items = Item::with('restaurant')
            ->where('is_active', '1')
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', "%$request->q%")
                    ->orWhereHas('item_category', function ($q) use ($request) {
                        $q->where('name', 'LIKE', "%$request->q%");
                    });
            })
            ->whereHas('restaurant', function ($q) {
                $q->where('is_accepted', '1')
                    ->where('is_active', '1');
            })
            ->take(50)
            ->get()
            ->filter(function ($item) {
                $restaurant = $item->restaurant;
                return $this->checkOperation($restaurant->latitude, $restaurant->longitude, $restaurant);
            });

        $nearMeItems = new Collection();
        foreach ($items as $item) {
            $nearMeItems->push($item);
        }

        $response = [
            'restaurants' => $nearMeRestaurants,
            'items' => $nearMeItems,
        ];

        return response()->json($response);
    }

    /**
     * @param Request $request
     */
    public function getSingleItem(Request $request)
    {
        if (Cache::has('item-single-' . $request->id)) {
            $item = Cache::get('item-single-' . $request->id);
        } else {

            if (config('setting.showInActiveItemsToo') == 'true') {
                $item = Item::where('id', $request->id)
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->first();
            } else {
                $item = Item::where('id', $request->id)
                    ->where('is_active', '1')
                    ->with('addon_categories')
                    ->with(array('addon_categories.addons' => function ($query) {
                        $query->where('is_active', 1);
                    }))
                    ->first();
            }

            $this->processSuperCache('item-single-' . $request->id, $item);
        }

        if ($item) {
            return response()->json($item);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getFilteredRestaurants(Request $request)
    {
        $activeFilteredRestaurants = Restaurant::where('is_accepted', '1')
            ->where('is_active', 1)
            ->whereHas('restaurant_categories', function ($query) use ($request) {
                $query->whereIn('restaurant_category_id', $request->category_ids);
            })->get();

        $nearMe = new Collection();

        foreach ($activeFilteredRestaurants as $restaurant) {
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $nearMe->push($restaurant);
            }
        }
        $nearMe = $nearMe->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'custom_featured_name', 'custom_message_on_list']);
        });
        $nearMe = $nearMe->toArray();

        $inActiveFilteredRestaurants = Restaurant::where('is_accepted', '1')
            ->where('is_active', 0)
            ->whereHas('restaurant_categories', function ($query) use ($request) {
                $query->whereIn('restaurant_category_id', $request->category_ids);
            })->get();

        $nearMeInActive = new Collection();

        foreach ($inActiveFilteredRestaurants as $restaurant) {
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $nearMeInActive->push($restaurant);
            }
        }

        $nearMeInActive = $nearMeInActive->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'custom_featured_name', 'custom_message_on_list']);
        });
        $nearMeInActive = $nearMeInActive->toArray();

        $merged = array_merge($nearMe, $nearMeInActive);

        return response()->json($merged);
    }

    /**
     * @param Request $request
     */
    public function checkCartItemsAvailability(Request $request)
    {
        $items = $request->items;
        $idsArr = [];

        foreach ($items as $item) {
            array_push($idsArr, $item['id']);
        }
        $cartItems = Item::whereIn('id', $idsArr)->get();

        $inActiveItem = [];

        foreach ($cartItems as $cartItem) {
            if ($cartItem) {
                $item = [
                    'id' => $cartItem->id,
                    'price' => $cartItem->price,
                    'is_active' => $cartItem->is_active,
                ];
                array_push($inActiveItem, $item);
            }
        }

        return response()->json($inActiveItem);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getFavoriteStores(Request $request)
    {
        $user = auth()->user();
        $restaurants = $user->favorite(Restaurant::class);

        $nearMe = new Collection();

        foreach ($restaurants as $restaurant) {
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant);
            if ($check) {
                $nearMe->push($restaurant);
            }
        }
        $nearMe = $nearMe->map(function ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            return $restaurant->only(['id', 'name', 'description', 'image', 'rating', 'avgRating', 'countRating', 'delivery_time', 'price_range', 'slug', 'is_featured', 'is_active', 'custom_featured_name', 'custom_message_on_list']);
        });
        $nearMe = $nearMe->toArray();

        return response()->json($nearMe);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function checkRestaurantOperationService(Request $request)
    {
        $check = false;

        $restaurant = Restaurant::where('id', $request->restaurant_id)->with('zone')->first();
        if ($restaurant) {
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant, true); //true is set for cart page check
            return $check;
        }
        return response()->json($check);
    }

    /**
     * @param Request $request
     */
    public function getRestaurantInfoAndOperationalStatus(Request $request)
    {
        $restaurant = Restaurant::where('id', $request->id)->first();

        if ($restaurant) {
            $restaurant->avgRating = storeAvgRating($restaurant->ratings);
            $restaurant->countRating = $restaurant->ratings->count('rating_store');
            $restaurant->makeHidden(['delivery_areas']);
            $check = $this->checkOperation($request->latitude, $request->longitude, $restaurant, true); //true is set for cart page check
            $is_operational = false;
            if ($check) {
                $is_operational = true;
            }
            $restaurant->is_operational = $is_operational;

            if ($restaurant->item_offers) {
                if (!is_null($restaurant->free_items)) {
                    $restaurant->free_items = Item::whereIn('id', json_decode($restaurant->free_items))->get();
                } else {
                    $restaurant->free_items = null;
                }
            } else {
                $restaurant->free_items = null;
            }

            return response()->json($restaurant);
        } else {
            abort(400, 'Restaurant ID not passed or not found.');
        }
    }

    /**
     * @param $latitudeFrom
     * @param $longitudeFrom
     * @param $restaurant
     * @return boolean
     */
    private function checkOperation($latitudeFrom, $longitudeFrom, $restaurant, $forCartPageCheck = null)
    {
        //check if distance matrix enabled
        if (config('setting.enGDMA') == "true" && $forCartPageCheck) {
            $data = [
                'originLat' => $latitudeFrom,
                'originLong' => $longitudeFrom,
                'destinationLat' => $restaurant->latitude,
                'destinationLong' => $restaurant->longitude,
            ];

            $distance = Curl::to(route('getOsmDistanceApi'))->withData($data)->asJsonResponse()->get();
        } else {
            //if Distance matrix not enabled
            $latFrom = deg2rad($latitudeFrom);
            $lonFrom = deg2rad($longitudeFrom);
            $latTo = deg2rad($restaurant->latitude);
            $lonTo = deg2rad($restaurant->longitude);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

            $distance = $angle * 6371; //distance in km
        }

        //if any delivery area assigned
        if (count($restaurant->delivery_areas) > 0) {
            //check if delivery pro module exists,
            if (Module::find('DeliveryAreaPro') && Module::find('DeliveryAreaPro')->isEnabled()) {
                $dap = new DeliveryArea();
                return $dap->checkArea($latitudeFrom, $longitudeFrom, $restaurant->delivery_areas);
            } else {
                //else use general distance
                if ($distance <= $restaurant->delivery_radius) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            //if no delivery areas, then use general distance
            if ($distance <= $restaurant->delivery_radius) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $name
     * @param $data
     */
    private function processSuperCache($name, $data = null)
    {
        if (Module::find('SuperCache') && Module::find('SuperCache')->isEnabled()) {
            $superCache = new SuperCache();
            $superCache->cacheResponse($name, $data);
        }
    }

    public function getOsmDistanceApi(Request $request)
    {
        $distance = getOsmDistance($request->originLat, $request->originLong, $request->destinationLat, $request->destinationLong);

        //  $distance = getGoogleDistance($request->originLat, $request->originLong, $request->destinationLat, $request->destinationLong);

        return response()->json($distance);
    }
};
