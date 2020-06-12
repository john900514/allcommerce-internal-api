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
    $user = $request->user();
    $merchant = $user->merchant();
    if(!is_null($merchant))
    {
        $m = ['merchant' => $merchant->name];
    }
    else
    {
        $m = ['merchant' => 'Cape & Bay User'];
    }

    $payload = array_merge($user->toArray(), $m);
    return $payload;
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

Route::group(['middleware' => ['shopify.hmac']], function () {
    Route::group(['prefix'=> 'shopify'], function() {
        Route::group(['prefix'=> 'installer'], function() {
            Route::post('/nonce', 'ShopifySalesChannel\ShopifySalesChannelInstallerController@nonce');
            Route::post('/confirm-request', 'ShopifySalesChannel\ShopifySalesChannelInstallerController@confirm');
        });

        Route::group(['prefix'=> 'shop'], function() {
            Route::post('/', 'ShopifySalesChannel\ShopifyShopAccessController@get_basic_store_info');

        });
    });


});
