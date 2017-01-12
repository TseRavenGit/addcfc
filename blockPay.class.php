<?php

/**
 * blockpay.class.php
 * 
 * @lastmodify			2016-12-19
 */
 
class BlockPay {


	/**
	 * 远程接口获取汇率
	 */
	private static function getRake(){
		//汇率接口地址     正式服务器：https://api-wallet.trusblock.com/assets_rake
		$rakeUrl = "https://api-wallet.trusblock.com/assets_rake"; 
		$rake = https_request($rakeUrl);
		if(!$rake){
			return false;
		}
		$rake = json_decode($rake,true);
		return $rake['assets_rake'];
	}

	/**
	 * 根据汇率计算价格
	 * @param   $currency  币种
	 */
	public static function getMoneyByRake($currency){
		$rake = self::getRake();
		if($rake == false) return $rake;
		$rakePrice = $rake[$currency];
		return $rakePrice;
	}

	/**
	 * 根据汇率获取币种列表
	 *
	 */
	public static function getCurrencyList(){
		$rake = self::getRake();
		if($rake == false) return $rake;
		$currencyList = array_keys($rake);
		return $currencyList;
	}
	
}

?>