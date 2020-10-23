<?php

namespace App\Models\PaymentGateways;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class PaymentProviders extends Model
{
    use SoftDeletes, Uuid;

    protected $primaryKey  = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'id' => 'uuid',
        'provider_type' => 'uuid',
    ];

    public function payment_type()
    {
        return $this->hasOne('App\Models\PaymentGateways\PaymentProviderTypes', 'id', 'provider_type');
    }

    public function gateway_attributes()
    {
        return $this->hasMany('App\Models\PaymentGateways\PaymentProviderAttributes', 'provider_id', 'id');
    }

}
