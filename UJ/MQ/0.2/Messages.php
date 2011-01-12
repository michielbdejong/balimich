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
		                          "SELECT `messageId`, `value`, `PubSign` FROM `messages$partitionEsc` "
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
		$ret = Storage::update("", array(), "INSERT INTO `messages$partitionEsc` (`accountId`, `keyPath`, `value`, `PubSign`) "
		                                                             ."VALUES ($accountIdEsc, '$keyPathEsc', '$valueEsc', '$PubSignEsc')");
		if(!$ret) {
			throw new HttpInternalServerError();
		}
		return '';
	}
	public static function export($accountId, $partition, $keyPath, $needValue, $delete, $limit) {
		if($limit === null) {
			$limit = 101;
		}
		$accountIdInt = (int)$accountId;
		$partitionInt = (int)$partition;
		$keyPathEsc = Storage::escape($keyPath);
		$limitInt = (int)$limit;
		if($needValue) {
			$fieldsQ = '`messageId`, `keyPath`, `value`, `PubSign`';
		} else {
			$fieldsQ = '`messageId`';
		}
		$msgs = Storage::queryArr("", "SELECT $fieldsQ FROM `messages$partitionInt` WHERE `accountId` = $accountIdInt AND `keyPath` LIKE '$keyPathEsc%' LIMIT $limitInt");
		if(count($msgs) == 0) {
			throw new HttpNotFound();
		}
		if($delete) {
			$idsToDeleteEsc = array();
			foreach ($msgs as $row) {
				$idsToDeleteEsc[] = Storage::escape($row[0]);
			}
			$idsToDeleteQ = implode(', ', $idsToDeleteEsc);
			Storage::query("", "DELETE FROM `messages$partitionInt` WHERE `messageId` IN ($idsToDeleteQ)");
		}
		if($needValue) {
			$ret = array();
			foreach($msgs as $row) {
				if(!isset($ret[$row[1]])) {
					$ret[$row[1]] = array();
				}
				$ret[$row[1]][] = array('value'=>$row[2], 'PubSign'=>$row[3]);
			}
			return $ret;
		} else {
			return 'ok';
		}
	}
}
