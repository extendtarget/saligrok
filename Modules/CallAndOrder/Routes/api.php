<?php

/*
|--------------------------------------------------------------------------
| API  Routes
|--------------------------------------------------------------------------
|
 */


/* START qusay */
Route::group(['prefix' => 'order-for-guest'], function () {
    Route::post('login-as-customer', 'api\OrderForGuestController@loginAsCustomer');
    Route::post('save-address', 'api\OrderForGuestController@saveAddress');
});
/* END qusay */