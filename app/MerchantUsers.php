<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantUsers extends Model
{
    protected $table = 'merchant_users';

    public function merchant()
    {
        return $this->belongsTo('App\Merchants', 'merchant_uuid', 'uuid');
        //return $this->hasOne('App\Merchant', 'uuid', 'merchant_uuid');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'user_uuid', 'uuid');
    }
}
