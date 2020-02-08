<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Str;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/redirect', function (Request $request) {
    $request->session()->put('state', $state = Str::random(40));
    $data = $request->all();

    $query = http_build_query([
        'client_id' => $data['client_id'],
        'redirect_uri' => $data['redirect_uri'],
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
    ]);

    return redirect(env('APP_URL').'/oauth/authorize?'.$query);
});
