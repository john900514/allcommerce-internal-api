<?php

namespace App\Models\PaymentGateways;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class PaymentProviderTypes extends Model
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
    ];

    public function payment_gateways()
    {
        return $this->hasMany('App\Models\PaymentGateways\PaymentProviders', 'provider_type', 'id')
            ->with('gateway_attributes');
    }

    public function getAllCreditGateways($client_id)
    {
        $results = [];

        $type = $this->whereSlug('credit')
            ->with('payment_gateways')
            ->first();

        if((!is_null($type)) && (count($type->payment_gateways) > 0))
        {
            foreach($type->payment_gateways->toArray() as $idx => $gate)
            {
                $pre_status = $type->payment_gateways[$idx]->gateway_attributes->where('name', '=', 'Status')->first();
                $val = '';
                $disabled = true;
                $enabled =  false;
                switch($pre_status->value)
                {
                    case 'Available':
                        $val = 'Not Set Up';

                        if($gate['name'] == 'Dry Run Test Gateway')
                        {
                            $val = 'Enabled';
                        }
                        else
                        {
                            // @todo - if there is a client assigned record - set "Enabled"
                            $c_enabled = ClientEnabledPaymentProviders::whereProviderId($gate['id'])
                                ->whereClientId($client_id)
                                ->first();

                            if(!is_null($c_enabled))
                            {
                                $val = 'Enabled';
                                if(count($c_enabled->misc) > 0)
                                {
                                    $enabled = [];
                                    foreach ($c_enabled->misc as $misc)
                                    {
                                        $enabled[] = $misc;
                                    }
                                }
                            }
                        }

                        $disabled = false;
                        break;

                    case 'Coming Soon!':
                    default:
                    $val = $pre_status->value;

                }
                $results[$idx] = $gate;
                $results[$idx]['availability'] = [
                    'title' => $gate['name'],
                    'status' => $val,
                    'type' => 'Credit Card',
                    'disabled' => $disabled,
                    'enabled' => $enabled
                ];
            }
        }

        return $results;
    }

    public function getAllExpressGateways()
    {
        $results = [];

        $type = $this->whereSlug('express')->with('payment_gateways')->first();

        if((!is_null($type)) && (count($type->payment_gateways) > 0))
        {
            foreach($type->payment_gateways->toArray() as $idx => $gate)
            {
                $pre_status = $type->payment_gateways[$idx]->gateway_attributes->where('name', '=', 'Status')->first();
                $val = '';
                $disabled = true;
                switch($pre_status->value)
                {
                    case 'Available':
                        $val = 'Not Set Up';
                        // @todo - if there is a client assigned record - set "Enabled"
                        // @todo - else - Not Set up
                        $disabled = false;
                        break;

                    case 'Coming Soon!':
                    default:
                        $val = $pre_status->value;

                }
                $results[$idx] = $gate;
                $results[$idx]['availability'] = [
                    'title' => $gate['name'],
                    'status' => $val,
                    'type' => 'Express Pay',
                    'disabled' => $disabled,
                ];
            }
        }

        return $results;
    }

    public function getAllInstallmentGateways()
    {
        $results = [];

        $type = $this->whereSlug('install')->with('payment_gateways')->first();

        if((!is_null($type)) && (count($type->payment_gateways) > 0))
        {
            foreach($type->payment_gateways->toArray() as $idx => $gate)
            {
                $pre_status = $type->payment_gateways[$idx]->gateway_attributes->where('name', '=', 'Status')->first();
                $val = '';
                $disabled = true;
                switch($pre_status->value)
                {
                    case 'Available':
                        $val = 'Not Set Up';
                        // @todo - if there is a client assigned record - set "Enabled"
                        // @todo - else - Not Set up
                        $disabled = false;
                        break;

                    case 'Coming Soon!':
                    default:
                        $val = $pre_status->value;

                }
                $results[$idx] = $gate;
                $results[$idx]['availability'] = [
                    'title' => $gate['name'],
                    'status' => $val,
                    'type' => 'Installment Pay',
                    'disabled' => $disabled,
                ];
            }
        }

        return $results;
    }
}
