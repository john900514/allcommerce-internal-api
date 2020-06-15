<?php

namespace App;

use App\Traits\UuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutFunnels extends Model
{
    use SoftDeletes, UuidModel;

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
}
