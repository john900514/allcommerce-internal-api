<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid as RUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class ShopifyInstalls extends Model
{
    use SoftDeletes, Uuid;

    protected $casts = [
        'id' => 'uuid',
        'nonce' => 'uuid',
        'shop_uuid' => 'uuid',
        'merchant_id' => 'uuid',
        'client_id' => 'uuid',
        'logged_in_user' => 'uuid',
    ];

    public function getByShopUrl($url)
    {
        $results = false;

        $record = $this->whereShopifyStoreUrl($url)->first();

        if(!is_null($record))
        {
            $results = $record;
        }

        return $results;
    }

    public function insertNonceRecord($shop_url)
    {
        $results = false;

        $record = new $this();
        $record->nonce = RUuid::uuid4();
        $record->shopify_store_url = $shop_url;

        if($record->save())
        {
            $results = $record;
        }

        return $results;
    }

    public function shop()
    {
        return $this->hasOne('App\Shops', 'id', 'shop_uuid');
    }

    public function addOwnership()
    {
        Log::warning('Locating shop record...');
        $shop = Shops::whereShopUrl($this['shopify_store_url'])
            ->first();

        if(!is_null($shop))
        {
            Log::info('found shop! ', $shop->toArray());
            $this->shop_uuid = $shop->id;
            $this->merchant_id = $shop->merchant_id;
            $this->client_id = $shop->client_id;
            $this->save();
        }
        else
        {
            Log::error("Could not locate shop - {$this->shopify_shore_url}");
        }
    }
}
