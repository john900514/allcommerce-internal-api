<?php

namespace App;

use App\Merchants;
use App\Traits\UuidModel;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasRolesAndAbilities, LogsActivity, Notifiable, UuidModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id','password', 'remember_token', 'deleted_at','updated_at', 'email_verified_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return Merchants|null
     */
    public function merchant()
    {
        $through_model = $this->hasOne('App\MerchantUsers', 'user_uuid', 'uuid')->first();

        if(!is_null($through_model))
        {
            return $through_model->merchant()->first();
        }

        return null;

    }
}
