<?php

namespace App;

use App\MerchantInventory;
use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryVariants extends Model
{
    use SoftDeletes, UuidModel;

    protected $hidden = ['id', 'deleted_at'];

    protected $casts = [
        'options' => 'array'
    ];

    public function alpha_insertShopifyVariant(Merchants $merchant,
                                               MerchantInventory $item,
                                               array $data)
    {
        $results = false;

        $variant = new $this();
        $variant->merchant_uuid = $merchant->uuid;
        $variant->inventory_uuid = $item->uuid;
        $variant->platform_id = $data['id'];
        $variant->platform = 'shopify';
        $variant->inventory_platform_id = $data['product_id'];
        $variant->inventory_item_id = $data['inventory_item_id'];

        $variant->title = $data['title'];
        $variant->price = $data['price'];
        $variant->sku = $data['sku'];
        $variant->position = $data['position'];

        $variant->inventory_policy = $data['inventory_policy'];
        $variant->compare_at_price = $data['compare_at_price'];
        $variant->fulfillment_service = $data['fulfillment_service'];
        $variant->inventory_management = $data['inventory_management'];
        $variant->taxable = $data['taxable'];
        $variant->barcode = $data['barcode'];
        $variant->grams = $data['grams'];
        $variant->image_id = $data['image_id'];
        $variant->weight = $data['weight'];
        $variant->weight_unit = $data['weight_unit'];
        $variant->inventory_quantity = $data['inventory_quantity'];
        $variant->old_inventory_quantity = $data['old_inventory_quantity'];
        $variant->tax_code = $data['tax_code'];
        $variant->requires_shipping = $data['requires_shipping'];

        $options = [];
        foreach($data as $title => $val)
        {
            if (strpos($title, 'option') !== false) {
                $options[$title] = $val;
            }
        }
        $variant->options = json_encode($options);
        if($variant->save())
        {
            $results = $variant;
        }

        return $results;
    }

    public function insert(array $schema)
    {
        $results = false;

        $model = new $this();
        foreach($schema as $col => $val)
        {
            $model->$col = $val;
        }

        if($model->save())
        {
            $results = $model;
        }

        return $results;
    }
}
