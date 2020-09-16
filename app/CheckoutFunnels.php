<?php

namespace App;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutFunnels extends Model
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
        'shop_id' => 'uuid',
        'shop_install_id' => 'uuid',
    ];

    public function shop()
    {
        return $this->belongsTo('App\Shops', 'shop_id', 'id');
    }

    public function attributes()
    {
        return $this->hasMany('App\CheckoutFunnelAttributes', 'funnel_uuid', 'id');
    }

    public function getAllActiveFunnels($platform, $shop_uuid)
    {
        $results = [];

        $records = $this->whereShopPlatform($platform)
            ->whereShopInstallId($shop_uuid)
            ->whereActive(1)
            ->get();

        if(count($records) > 0)
        {
            $results = $records;
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

    public function getDefaultFunnelByShop($platform, $shop_uuid)
    {
        $results = false;

        $record = $this->whereShopPlatform($platform)
            ->whereShopInstallId($shop_uuid)
            ->whereDefault(1)
            ->whereActive(1)
            ->first();

        if(!is_null($record) > 0)
        {
            $results = $record;
        }

        return $results;
    }

    public function getProducts()
    {
        $results = [];

        $item_attrs = $this->attributes()->where('funnel_attribute', 'LIKE','%item-%')->get();

        if(count($item_attrs) > 0)
        {
            foreach($item_attrs as $attr)
            {
                $data = $attr['funnel_misc_json'];
                $variant = array_key_exists('variant', $data) ? $data['variant'] : null;
                $qty =  array_key_exists('qty', $data) ? $data['qty'] : 0;

                if(!is_null($variant))
                {
                    $variant_record = InventoryVariants::find($variant);

                    if(!is_null($variant_record))
                    {
                        $product = $variant_record->product()->first();

                        if(!is_null($product))
                        {
                            $results[] = [
                                'product' => $product->id,
                                'variant' => $variant_record->id,
                                'qty' => $qty
                            ];
                        }
                    }
                }
            }
        }

        return $results;
    }
}
