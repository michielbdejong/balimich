<?php
require_once BASE_DIR . 'Accounts.php';

class Migration {
	public static function emigrate($accountId, $partition, $migrationToken, $toUser, $toNode) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$toUserEsc = Storage::escape($toUser);
		$toNodeEsc = Storage::escape($toNode);
		$result = Storage::query("INSERT INTO `emigrants$partitionEsc` (`accountId`, `migrationToken`, `toUser`, `toNode`) VALUES ($accountIdEsc, $partitionEsc, '$migrationTokenEsc', '$toUserEsc', '$toNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		Accounts::setState($accountId, $partition, Accounts::STATE_EMIGRANT);
	}
	public static function immigrate($accountId, $partition, $migrationToken, $fromUser, $fromNode) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$fromUserEsc = Storage::escape($fromUser);
		$fromNodeEsc = Storage::escape($fromNode);
		$result = Storage::query("INSERT INTO `immigrants$partitionEsc` (`accountId`, `migrationToken`, `fromUser`, `fromNode`) VALUES ($accountIdEsc, $partitionEsc, '$migrationTokenEsc', '$fromUserEsc', '$fromNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		Accounts::setState($accountId, $partition, Accounts::STATE_IMMIGRANT);
	}
	private static function checkEmigrant($accountId, $partition, $migrationToken, $toUser, $toNode) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$toUserEsc = Storage::escape($toUser);
		$toNodeEsc = Storage::escape($toNode);
		$rows = Storage::queryArr("SELECT COUNT(*) FROM `emigrants$partitionEsc` WHERE `accountId` = $accountIdEsc AND `migrationToken` = '$migrationTokenEsc' "
						."AND `toUser` = '$toUserEsc' AND `toNode` = '$toNodeEsc';");
		if(!is_array($rows) || count($rows) != 1 || count($rows[0]) != 1 || $rows[0][0] > 1) {
			throw new HttpInternalServerError();
		}
		if($rows[0][0] != 1) {
			throw new HttpForbidden();
		}
	}
	public static function migrate($accountId, $partition, $migrationToken, $group, $keyPath, $needValue, $delete, $limit, $toUser, $toNode) {
		self::checkEmigrant($accountId, $partition, $migrationToken, $toUser, $toNode);
		switch($group) {
		case 'KV':
			return KeyValue::export($accountId, $partition, $needValue, $delete, $limit);
			break;
		case 'MSG':
			return Messages::export($accountId, $partition, $needValue, $delete, $limit);
			break;
		default:
			throw new HttpBadRequest();
		}
	}
	public static function doMigration($emailUser, $emailDomain, $storageNode, $app, $group, $keyPath) {
		list($accountId, $partition) = Accounts::getAccountId($emailUser, $emailDomain, $storageNode, $app);
		list($migrationToken, $fromUser, $fromNode) = self::getImmigrantDetails($accountId, $partition);
		//get a few objects (entries or messages, depending on $group):
		$objects = Http::call($fromNode, array(
			'method'=>'MIGR.MIGRATE',
			'app'=>$app,
			'migrationToken'=>$migrationToken, 
			'toNode'=>$storageNode, 
			'group'=>$group,
			'keyPath'=>$keyPath,
			'delete'=>'false',
			'limit'=>10,
			'needValue'=>'true'
			));
		switch($group) {
		case 'KV':
			$success = KeyValue::import($accountId, $partition, $objects);
			break;
		case 'MSG':
			$success = Messages::import($accountId, $partition, $objects);
			break;
		default:
			throw new HttpBadRequest();
		}
		if($success) {//let fromNode know that they we received them correctly (TODO: check this with e.g. CRC!) and they can now delete those objects on their side
			foreach($objects as $thisKeyPath=>$object) {
				Http::call($fromNode, array(
					'method'=>'MIGR.MIGRATE',
					'app'=>$app,
					'migrationToken'=>$migrationToken, 
					'toNode'=>$storageNode, 
					'group'=>$group,
					'keyPath'=>$thisKeyPath,
					'delete'=>'true',
					'limit'=>1,
					'needValue'=>'false'
					));
			}
		}
	}
}
