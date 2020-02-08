<?php

namespace App;

use App\Merchants;
use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantInventory extends Model
{
    use SoftDeletes, UuidModel;

    protected $hidden = ['id', 'deleted_at'];

    public function alpha_insertShopifyItem(Merchants $merchant, array $data)
    {
        $results = false;

        $item = new $this();
        $item->merchant_uuid = $merchant->uuid;
        $item->platform_id = $data['id'];
        $item->platform = 'shopify';
        $item->title = $data['title'];
        $item->body_html = $data['body_html'];
        $item->vendor = $data['vendor'];
        $item->product_type = $data['product_type'];
        $item->handle = $data['handle'];
        $item->handle = $data['published_at'];
        $item->tags = $data['tags'];
        if($item->save())
        {
            $results = $item;
        }

        return $results;
    }

    public function variants()
    {
        return $this->hasMany('App\InventoryVariants', 'inventory_uuid', 'uuid');
    }

    public function images()
    {
        return $this->hasMany('App\InventoryImages', 'inventory_uuid', 'uuid');
    }
}
