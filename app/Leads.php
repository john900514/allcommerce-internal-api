<?php

namespace App;

use App\Aggregates\Orders\ShopifyOrderAggregate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class Leads extends Model
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
        'reference_uuid' => 'uuid',
        'shipping_uuid' => 'uuid',
        'billing_uuid' => 'uuid',
        'order_uuid' => 'uuid',
        'shop_uuid' => 'uuid',
        'merchant_uuid' => 'uuid',
        'client_uuid' => 'uuid',
        'misc' => 'collection',
    ];

    public function findLeadViaEmailAddress($email, $shop_id = null, $merchant_id = null, $client_id = null)
    {
        $results = false;

        $record = $this->whereEmail($email);

        if(!is_null($shop_id)) { $record = $record->whereShopUuid($shop_id); }

        if(!is_null($merchant_id)) { $record = $record->whereMerchantUuid($merchant_id); }

        if(!is_null($client_id)) { $record = $record->whereClientUuid($client_id); }

        $record = $record->first();

        if(!is_null($record))
        {
            $results = $record;
        }

        return $results;
    }

    public function findLeadViaPhoneNumber($phone, $shop_id = null, $merchant_id = null, $client_id = null)
    {
        $results = false;

        $record = $this->wherePhone($phone);

        if(!is_null($shop_id)) { $record = $record->whereShopUuid($shop_id); }

        if(!is_null($merchant_id)) { $record = $record->whereMerchantUuid($merchant_id); }

        if(!is_null($client_id)) { $record = $record->whereClientUuid($client_id); }

        $record = $record->first();

        if(!is_null($record))
        {
            $results = $record;
        }

        return $results;
    }

    public function attributes()
    {
        return $this->hasMany('App\LeadAttributes', 'lead_uuid', 'id');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shops', 'shop_uuid', 'id');
    }

    public function merchant()
    {
        return $this->belongsTo('App\Merchants', 'merchant_uuid', 'id');
    }

    public function client()
    {
        return $this->belongsTo('App\Clients', 'client_uuid', 'id');
    }

    public function shipping_address()
    {
        return $this->hasOne('App\ShippingAddresses', 'lead_uuid', 'id');
    }

    public function billing_address()
    {
        return $this->hasOne('App\BillingAddresses', 'lead_uuid', 'id');
    }

    public function email_record()
    {
        return $this->hasOne('App\Emails', 'email', 'email');
    }

    public function shop_install()
    {
        return $this->belongsTo('App\ShopifyInstalls', 'shop_uuid', 'shop_uuid');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($lead) {

        });
    }

    public function isReadyToBeConvertedToOrder()
    {
        $results = false;

        // Validate Shipping and Billing UUID existence
        if((!is_null($this->shipping_uuid)) && (!is_null($this->billing_uuid)))
        {
            // Reference (assumed checkout funnel for now)
            if((!is_null($this->reference_type)) && (!is_null($this->reference_uuid)))
            {
                $shop_type = false;
                switch($this->reference_type)
                {
                    case 'checkout_funnel':
                        $reference = CheckoutFunnels::find($this->reference_uuid);
                        if(!is_null($reference))
                        {
                            $shop_type = $reference->shop_platform;
                        }
                        break;
                }

                // Shop's ShopType (assumed to be shopify for now)
                switch($shop_type)
                {
                    // If shopify, emailList, shopifyCustomer, shopifyDraftOrder
                    case 'shopify':
                        $emailList = $this->attributes()->whereName('emailList')->first();
                        $shopifyCustomer = $this->attributes()->whereName('shopifyCustomer')->first();
                        $shopifyDraftOrder = $this->attributes()->whereName('shopifyDraftOrder')->first();

                        $results = (!is_null($emailList)) && (!is_null($shopifyCustomer)) && (!is_null($shopifyDraftOrder));
                        break;
                }
            }
        }

        return $results;
    }
}
