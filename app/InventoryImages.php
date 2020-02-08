<?php

namespace App;

use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryImages extends Model
{
    use SoftDeletes, UuidModel;

    protected $hidden = ['id', 'deleted_at'];

    public function alpha_insertShopifyImage(Merchants $merchant, MerchantInventory $item, array $data)
    {
        $results = false;

        $img = new $this();
        $img->merchant_uuid = $merchant->uuid;
        $img->inventory_uuid = $item->uuid;
        $img->platform_id = $data['id'];
        $img->platform = 'shopify';
        $img->inventory_platform_id = $data['product_id'];

        $img->position = $data['position'];
        $img->alt = $data['alt'];
        $img->width = $data['width'];
        $img->height = $data['height'];
        $img->src = $data['src'];
        $img->variant_ids = json_encode($data['variant_ids']);

        if($img->save())
        {
            $results = $img;
        }

        return $results;
    }
}
