<?php

namespace App\Models\PaymentGateways;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class ClientEnabledPaymentProviders extends Model
{
    use HasJsonRelationships, SoftDeletes, Uuid;

    protected $guarded = [];

    protected $casts = [
        'id' => 'uuid',
        'merchant_id' => 'uuid',
        'client_id' => 'uuid',
        'shop_type' => 'uuid',
        'misc'=> 'array'
    ];

    public function payment_provider()
    {
        return $this->belongsTo('App\Models\PaymentGateways\PaymentProviders', 'provider_id', 'id');
    }
}
