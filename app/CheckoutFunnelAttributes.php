<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutFunnelAttributes extends Model
{
    use SoftDeletes;

    protected $casts = [
        'funnel_misc_json' => 'array'
    ];

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
