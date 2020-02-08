<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware'=> ['auth:api', 'scopes']], function() {

    Route::group(['prefix'=> 'merchant'], function() {
        Route::get('/', 'MerchantAccountController@index');
    });

    Route::group(['prefix'=> 'inventory'], function() {
        Route::get('/', 'MerchantInventoryController@index');
        Route::post('/adhoc-inventory-import', 'MerchantInventoryController@rogue_import_biatch');
    });

});
