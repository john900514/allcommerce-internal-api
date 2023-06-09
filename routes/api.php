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

// @todo - Deprecated
Route::group(['middleware'=> ['auth:api', 'scopes']], function() {
    /*
    Route::group(['prefix'=> 'merchant'], function() {
        Route::get('/', 'MerchantAccountController@index');
    });
    */

    Route::group(['prefix'=> 'inventory'], function() {
        Route::get('/', 'MerchantInventoryController@index');
        Route::post('/adhoc-inventory-import', 'MerchantInventoryController@rogue_import_biatch');
    });
});

Route::group(['middleware'=> ['faux-auth']], function() {
    Route::group(['prefix'=> 'client'], function() {
        Route::get('/', 'ClientResourceController@index');
    });

    Route::group(['middleware'=> ['faux-auth.merchant']], function() {
        Route::group(['prefix'=> 'merchant'], function() {
            Route::get('/', 'MerchantResourceController@index');
        });
    });

    Route::group(['middleware'=> ['faux-auth.shop']], function() {
        Route::group(['prefix'=> 'shop'], function() {
            Route::get('/', 'ShopResourceController@index');
            Route::get('/shipping-methods', 'ShopResourceController@shipping_methods');
            Route::get('/shipping-rates', 'ShopResourceController@shipping_methods');
        });

        Route::group(['prefix'=> 'leads'], function() {
            // New Lead Management Routes - Decoupled.
            Route::post('/email', 'Leads\LeadsController@createWithEmail');
            Route::put('/email', 'Leads\LeadsController@updateWithEmail');

            Route::post('/shipping', 'Leads\LeadsController@createWithShipping');
            Route::put('/shipping', 'Leads\LeadsController@updateWithShipping');
            Route::put('/billing', 'Leads\LeadsController@updateWithBilling');

            Route::post('/draftOrder/shippingMethod', 'Leads\LeadsController@draftOrderWithShippingMethod');
        });

        Route::group(['prefix'=> 'orders'], function() {
            Route::post('/', 'Orders\OrdersController@createWithLead');

            Route::group(['prefix'=> 'payments'], function() {
                Route::group(['prefix'=> 'credit'], function() {
                    Route::post('/authorize', 'Payments\Credit\CreditCardPaymentController@authorize_card');
                    Route::post('/capture', 'Payments\Credit\CreditCardPaymentController@capture_card');
                });
            });
        });
    });

    Route::group(['prefix'=> 'oauth'], function() {
        Route::put('/token', 'Auth\FauxAuthenticationController@update');
        Route::put('/token/active', 'Auth\FauxAuthenticationController@active');
        Route::delete('/token', 'Auth\FauxAuthenticationController@delete');

        Route::group(['middleware'=> ['faux-auth.host']], function() {
            Route::post('/token', 'Auth\FauxAuthenticationController@create');
        });
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
            Route::post('/shipping-rates', 'ShopifySalesChannel\ShopifyShopAccessController@get_store_shipping_rates');

            Route::group(['prefix'=> 'merchant'], function() {
                Route::get('/', 'ShopifySalesChannel\ShopifyShopAccessController@get_assigned_merchant_info');
            });
        });
    });
});
