<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Silber\Bouncer\BouncerFacade as Bouncer;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class Clients extends Model
{
    use Uuid, SoftDeletes;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $fillable = ['name', 'active'];

    public static function getAllClientsDropList()
    {
        $results = ['Select a Client Account'];

        $records = self::all();
        $host_uuid = self::getHostClient();

        if(count($records) > 0)
        {
            foreach ($records as $client) {
                if(($client->uuid == $host_uuid) && (backpack_user()->client_id == $host_uuid))
                {
                    $results[$client->id] = $client->name;
                }
                elseif(backpack_user()->client_id == $client->uuid)
                {
                    $results[$client->id] = $client->name;
                }
                elseif(Bouncer::is(backpack_user())->a('god', 'admin'))
                {
                    $results[$client->id] = $client->name;
                }
            }
        }

        return $results;
    }

    public static function getHostClient()
    {
        $results = false;

        $model = self::whereName(env('HOST_CLIENT'))->first();

        if(!is_null($model))
        {
            $results = $model->id;
        }

        return $results;
    }

    public function merchants()
    {
        return $this->hasMany('App\Merchants', 'client_id', 'id');
    }

    public function shops()
    {
        return $this->hasMany('App\Shops', 'client_id', 'id');
    }
}
