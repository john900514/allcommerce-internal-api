<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class Shops extends Model
{
    use SoftDeletes, Uuid;

    protected $guarded = [];

    protected $casts = [
        'id' => 'uuid',
        'merchant_id' => 'uuid',
        'client_id' => 'uuid',
        'shop_type' => 'uuid'
    ];

    public function merchant()
    {
        return $this->belongsTo('App\Merchants', 'merchant_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo('App\Clients', 'client_id', 'id');
    }

    public function shop_type()
    {
        return $this->belongsTo('App\ShopTypes', 'shop_type', 'id');
    }

    public function shoptype()
    {
        return $this->shop_type();
    }

    public function shopify_install()
    {
        return $this->hasOne('App\ShopifyInstalls', 'shop_uuid', 'id');
    }

    public function oauth_api_token()
    {
        return $this->hasOne('App\MerchantApiTokens', 'scopes->shop_id', 'id');
    }

    public function shop_assigned_payment_providers()
    {
        return $this->hasMany('App\Models\PaymentGateways\ShopAssignedPaymentProviders', 'shop_uuid', 'id')
            ->with('payment_provider');
    }

    public function client_enabled_payment_providers()
    {
        return $this->hasMany('App\Models\PaymentGateways\ClientEnabledPaymentProviders', 'client_id', 'client_id')
            ->with('payment_provider');
    }
}
