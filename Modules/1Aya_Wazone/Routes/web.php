<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('wazone')->group(function() {
    Route::get('/activate', 'WazoneController@activate')->name('Wazone.activate');
    Route::get('/device-show', 'WazoneController@show')->name('Wazone.show');
    Route::get('/editRestaurant/{id}', 'TableController@editRestaurant')->name('Wazone.editRestaurant');
    Route::get('/editUser/{id}', 'TableController@editUser')->name('Wazone.editUser');
    Route::get('/saveRestaurantNotifiable/{id}', 'WazoneController@saveRestaurantNotifiable')->name('Wazone.saveRestaurantNotifiable');
    Route::get('/saveUserNotifiable/{id}', 'WazoneController@saveUserNotifiable')->name('Wazone.saveUserNotifiable');
    Route::get('/settings', 'WazoneController@settings')->name('Wazone.settings');
    Route::get('/storesDatatable', 'TableController@storesDatatable')->name('Wazone.storesDatatable');
    Route::get('/testMessage/{phone}', 'MessageController@testMessage')->name('Wazone.testMessage');
    Route::post('/updateRestaurant', 'TableController@updateRestaurant')->name('Wazone.updateRestaurant');
    Route::post('/updateUser', 'TableController@updateUser')->name('Wazone.updateUser');
    Route::get('/usersDatatable', 'TableController@usersDatatable')->name('Wazone.usersDatatable');

    Route::post('/saveMessageTemplates', 'WazoneController@saveMessageTemplates')->name('Wazone.saveMessageTemplates');
    Route::post('/saveWazoneSettings', 'WazoneController@saveWazoneSettings')->name('Wazone.saveWazoneSettings');

    Route::get('/enableAllUsers', 'WazoneController@enableAllUsers')->name('Wazone.enableAllUsers');
    Route::get('/disableAllUsers', 'WazoneController@disableAllUsers')->name('Wazone.disableAllUsers');
    Route::get('/enableAllStores', 'WazoneController@enableAllStores')->name('Wazone.enableAllStores');
    Route::get('/disableAllStores', 'WazoneController@disableAllStores')->name('Wazone.disableAllStores');
});
