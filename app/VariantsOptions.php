<?php

namespace App;

use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantsOptions extends Model
{
    use SoftDeletes, UuidModel;

    protected $hidden = ['id', 'deleted_at'];

    protected $casts = [
        'values' => 'array'
    ];

    public function alpha_insertShopifyOption(Merchants $merchant, MerchantInventory $item, array $data)
    {
        $results = false;

        $option = new $this();
        $option->merchant_uuid = $merchant->uuid;
        $option->inventory_uuid = $item->uuid;
        $option->platform_id = $data['id'];
        $option->platform = 'shopify';
        $option->inventory_platform_id = $data['product_id'];

        $option->name = $data['name'];
        $option->position = $data['position'];
        $option->values = json_encode($data['values']);

        if($option->save())
        {
            $results = $option;
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
