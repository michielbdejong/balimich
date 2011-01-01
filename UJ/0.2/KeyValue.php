<?php
require_once BASE_DIR . 'Storage.php';

class KeyValue {
	public static function get($accountId, $partition, $keyPath) {
	 	$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$keyPathEsc = Storage::escape($keyPath);
		$values = Storage::queryArr("entries$accountIdEsc:$keyPathEsc",
		                               "SELECT `value`, `PubSign` FROM `entries$partitionEsc` WHERE `accountId` = $accountIdEsc AND `keyPath`= '$keyPathEsc'");
		if(!is_array($values) || count($values) > 1) {
			throw new HttpInternalServerError();
		}
		if(count($values) == 0) {
			return json_encode(array('value' => null, 'PubSign' => ''));
		}
		return json_encode(array('value' => $values[0][0], 'PubSign' => $values[0][1]));
	}
	public static function set($accountId, $partition, $keyPath, $value, $PubSign) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$keyPathEsc = Storage::escape($keyPath);
		$valueEsc = Storage::escape($value);
		$PubSignEsc = Storage::escape($PubSign);
		$values = Storage::update("entries$accountIdEsc:$keyPathEsc", array($valueEsc, $PubSignEsc),
		                          "INSERT INTO `entries$partitionEsc` (`accountId`, `keyPath`, `value`, `PubSign`) "
		                                                  ."VALUES ($accountIdEsc, '$keyPathEsc', '$valueEsc', '$PubSignEsc') ON DUPLICATE KEY UPDATE "
		                                                  ."`value` = '$valueEsc', `PubSign` = '$PubSignEsc'");
		if($values !== true) {
			throw new HttpInternalServerError();
		}
		return '';
	}
}
