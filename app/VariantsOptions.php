<?php

namespace App;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantsOptions extends Model
{
    use SoftDeletes, Uuid;

    protected $hidden = ['deleted_at'];

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
        'values' => 'array',
        'id' => 'uuid',
        'shop_id' => 'uuid',
        'inventory_id' => 'uuid',
    ];

    public function alpha_insertShopifyOption(Merchants $merchant, MerchantInventory $item, array $data)
    {
        $results = false;

        $option = new $this();
        $option->merchant_uuid = $merchant->uuid;
        $option->inventory_id = $item->uuid;
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
