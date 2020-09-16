<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class ShopTypes extends Model
{
    use SoftDeletes, Uuid;

    protected $casts = [
        'id' => 'uuid'
    ];
}
