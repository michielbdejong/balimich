<?php
require_once BASE_DIR . 'Storage.php';

class Messages {
	const MAX_LIMIT = 100;
	public static function receive($accountId, $partition, $keyPath, $delete, $limit) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$limitEsc = (int) min($limit, self::MAX_LIMIT);
		$keyPathEsc = Storage::escape($keyPath);
		$messages = Storage::queryArr("",
		                          "SELECT `messageId`, `value`, `PubSign02` FROM `messages$partitionEsc` "
		                          ."WHERE `accountId` = $accountIdEsc AND `keyPath`= '$keyPathEsc' "
		                          ."ORDER BY `messageId` LIMIT $limitEsc");
		if(count($messages) > 0) {
			$idListEsc = array();
			$returnItems = array();
			foreach($messages as $message) {
				$idListEsc[] = (int)($message[0]);//putting this here optimizes code readability, not performance
				$returnItems[] = array('value' => $message[1], 'PubSign' => $message[2]);
			}
			if($delete) {
				$idListStrEsc = implode(', ', $idListEsc);
				$values = Storage::query("", "DELETE FROM `messages$partitionEsc` WHERE `messageId` IN ($idListStrEsc)");
			}
			return json_encode($returnItems);
		} else {
			return json_encode(array());
		}
	}
	public static function send($accountId, $partition, $keyPath, $value, $PubSign) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$keyPathEsc = Storage::escape($keyPath);
		$valueEsc = Storage::escape($value);
		$PubSignEsc = Storage::escape($PubSign);
		$ret = Storage::update("", array(), "INSERT INTO `messages$partitionEsc` (`accountId`, `keyPath`, `value`, `PubSign02`) "
		                                                             ."VALUES ($accountIdEsc, '$keyPathEsc', '$valueEsc', '$PubSignEsc')");
		if(!$ret) {
			throw new HttpInternalServerError();
		}
		return '';
	}
}