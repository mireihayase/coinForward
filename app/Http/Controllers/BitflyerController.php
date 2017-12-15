<?php

namespace App\Http\Controllers;

use App\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api;
use Illuminate\Support\Facades\Redis;


class BitflyerController extends Controller{
	public $data;
	public $user_id;
	public $user_name;

	public $api_key = '';
	public $api_secret = '';
	const API_URL = 'https://api.bitflyer.jp';

	public function __construct(){

	}

	public function setParameter(){
		$exchange_id = config('exchanges.bitflyer');
		$this->data['exchange_id'] = $exchange_id;
		$user_id = Auth::id();
		$api_model = new Api;
		$api = $api_model::where('user_id', $user_id)->where('exchange_id', $exchange_id)->first();

		$this->api_key = !empty($api) ? $api->api_key : '';
		$this->api_secret = !empty($api) ? $api->api_secret : '';
		$this->data['user_name'] = Auth::user()->name;
	}

	public function generateHeader($path, $query_data = null){
		$timestamp = time();
		$body = !empty($query_data) ? '?' . http_build_query($query_data) : '';
		$text = $timestamp . 'GET' . $path . $body;
//		$text = $timestamp . $method . $api . $body;
		$sign = hash_hmac('sha256', $text, $this->api_secret);
//		$header['http_headers'] = array(
		$header = array(
			'ACCESS-KEY:' . $this->api_key,
			'ACCESS-TIMESTAMP:' . $timestamp,
			'ACCESS-SIGN:' . $sign,
			'Content-Type:application/json',
			'Content-Length:'. strlen($body),
		);

		return $header;
	}

	//TODO  createApi 名前変更
	public function createApi(){
		self::setParameter();
		$this->data['api_key'] = !empty($this->api_key) ? $this->api_key : 'API Keyを入力してください';
		$this->data['api_secret'] = !empty($this->api_key)? $this->api_secret : 'API Secretを入力してください';

		return view('regist_api', $this->data);
	}

	public function registApi(Request $request){
		$api_model = new Api;
		$user_id = Auth::id();
		$exchange_id = config('exchanges.bitflyer');
		$api = $api_model::where('user_id', $user_id)->where('exchange_id', $exchange_id)->first();
		if(!empty($api)){
			$api_model = $api;
		}
		$api_model->api_key = $request->input('api_key');
		//TODO hash化する
		$api_model->api_secret = $request->input('api_secret');
		$api_model->user_id = Auth::id();
		$api_model->exchange_id = $request->input('exchange_id');
		$api_model->save();

		$this->data['api_key'] = $api_model->api_key;
		$this->data['api_secret'] = $api_model->api_secret;
		$this->data['exchange_id'] = $api_model->exchange_id;
		$this->data['message'] = 'APIの登録が完了しました。';
		$this->data['user_name'] = Auth::user()->name;

		return view('regist_api', $this->data);
	}

	//
	public function getMarket(){
		$path = '/v1/getmarkets';
		$url = self::API_URL . $path;
		$response = self::curlExec($url);

		return $response;
	}

	//板情報取得
	public function getBoard(){
		$path = '/v1/getboard';
		$url = self::API_URL . $path;
		$response = self::curlExec($url);

		return $response;
	}

	//価格情報取得
	public static function getticker($coin_pair){
		$path = '/v1/getticker';
		$url = self::API_URL . $path;
		$query = ['product_code' => $coin_pair];
		$header = null;
		$response = self::getRequest($url, $header, $query);

		return $response;
	}

	//redisに保存
	//TODO LTC MONA 価格取得
	public static function storeCoinRate(){
		$btc_str = self::getticker('BTC_JPY');
		$btc_class = json_decode($btc_str);
		$btc_rate = $btc_class->best_bid;
		$rate_arary['BTC'] = $btc_rate;

		$eth_str = self::getticker('ETH_BTC');
		$eth_class = json_decode($eth_str);
		$eth_rate = $eth_class->best_bid;
		$rate_arary['ETH'] = $eth_rate * $btc_rate;

		$bch_str = self::getticker('BCH_BTC');
		$bch_class = json_decode($bch_str);
		$bch_rate = $bch_class->best_bid;
		$rate_arary['BCH'] = $bch_rate * $btc_rate;

		Redis::set('bitflyer_rate', json_encode($rate_arary));
	}

	//預入履歴
	public function getCoins(){
//		$path = '/v1/me/getcoins';
		$path = '/v1/me/getdeposits';
		$url = self::API_URL . $path;
		self::setParameter();
		$header = self::generateHeader($path);
		$response = self::curlExec($url, $header);
		echo '<Pre>';
		var_dump('ExecFile: ' . basename(__FILE__) . '(' . __LINE__ . ')', 'FUNCTION: ' . __FUNCTION__);
		var_dump($response);
		exit;
		return $response;
	}

	//資産残高を取得
	public function getBalance(){
		$path = '/v1/me/getbalance';
		$url = self::API_URL . $path;
		self::setParameter();
		$header = self::generateHeader($path);
		$response = self::curlExec($url, $header);

		return $response;
	}

	//
	public function getHistory(){
//		$path = '/v1/me/gethistogethistoryry';
		$path = '/v1/me/getchildorders';
		$url = self::API_URL . $path;
		self::setParameter();
		$header = self::generateHeader($path);
		$response = self::curlExec($url, $header);

		return $response;
	}

	//
	public function getCoinOuts(){
		$path = '/v1/me/getcoinouts';
		$url = self::API_URL . $path;
		$header = self::generateHeader($path);
		$response = self::curlExec($url, $header);

		return $response;
	}

	//注文 post
	/*
	 * {
		  "product_code": "BTC_JPY", //required
		  "child_order_type": "LIMIT", //指値:'LIMIT'/ 成行:'MARKET'  required
		  "side": "BUY", //買い:'BUY'/ 売り:'SELL'  required
		  "price": 30000,
		  "size": 0.1, //required
		  "minute_to_expire": 10000,
		  "time_in_force": "GTC" //'GTC', 'IOC', 'FOK'
		}
	 */
	public function order(){
		$path = '/v1/me/sendchildorder';
//		$post_data = array(
//			'product_code' => $product_code,
//			'child_order_type' => $child_order_type,
//			'side' => $side,
//			'price' => $price,
//			'size' => $size,
//			'minute_to_expire' => $minute_to_expire,
//			'time_in_force' => $time_in_force,
//		);
		self::setParameter();
		$post_data = array(
			'product_code' => 'BTC_JPY',
			'child_order_type' => 'LIMIT',
			'side' => 'BUY',
			'price' => 300,
			'size' => 0.01,
			'minute_to_expire' => 10000,
			'time_in_force' => 'GTC',
		);
		$url = self::API_URL . $path;
		$header = self::generateHeader($path);
		$response = self::curlPost($url, $header,$post_data);

		echo '<Pre>';
		var_dump('ExecFile: ' . basename(__FILE__) . '(' . __LINE__ . ')', 'FUNCTION: ' . __FUNCTION__);
		var_dump($response);
		exit;
		return $response;
	}

	public function dispAsset(){
		self::setParameter();
		$response = self::getBalance();
		$this->data['assets'] = $response;

		return view('btf_assets', $this->data);
	}

	public function dispHistory(){
		self::setParameter();
		$response = self::getHistory();
//		echo '<Pre>';
//		var_dump('ExecFile: ' . basename(__FILE__) . '(' . __LINE__ . ')', 'FUNCTION: ' . __FUNCTION__);
//		var_dump($response);
//		exit;
		$this->data['history'] = $response;

		return view('btf_history', $this->data);
	}
}
