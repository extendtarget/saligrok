<?php

/* API ROUTES */



/* START BY qusay */
use Nwidart\Modules\Facades\Module;

 Route::get('qusay', function () {
    //  return getGoogleDistance('35.1219796170162', '36.73946472358833', '35.1295793', '36.7497396');
     return getOsmDistance('35.1219796170162', '36.73946472358833', '35.1295793', '36.7497396');
 });
 
 Route::get('send-sms-for-test', function () {
     $filename = Module::getModulePath('Wazone') . 'message_templates.json';
     $data = json_decode(file_get_contents($filename), true);
     dd($data['msg_status6']);
    // return (new Modules\Wazone\Http\Controllers\WazoneController())->wazone('+963956343074', 'for test');
 });


Route::get('get-delivery-cost', [
    'uses' => 'OrderController@getDeliveryCost',
])->middleware('jwt-auth');

Route::group(['prefix' => 'store-owner', 'middleware' => ['jwt-auth']], function () {
    Route::get('get-cancel-reasons-for-restaurant', [
        'uses' => 'OrderController@getCancelReasonsForRestaurant',
    ]);
    
    Route::get('get-addon-categories', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getAddonCategories',
    ]);
    
    Route::post('save-addon-category', [
        'uses' => 'StoreOwner\StoreOwnerAppController@saveAddonCategory',
    ]);
    
    Route::get('get-addon-category-information-to-edit', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getAddonCategoryInformationToEdit',
    ]);
    
    Route::post('update-addon-category', [
        'uses' => 'StoreOwner\StoreOwnerAppController@updateAddonCategory',
    ]);
    
    Route::get('get-addons', [
    'uses' => 'StoreOwner\StoreOwnerAppController@getAddons',
    ]);
    
    Route::get('get-items-categories', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getItemsOfCategories',
    ]);
    
    Route::post('create-items-categories', [
        'uses' => 'StoreOwner\StoreOwnerAppController@createItemCategory',
    ]);
    
    Route::post('update-items-categories', [
        'uses' => 'StoreOwner\StoreOwnerAppController@updateItemCategory',
    ]);
    
    Route::post('toggle-status', [
        'uses' => 'StoreOwner\StoreOwnerAppController@toggleStatusOfItemOfCategory',
    ]);
    
    Route::post('create-item', [
        'uses' => 'StoreOwner\StoreOwnerAppController@createItem',
    ]);
    
     Route::post('get-menu-with-details', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getMenuWithDetails',
    ]);
    // extend by aya ---------------------------------------------------
   
    Route::delete('addon-category/delete/{id}', 'StoreOwner\StoreOwnerAppController@deleteAddonCategory')->name('storeOwner.deleteAddonCategory');
    
   Route::delete('item-category/delete/{id}', 'StoreOwner\StoreOwnerAppController@deleteItemCategory')->name('storeOwner.deleteItemCategory');
    
    Route::delete('item/delete/{id}', 'StoreOwner\StoreOwnerAppController@deleteItem')->name('storeOwner.deleteItem');
 
    Route::delete('addon/delete/{id}', 'StoreOwner\StoreOwnerAppController@deleteAddon')->name('storeOwner.deleteAddon');
    // ------------------------------------------------------------------
});

Route::get('/get-cod-payment', [
    'uses' => 'PaymentController@getCodPayment',
]);


Route::post('/get-restaurant-items-with-details/{slug}', [
    'uses' => 'RestaurantController@getRestaurantItemsWithDetails',
]);

/* END BY qusay */

Route::post('files-checksum', 'FilesChecksumController@filesCheck');

Route::post('/coordinate-to-address', [
    'uses' => 'GeocoderController@coordinatesToAddress',
]);

Route::post('/address-to-coordinate', [
    'uses' => 'GeocoderController@addressToCoordinates',
]);

Route::match(['get', 'post'], '/get-osm-distance', [
    'uses' => 'RestaurantController@getOsmDistanceApi',
])->name('getOsmDistanceApi');

Route::post('/get-settings', [
    'uses' => 'SettingController@getSettings',
]);

Route::get('/get-setting/{key}', [
    'uses' => 'SettingController@getSettingByKey',
]);

Route::post('/search-location/{query}', [
    'uses' => 'LocationController@searchLocation',
]);

Route::post('/popular-locations', [
    'uses' => 'LocationController@popularLocations',
]);

Route::post('/popular-geo-locations', [
    'uses' => 'LocationController@popularGeoLocations',
]);

Route::post('/promo-slider', [
    'uses' => 'PromoSliderController@promoSlider',
]);

Route::post('/get-delivery-restaurants', [
    'uses' => 'RestaurantController@getDeliveryRestaurants',
]);

Route::post('/get-selfpickup-restaurants', [
    'uses' => 'RestaurantController@getSelfPickupRestaurants',
]);

Route::post('/get-restaurant-info/{slug}', [
    'uses' => 'RestaurantController@getRestaurantInfo',
]);

Route::post('/get-restaurant-info-by-id/{id}', [
    'uses' => 'RestaurantController@getRestaurantInfoById',
]);

Route::post('/get-restaurant-info-and-operational-status', [
    'uses' => 'RestaurantController@getRestaurantInfoAndOperationalStatus',
]);

Route::post('/get-restaurant-items/{slug}', [
    'uses' => 'RestaurantController@getRestaurantItems',
]);

Route::post('/get-pages', [
    'uses' => 'PageController@getPages',
]);

Route::post('/get-single-page', [
    'uses' => 'PageController@getSinglePage',
]);

Route::post('/search-restaurants', [
    'uses' => 'RestaurantController@searchRestaurants',
]);

Route::post('/send-otp', [
    'uses' => 'SmsController@sendOtp',
]);
Route::post('/verify-otp', [
    'uses' => 'SmsController@verifyOtp',
]);
Route::post('/check-restaurant-operation-service', [
    'uses' => 'RestaurantController@checkRestaurantOperationService',
]);

Route::post('/get-single-item', [
    'uses' => 'RestaurantController@getSingleItem',
]);

Route::post('/get-all-languages', [
    'uses' => 'LanguageController@getAllLanguages',
]);

Route::post('/get-single-language', [
    'uses' => 'LanguageController@getSingleLanguage',
]);

Route::post('/get-restaurant-category-slides', [
    'uses' => 'RestaurantCategoryController@getRestaurantCategorySlider',
]);

Route::post('/get-all-restaurants-categories', [
    'uses' => 'RestaurantCategoryController@getAllRestaurantsCategories',
]);

Route::post('/get-filtered-restaurants', [
    'uses' => 'RestaurantController@getFilteredRestaurants',
]);

Route::post('/send-password-reset-mail', [
    'uses' => 'PasswordResetController@sendPasswordResetMail',
]);

Route::post('/verify-password-reset-otp', [
    'uses' => 'PasswordResetController@verifyPasswordResetOtp',
]);

Route::post('/change-user-password', [
    'uses' => 'PasswordResetController@changeUserPassword',
]);

Route::post('/check-cart-items-availability', [
    'uses' => 'RestaurantController@checkCartItemsAvailability',
]);

Route::get('/stripe-redirect-capture', [
    'uses' => 'PaymentController@stripeRedirectCapture',
])->name('stripeRedirectCapture');

/* Paytm */
Route::get('/payment/paytm/{order_id}', [
    'uses' => 'PaymentController@payWithPaytm',
]);
Route::post('/payment/process-paytm', [
    'uses' => 'PaymentController@processPaytm',
]);
/* END Paytm */

Route::get('/get-store-reviews/{slug}', [
    'uses' => 'RatingReviewController@getRatingAndReview',
]);

Route::get('/payment/verify-khalti-payment', [
    'uses' => 'PaymentController@verifyKhaltiPayment',
]);

Route::post('/save-notification-token-no-user', [
    'uses' => 'NotificationController@saveTokenNoUser',
]);

Route::post('/update-user-for-token', [
    'uses' => 'NotificationController@updateUserForToken',
]);


/* Protected Routes for Loggedin users */
Route::group(['middleware' => ['jwt-auth']], function () {

    Route::post('/customer-update-profile', [
        'uses' => 'UserController@updateUserProfile',
    ]);

    Route::post('/customer-delete-profile', [
        'uses' => 'UserController@deleteUserProfile',
    ]);

    Route::post('/get-ratable-order', [
        'uses' => 'RatingReviewController@getRatableOrder',
    ]);

    Route::post('/rate-order', [
        'uses' => 'RatingReviewController@rateOrder',
    ]);

    // Route::post('/get-store-reviews', [
    //     'uses' => 'RatingReviewController@getStoreReviews',
    // ]);

    Route::post('/get-restaurant-info-with-favourite/{slug}', [
        'uses' => 'RestaurantController@getRestaurantInfoWithFavourite',
    ]);

    Route::post('/apply-coupon', [
        'uses' => 'CouponController@applyCoupon',
    ]);

    Route::post('/get-restaurant-coupons', [
        'uses' => 'CouponController@getRestaurantCoupons',
    ]);

    Route::post('/save-notification-token', [
        'uses' => 'NotificationController@saveToken',
    ]);

    Route::post('/update-app-token-for-user', [
        'uses' => 'NotificationController@updateAppTokenForUser',
    ]);

    Route::post('/get-payment-gateways', [
        'uses' => 'PaymentController@getPaymentGateways',
    ]);

    Route::post('/get-addresses', [
        'uses' => 'AddressController@getAddresses',
    ]);
    Route::post('/save-address', [
        'uses' => 'AddressController@saveAddress',
    ]);
    Route::post('/delete-address', [
        'uses' => 'AddressController@deleteAddress',
    ]);
    Route::post('/update-user-info', [
        'uses' => 'UserController@updateUserInfo',
    ]);
    Route::post('/check-running-order', [
        'uses' => 'UserController@checkRunningOrder',
    ]);

    Route::group(['middleware' => ['isactiveuser']], function () {
        Route::post('/place-order', [
            'uses' => 'OrderController@placeOrder',
        ]);
    });

    Route::post('/accept-stripe-payment', [
        'uses' => 'PaymentController@acceptStripePayment',
    ]);

    Route::post('/set-default-address', [
        'uses' => 'AddressController@setDefaultAddress',
    ]);
    Route::post('/get-orders', [
        'uses' => 'OrderController@getOrders',
    ]);
    Route::post('/get-order-items', [
        'uses' => 'OrderController@getOrderItems',
    ]);

    Route::post('/cancel-order', [
        'uses' => 'OrderController@cancelOrder',
    ]);

    Route::post('/get-wallet-transactions', [
        'uses' => 'UserController@getWalletTransactions',
    ]);

    Route::post('/get-referred-users', [
        'uses' => 'UserController@getReferredUsers',
    ]);

    Route::post('/get-user-notifications', [
        'uses' => 'NotificationController@getUserNotifications',
    ]);
    Route::post('/mark-all-notifications-read', [
        'uses' => 'NotificationController@markAllNotificationsRead',
    ]);
    Route::post('/mark-one-notification-read', [
        'uses' => 'NotificationController@markOneNotificationRead',
    ]);

    Route::post('/delivery/update-user-info', [
        'uses' => 'DeliveryController@updateDeliveryUserInfo',
    ]);

    Route::post('/delivery/get-delivery-orders', [
        'uses' => 'DeliveryController@getDeliveryOrders',
    ]);

    Route::post('/delivery/get-single-delivery-order', [
        'uses' => 'DeliveryController@getSingleDeliveryOrder',
    ]);

    Route::post('/delivery/set-delivery-guy-gps-location', [
        'uses' => 'DeliveryController@setDeliveryGuyGpsLocation',
    ]);

    Route::post('/delivery/get-delivery-guy-gps-location', [
        'uses' => 'DeliveryController@getDeliveryGuyGpsLocation',
    ]);

    Route::post('/delivery/accept-to-deliver', [
        'uses' => 'DeliveryController@acceptToDeliver',
    ]);

    Route::post('/delivery/pickedup-order', [
        'uses' => 'DeliveryController@pickedupOrder',
    ]);

    Route::post('/delivery/deliver-order', [
        'uses' => 'DeliveryController@deliverOrder',
    ]);

    Route::post('/delivery/toggle-delivery-guy-status', [
        'uses' => 'DeliveryController@updateDeliveryUserInfo',
    ]);

    Route::post('/delivery/get-completed-orders', [
        'uses' => 'DeliveryController@getCompletedOrders',
    ]);

    Route::post('/conversation/chat', [
        'uses' => 'ChatController@deliveryCustomerChat',
    ]);

    Route::post('/change-avatar', [
        'uses' => 'UserController@changeAvatar',
    ]);

    Route::post('/check-ban', [
        'uses' => 'UserController@checkBan',
    ]);

    Route::post('/toggle-favorite', [
        'uses' => 'UserController@toggleFavorite',
    ]);

    Route::post('/get-favorite-stores', [
        'uses' => 'RestaurantController@getFavoriteStores',
    ]);

    Route::post('/update-tax-number', [
        'uses' => 'UserController@updateTaxNumber',
    ]);
});
/* END Protected Routes */

/*Razorpay APIs*/
Route::post('/payment/razorpay/create-order', [
    'uses' => 'RazorpayController@razorPayCreateOrder',
]);
Route::post('/payment/razorpay/process', [
    'uses' => 'RazorpayController@processRazorpayPayment',
]);
Route::post('/payment/razorpay/webhook', [
    'uses' => 'RazorpayController@webhook',
]);
/*END Razorpay APIs*/


Route::post('/payment/process-razor-pay', [
    'uses' => 'PaymentController@processRazorpay',
]);

Route::get('/payment/process-mercado-pago/{id}', [
    'uses' => 'PaymentController@processMercadoPago',
]);
Route::get('/payment/return-mercado-pago', [
    'uses' => 'PaymentController@returnMercadoPago',
]);

Route::post('/payment/process-paymongo', [
    'uses' => 'PaymentController@processPaymongo',
]);
Route::get('/payment/handle-process-paymongo/{id}', [
    'uses' => 'PaymentController@handlePayMongoRedirect',
]);

/* Auth Routes */
Route::post('/login', [
    'uses' => 'UserController@login',
]);

Route::post('/login-with-otp', [
    'uses' => 'UserController@loginWithOtp',
]);

// extend by aya ==========================
Route::post('/logout', [
    'uses' => 'UserController@logout',
]);

Route::post('/refresh-token', [
    'uses' => 'UserController@refreshToken',
]);
// ==================================================== 
Route::post('/generate-otp-for-login', [
    'uses' => 'SmsController@generateOtpForLogin',
]);

Route::post('/register', [
    'uses' => 'UserController@register',
]);

Route::post('/delivery/login', [
    'uses' => 'DeliveryController@login',
]);
/* END Auth Routes */

/*Store App Routes */


Route::post('/store-owner/login', [
    'uses' => 'StoreOwner\StoreOwnerAppController@login',
]);

Route::get('/store-owner/get-all-language', [
    'uses' => 'StoreOwner\StoreOwnerAppController@getAllLanguage',
]);
Route::get('/store-owner/get-single-language/{language_code}', [
    'uses' => 'StoreOwner\StoreOwnerAppController@getSingleLanguage',
]);

Route::group(['middleware' => ['jwt-auth']], function () {

    Route::post('/store-owner/dashboard', [
        'uses' => 'StoreOwner\StoreOwnerAppController@dashboard',
    ]);

    // Route::post('/store-owner/toggle-store-status', [
    //     'uses' => 'StoreOwner\StoreOwnerAppController@toggleStoreStatus',
    // ]);
    // Extend By Aya
    // Route::get('/store-owner/get-store-status', 'StoreOwner\StoreOwnerAppController@getStoreStatus');
    
    Route::post('/store-owner/get-orders', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getOrders',
    ]);

    Route::post('/store-owner/get-single-order', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getSingleOrder',
    ]);

    Route::post('/store-owner/cancel-order', [
        'uses' => 'StoreOwner\StoreOwnerAppController@cancelOrder',
    ]);

    Route::post('/store-owner/accept-order', [
        'uses' => 'StoreOwner\StoreOwnerAppController@acceptOrder',
    ]);

    Route::post('/store-owner/mark-selfpickup-order-ready', [
        'uses' => 'StoreOwner\StoreOwnerAppController@markSelfpickupOrderReady',
    ]);

    Route::post('/store-owner/mark-selfpickup-order-completed', [
        'uses' => 'StoreOwner\StoreOwnerAppController@markSelfpickupOrderCompleted',
    ]);
    Route::post('/store-owner/confirm-scheduled-order', [
        'uses' => 'StoreOwner\StoreOwnerAppController@confirmScheduledOrder',
    ]);

    Route::post('/store-owner/get-menu', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getMenu',
    ]);

    Route::post('/store-owner/toggle-item-status', [
        'uses' => 'StoreOwner\StoreOwnerAppController@toggleItemStatus',
    ]);

    Route::post('/store-owner/search-items', [
        'uses' => 'StoreOwner\StoreOwnerAppController@searchItems',
    ]);

    Route::post('/store-owner/edit-item', [
        'uses' => 'StoreOwner\StoreOwnerAppController@editItem',
    ]);

    Route::post('/store-owner/update-item', [
        'uses' => 'StoreOwner\StoreOwnerAppController@updateItem',
    ]);
    Route::post('/store-owner/get-past-orders', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getPastOrders',
    ]);
    Route::post('/store-owner/search-orders', [
        'uses' => 'StoreOwner\StoreOwnerAppController@searchOrders',
    ]);

    Route::post('/store-owner/update-item-image', [
        'uses' => 'StoreOwner\StoreOwnerAppController@updateItemImage',
    ]);

    Route::post('/store-owner/get-ratings', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getRatings',
    ]);

    Route::post('/store-owner/get-earnings', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getEarnings',
    ]);

    Route::post('/store-owner/send-payout-request', [
        'uses' => 'StoreOwner\StoreOwnerAppController@sendPayoutRequest',
    ]);

    Route::post('/store-owner/get-inactive-items', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getInactiveItems',
    ]);
    

    Route::post('/store-owner/get-store-page', [
        'uses' => 'StoreOwner\StoreOwnerAppController@getStorePage',
    ]);

    Route::post('/store-owner/toggle-category-status', [
        'uses' => 'StoreOwner\StoreOwnerAppController@toggleCategoryStatus',
    ]);
    // Extend By aya
       Route::post('/store-owner/logout', [
        'uses' => 'StoreOwner\StoreOwnerAppController@logout',
    ]);
    
});


