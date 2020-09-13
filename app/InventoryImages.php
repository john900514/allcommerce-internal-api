<?php

namespace App;

use App\Traits\UuidModel;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryImages extends Model
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
        'variant_ids' => 'array',
        'id' => 'uuid',
        'shop_id' => 'uuid',
        'inventory_uuid' => 'uuid',
    ];

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
