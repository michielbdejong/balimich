<?php
require_once BASE_DIR . 'Storage.php';

class KeyValue {
	public static function get($accountId, $partition, $keyPath) {
	 	$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$keyPathEsc = Storage::escape($keyPath);
		$values = Storage::queryArr("entries$accountIdEsc:$keyPathEsc",
		                               "SELECT `value`, `pubSign` FROM `entries$partitionEsc` WHERE `accountId` = $accountIdEsc AND `keyPath`= '$keyPathEsc'");
		if(!is_array($values) || count($values) > 1) {
			throw new HttpInternalServerError();
		}
		if(count($values) == 0) {
			return json_encode(array('cmd' => null, 'pubSign' => null));
		}
		return json_encode(array('cmd' => $values[0][0], 'pubSign' => $values[0][1]));
	}
	public static function set($accountId, $partition, $keyPath, $value, $pubSign) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int) $partition;
		$keyPathEsc = Storage::escape($keyPath);
		$valueEsc = Storage::escape($value);
		$pubSignEsc = Storage::escape($pubSign);
		$values = Storage::update("entries$accountIdEsc:$keyPathEsc", array($valueEsc, $pubSignEsc),
		                          "INSERT INTO `entries$partitionEsc` (`accountId`, `keyPath`, `value`, `pubSign`) "
		                                                  ."VALUES ($accountIdEsc, '$keyPathEsc', '$valueEsc', '$pubSignEsc') ON DUPLICATE KEY UPDATE "
		                                                  ."`value` = '$valueEsc', `pubSign` = '$pubSignEsc'");
		if($values !== true) {
			throw new HttpInternalServerError();
		}
		return 'ok';
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
			$fieldsQ = '`keyPath`, `value`, `pubSign`';
		} else {
			$fieldsQ = '`keyPath`';
		}
		$keys = Storage::queryArr("", "SELECT $fieldsQ FROM `entries$partitionInt` WHERE `accountId` = $accountIdInt AND `keyPath` LIKE '$keyPathEsc%' LIMIT $limitInt");
		if(count($keys) == 0) {
			throw new HttpNotFound();
		}
		if($delete) {
			$keysToDeleteEsc = array();
			foreach ($keys as $row) {
				$keysToDeleteEsc[] = Storage::escape($row[0]);
			}
			$keysToDeleteQ = "'".implode("', '", $keysToDeleteEsc)."'";
			Storage::query("", "DELETE FROM `entries$partitionInt` WHERE `keyPath` IN ($keysToDeleteQ)");
		}
		if($needValue) {
			$ret = array();
			foreach($keys as $row) {
				$ret[$row[0]] = array('value'=>$row[1], 'pubSign'=>$row[2]);
			}
			return $ret;
		} else {
			return 'ok';
		}
	}
}
