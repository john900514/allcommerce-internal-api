<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class Merchants extends Model
{
    use HasRolesAndAbilities, LogsActivity, Notifiable, SoftDeletes, Uuid;

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

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'id' => 'uuid',
        'client_id' => 'uuid'
    ];

    public static function clientMerchants($client_id)
    {
        return self::whereClientId($client_id)->get();
    }

    public function permissions(User $user)
    {
        $results = [];

        $abilities = $this->getAbilities();
        $forbidden = $this->getForbiddenAbilities();

        foreach ($abilities as $ability)
        {
            $results[$ability->name] = true;
        }

        foreach ($forbidden as $ability)
        {
            $results[$ability->name] = false;
        }

        foreach ($results as $ability => $toggle)
        {
            if($toggle && $user->cannot($ability))
            {
                Bouncer::allow($user)->to($ability);
                Bouncer::unforbid($user)->to($ability);
            }
            else if((!$toggle))
            {
                Bouncer::forbid($user)->to($ability);
            }
        }

        return $results;
    }

    public function shops()
    {
        return $this->hasMany('App\Shops', 'merchant_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo('App\Clients', 'client_id', 'id');
    }

    public function inventory()
    {
        return $this->hasMany('App\MerchantInventory', 'merchant_uuid', 'uuid');
    }
}
