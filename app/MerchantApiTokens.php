<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class MerchantApiTokens extends Model
{
    use HasJsonRelationships, SoftDeletes, Uuid;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $fillable = ['token', 'client_id', 'token_type', 'scopes', 'active'];

    protected $casts = [
        'id' => 'uuid',
        'token' => 'uuid',
        'client_id' => 'uuid',
        'scopes' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo('App\Clients', 'client_id', 'id');
    }

    public function merchant()
    {
        return $this->belongsTo('App\Merchants', 'scopes->merchant_id','id');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shops', 'scopes->shop_id', 'id');
    }
}
