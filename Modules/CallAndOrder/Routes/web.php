<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
 */

Route::prefix('callandorder')->group(function () {

    Route::group(['middleware' => 'role:Admin'], function () {
        Route::get('/settings', 'CallAndOrderController@settings')->name('cao.settings');
        Route::post('/save-settings', 'CallAndOrderController@saveSettings')->name('cao.saveSettings');

    });

    Route::group(['middleware' => 'permission:login_as_customer'], function () {
        Route::get('/users', 'CallAndOrderController@users')->name('cao.usersPage');
        Route::get('/usersDatatable', 'CallAndOrderController@usersDatatable')->name('cao.usersDatatable');
        Route::post('login-as-customer', 'CallAndOrderController@loginAsCustomer')->name('cao.loginAsCustomer');
        Route::post('register-guest-user', 'CallAndOrderController@registerGuestUser')->name('cao.registerGuestUser');
    });

});
