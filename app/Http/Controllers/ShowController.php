<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BitflyerController;
use App\Http\Controllers\CoincheckController;
use App\Http\Controllers\ZaifController;
use Illuminate\Support\Facades\Redis;
use App\ExchangeApi;
use App\DailyAssetHistory;
use App\DailyRateHistory;
use App\CurrentTotalAmount;
use function Psy\debug;


class ShowController extends Controller{

	public function getController($exchange){
		switch ($exchange){
			case 'bitflyer':
				$controller = new BitflyerController;
				break;
			case 'coincheck':
				$controller = new CoincheckController;
				break;
			case 'zaif':
				$controller = new ZaifController;
				break;
		}

		return $controller;
	}

	public function totalAsset() {
		//api 未登録時
		$exchange_api_models = new ExchangeApi;
		$exchange_apis = $exchange_api_models::where('user_id', Auth::id())->get();
		if($exchange_apis->isEmpty()) {
			$this->data['total_amount'] = 0;
			$this->data['daily_gain'] = 0;
			return view('total_asset', $this->data);
		}

		$bitflyerController = new BitflyerController;
		$bitflyer_assets = $bitflyerController->setAssetParams();
		$asset_params['bitflyer'] = $bitflyer_assets;
		$coincheckController = new CoincheckController;
		$coincheck_assets = $coincheckController->setAssetParams();
		$asset_params['coincheck'] = $coincheck_assets;
		$zaifController = new ZaifController;
		$zaif_assets = $zaifController->setAssetParams();
		$asset_params['zaif'] = $zaif_assets;

		$current_total_amount_model = new CurrentTotalAmount;
		$current_amount = $current_total_amount_model::where('user_id', Auth::id())->first();
		if(!empty($current_amount)) {
			$total_amount = $current_amount->amount;
		}else {
			$total_amount = $bitflyer_assets['total'] + $coincheck_assets['total'] + $zaif_assets['total'];
			$current_total_amount_model->amount = $total_amount;
			$current_total_amount_model->user_id = Auth::id();
			$current_total_amount_model->save();
		}

		$yesterday_amount = DailyAssetHistory::where('user_id', Auth::id())->whereDate('date',  date('Y-m-d', strtotime('-2 day', time())))->first();
		$daily_gain = !empty($yesterday_amount) ? $total_amount - $yesterday_amount->amount : 0;

		$this->data['daily_gain'] = $daily_gain;
		$this->data['all_assets'] = $asset_params;
		$this->data['total_amount'] = $total_amount;

		return view('total_asset', $this->data);
	}

	//todo APIで共通化する
	// /coin_ratio
	public function coinRatio(){
		$bitflyerController = new BitflyerController;
		$bitflyer_assets = $bitflyerController->setAssetParams();
		$asset_params['bitflyer'] = $bitflyer_assets;
		$coincheckController = new CoincheckController;
		$coincheck_assets = $coincheckController->setAssetParams();
		$asset_params['coincheck'] = $coincheck_assets;
		$zaifController = new ZaifController;
		$zaif_assets = $zaifController->setAssetParams();
		$asset_params['zaif'] = $zaif_assets;

		$current_total_amount_model = new CurrentTotalAmount;
		$current_amount = $current_total_amount_model::where('user_id', Auth::id())->first();
		if(!empty($current_amount)) {
			$total_amount = $current_amount->amount;
		}else {
			$total_amount = $bitflyer_assets['total'] + $coincheck_assets['total'] + $zaif_assets['total'];
			$current_total_amount_model->amount = $total_amount;
			$current_total_amount_model->user_id = Auth::id();
			$current_total_amount_model->save();
		}

		$coin_amount = [];
		$coin_amount += (array)config('BitflyerCoins');
		$coin_amount += (array)config('CoincheckCoins');
		$coin_amount += (array)config('ZaifCoins');
		$coin_amount['JPY'] = 0;
		foreach ($coin_amount as $coin_name => $v) {
			$coin_amount[$coin_name] = [];
			$coin_amount[$coin_name]['convert_JPY'] = 0;
			$coin_amount[$coin_name]['amount'] = 0;
		}
		foreach ($asset_params as $exchange => $coin_info) {
			if(!empty($coin_info['coin'])) {
				foreach ($coin_info['coin'] as $coin) {
					$coin_amount[$coin['coin_name']]['convert_JPY'] += $coin['convert_JPY'];
					$coin_amount[$coin['coin_name']]['amount'] += $coin['amount'];
				}
			}
		}

		$this->data['total_amount'] = $total_amount;
		$this->data['amount'] = $coin_amount;

		return view('coin_ratio', $this->data);
	}

	//  /asset_history
	public function dailyAssetHistory() {
		$current_total_amount_model = new CurrentTotalAmount;
		$current_amount = $current_total_amount_model::where('user_id', Auth::id())->first();
		$total_amount = $current_amount->amount;
		$this->data['total_amount'] = $total_amount;

		return view('asset_history', $this->data);
	}

	// /price_list
	public function priceList(){
		$coin_rate_array = [];
		$bitflyer_coin_rate = Redis::get('bitflyer_rate');
		$bitflyer_coin_rate = (array)json_decode($bitflyer_coin_rate);
		unset($bitflyer_coin_rate['JPY']);
		$coin_rate_array['bitflyer'] = $bitflyer_coin_rate;
		$coincheck_coin_rate = Redis::get('coincheck_rate');
		$coincheck_coin_rate = (array)json_decode($coincheck_coin_rate);
		unset($coincheck_coin_rate['JPY']);
		$coin_rate_array['coincheck'] = $coincheck_coin_rate;
		$zaif_coin_rate = Redis::get('zaif_rate');
		$zaif_coin_rate = (array)json_decode($zaif_coin_rate);
		unset($zaif_coin_rate['JPY']);
		$coin_rate_array['zaif'] = $zaif_coin_rate;
		$this->data['coin_rate_array'] = $coin_rate_array;
		$daily_rate_history_model = new DailyRateHistory;
		$bitflyer_rate_histories = $daily_rate_history_model::where('exchange_id', config('exchanges.bitflyer'))->where('date', date('Y-m-d', strtotime('-1 day', time())))->get();
		$yesterday_rate_array = [];
		foreach ($bitflyer_rate_histories as $v) {
			$yesterday_price = $v->rate;
			$current_price = $bitflyer_coin_rate[$v->coin_name];
			$yesterday_rate = num2per($yesterday_price, $current_price, 2) - (float)100.0;
			$yesterday_rate_array['bitflyer'][$v->coin_name] = number_format($yesterday_rate, 2);
		}
		$coincheck_rate_histories = $daily_rate_history_model::where('exchange_id', config('exchanges.coincheck'))->where('date', date('Y-m-d', strtotime('-1 day', time())))->get();
		foreach ($coincheck_rate_histories as $v) {
			$yesterday_price = $v->rate;
			$current_price = $coincheck_coin_rate[$v->coin_name];
			$yesterday_rate = num2per($yesterday_price, $current_price, 2) - (float)100.0;
			$yesterday_rate_array['coincheck'][$v->coin_name] = number_format($yesterday_rate, 2);
		}
		$zaif_rate_histories = $daily_rate_history_model::where('exchange_id', config('exchanges.zaif'))->where('date', date('Y-m-d', strtotime('-1 day', time())))->get();
		foreach ($zaif_rate_histories as $v) {
			$yesterday_price = $v->rate;
			$current_price = $zaif_coin_rate[$v->coin_name];
			$yesterday_rate = num2per($yesterday_price, $current_price, 2) - (float)100.0;
			$yesterday_rate_array['zaif'][$v->coin_name] = number_format($yesterday_rate, 2);
		}
		$this->data['yesterday_rate_array'] = $yesterday_rate_array;

		return view('price_list', $this->data);
	}

	// /$exchange/asset
	public function dispAsset($exchange){
		$controller = self::getController($exchange);
		$asset_params = $controller->setAssetParams();
		$this->data['assets'] = $asset_params;
		$this->data['exchange'] = $exchange;

		return view('assets', $this->data);
	}

	public function dispHistory(){
		self::setParameter();
		$response = self::getHistory();
		$this->data['history'] = $response;

		return view('history', $this->data);
	}

	public function createApi($exchange){
		$exchange_id = config('exchanges')[$exchange];
		$user_id = Auth::id();
		$api_model = new ExchangeApi;
		$api = $api_model::where('user_id', $user_id)->where('exchange_id', $exchange_id)->first();
		$this->data['exchange_id'] = $exchange_id;
		$this->data['api_key'] = !empty($api->api_key) ? $api->api_key : '';
		$this->data['api_secret'] = !empty($api->api_secret)? decrypt($api->api_secret) : '';

		return view('regist_api', $this->data);
	}

	public function registApi($exchange, Request $request){
		$api_model = new ExchangeApi;
		$user_id = Auth::id();
		$exchange_id = config('exchanges')[$exchange];
		$api = $api_model::where('user_id', $user_id)->where('exchange_id', $exchange_id)->first();
		if(!empty($api)){
			$api_model = $api;
		}
		$api_model->api_key = $request->input('api_key');
		//TODO hash化する
		$api_model->api_secret = encrypt($request->input('api_secret'));
		$api_model->user_id = Auth::id();
		$api_model->exchange_id = $request->input('exchange_id');
		$api_model->save();

		$this->data['api_key'] = $api_model->api_key;
		$this->data['api_secret'] = decrypt($api_model->api_secret);
		$this->data['exchange_id'] = $api_model->exchange_id;
		$this->data['message'] = 'APIの登録が完了しました。';

		return view('regist_api', $this->data);
	}


}
