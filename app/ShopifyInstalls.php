<?php

namespace App;

use Ramsey\Uuid\Uuid;
use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopifyInstalls extends Model
{
    use UuidModel, SoftDeletes;

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
        $record->nonce = Uuid::uuid4();
        $record->shopify_store_url = $shop_url;

        if($record->save())
        {
            $results = $record;
        }

        return $results;
    }

    public function allcommerce_merchant()
    {
        return $this->belongsTo('App\Merchants', 'merchant_uuid', 'uuid');
    }
}
