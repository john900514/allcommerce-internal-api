<?php

namespace Tests\Route;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OAuthUserRouteTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp() : void
    {
        parent::setUp();
        // Important code goes here.
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $user = factory(User::class)->create();

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;

        $this->json('GET', '/api/user',[], $header)
            ->assertJson([
                'uuid' => $user->uuid,
                'email' => $user->email,
                'name' => $user->name,
            ]);
    }

    public function tearDown() : void
    {
        parent::tearDown();
        // Important code goes here.
    }
}
