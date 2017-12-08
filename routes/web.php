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

Route::get('/', function () {
	return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

//display template
Route::get('/archive', 'indexController@archive');
Route::get('/index', 'indexController@index');
Route::get('/dashboard', 'indexController@dashboard');
Route::get('/detail', 'indexController@detail');


Route::group(['middleware' => ['auth']], function () {

	Route::group(['prefix' => 'bitflyer'], function () {
		Route::get('/board', 'BitflyerController@getBoard');
		Route::get('/getmarkets', 'BitflyerController@getMarket');
		Route::get('/getbalance', 'BitflyerController@getBalance');
		Route::get('/getcoins', 'BitflyerController@getCoins');
		Route::get('/gethistogethistoryry', 'BitflyerController@getHistory');
		Route::get('/getcoinouts', 'BitflyerController@getCoinOuts');
		Route::get('/getchildorders', 'BitflyerController@getHistory');

		Route::get('/order', 'BitflyerController@order');


		//表示
		Route::get('/asset', 'BitflyerController@dispAsset');
		Route::get('/history', 'BitflyerController@dispHistory');
		Route::get('/api', 'BitflyerController@createApi');
		Route::post('/api', 'BitflyerController@registApi');
	});

	Route::group(['prefix' => 'coincheck'], function () {
		Route::get('/board', 'CoincheckController@getBoard');
		Route::get('/getbalance', 'CoincheckController@getBalance');
		Route::get('/transactions', 'CoincheckController@getTransaction');

		Route::get('/leverage_positions', 'CoincheckController@getLeveragePositions');

		Route::get('/order', 'CoincheckController@order');

		//表示
		Route::get('/asset', 'CoincheckController@dispAsset');
		Route::get('/history', 'CoincheckController@dispHistory');
		Route::get('/api', 'CoincheckController@createApi');
		Route::post('/api', 'CoincheckController@registApi');
	});

	Route::group(['prefix' => 'zaif'], function () {
		Route::get('/get_info', 'ZaifController@getInfo');
		Route::get('/trade_history', 'ZaifController@tradeHistory');

		Route::get('/order', 'ZaifController@order');

		//表示
		Route::get('/asset', 'ZaifController@dispAsset');
		Route::get('/history', 'ZaifController@dispHistory');
		Route::get('/api', 'ZaifController@createApi');
		Route::post('/api', 'ZaifController@registApi');
	});

});

