<?php

use App\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => Str::random(10),
            'email' => 'developers@capeandbay.com',
            'password' => bcrypt('password'),
            'uuid' => Uuid::uuid4()
        ]);

        $user = User::whereEmail('developers@capeandbay.com')->first();
        Bouncer::assign('dev-god')->to($user);

        DB::table('users')->insert([
            'name' => Str::random(10),
            'email' => 'owner@testclient.com',
            'password' => bcrypt('password'),
            'uuid' => Uuid::uuid4()
        ]);

        $user = User::whereEmail('owner@testclient.com')->first();
        Bouncer::assign('client-owner')->to($user);
    }
}
