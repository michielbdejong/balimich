<?php
require_once BASE_DIR . 'Accounts.php';

class AccountActions {
	private static function genRegistrationToken($email) {
		return md5('Repelsteeltke'.$email);
	}
	public static function register($emailUser, $emailDomain, $storageNode, $app, $pubPass, $subPass, $fromNode = null) {
		$emailUserEsc = Storage::escape($emailUser);
		$emailDomainEsc = Storage::escape($emailDomain);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$md5PubPass = md5($pubPass);
		$md5SubPass = md5($subPass);
		$partition = ord(substr($emailUserEsc, 0, 1));
		if($fromNode === null) {
			$accountStateInt = Accounts::STATE_PENDING;
		} else {
			$accountStateInt = Accounts::STATE_PENDINGIMMIGRANT;
		}
		$emailEsc = $emailUserEsc.'@'.$emailDomainEsc;
		$registrationTokenEsc = self::genRegistrationToken($emailEsc);
		$existingCount = Storage::queryArr("", "SELECT COUNT(*) FROM `accounts$partition` WHERE "
		    ."`emailUser` = '$emailUserEsc' AND `emailDomain` = '$emailDomainEsc' "
		    ."AND `storageNode` = '$storageNodeEsc' AND `app` = '$appEsc'");
		if(!is_array($existingCount) || ! is_array($existingCount[0]) || $existingCount[0][0] != '0') {
			throw new HttpForbidden('combination of email and app already exists on this unhosted storage node');
		}
		Storage::query("acctmd5PubPass:$emailUserEsc:$emailDomainEsc:$storageNodeEsc:$appEsc:$md5PubPass",
		                "INSERT INTO `accounts$partition` (`emailUser`, `emailDomain`, `storageNode`, `app`, `md5PubPass`, `md5SubPass`, `state`, `registrationToken`) "
		                                           ."VALUES ('$emailUserEsc', '$emailDomainEsc', '$storageNodeEsc', '$appEsc', '$md5PubPass', '$md5SubPass', $accountStateInt, '$registrationTokenEsc')");
		$emailSenderClass = EMAIL_SENDER;
		$emailSender = new $emailSenderClass();
		$emailSender->sendRegistrationToken($emailEsc, $registrationTokenEsc, $fromNode);
		return 'ok';
	}
	public static function confirm($accountId, $partition, $registrationToken) {
		$accountIdInt = (int)$accountId;
		$partitionInt = (int)$partition;
		$registrationTokenEsc = Storage::escape($registrationToken);
		$pendingStateInt = Accounts::STATE_PENDING;
		$pendingImmigrantStateInt = Accounts::STATE_PENDINGIMMIGRANT;
		$liveStateInt = Accounts::STATE_LIVE;
		Storage::query("", "UPDATE `accounts$partitionInt` SET `state` = $liveStateInt WHERE "
		    ."(`state` = $pendingStateInt OR `state` = $pendingImmigrantStateInt) AND `accountId` = $accountIdInt");
		return 'ok';
	}
	public static function disappear($accountId, $partition) {
		switch(Accounts::getState($accountId, $partition)) {
		case Accounts::STATE_PENDING:
		case Accounts::STATE_PENDINGIMMIGRANT:
			Accounts::deleteAccount($accountId, $partition);
			break;
		default:
			Accounts::setState($accountId, $partition, Accounts::STATE_GONE);
		}
		return 'ok';
	}
	public static function emigrate($accountId, $partition, $toNode, $migrationToken) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$toNodeEsc = Storage::escape($toNode);
		$result = Storage::query("", "INSERT INTO `emigrants$partitionEsc` (`accountId`, `migrationToken`, `toNode`) VALUES ($accountIdEsc, '$migrationTokenEsc', '$toNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		Accounts::setState($accountId, $partition, Accounts::STATE_EMIGRANT);
		return 'ok';
	}
	public static function createImmigrant($accountId, $partition, $migrationToken, $fromNode) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$fromNodeEsc = Storage::escape($fromNode);
		$result = Storage::query("", "INSERT INTO `immigrants$partitionEsc` (`accountId`, `migrationToken`, `fromNode`) VALUES ($accountIdEsc, '$migrationTokenEsc', '$fromNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		Accounts::setState($accountId, $partition, Accounts::STATE_PENDINGIMMIGRANT);
		return 'ok';
	}
	public static function immigrate($emailUser, $emailDomain, $storageNode, $app, $pubPass, $subPass, $migrationToken, $fromNode) {
		self::register($emailUser, $emailDomain, $storageNode, $app, $pubPass, $subPass, $fromNode);
		list($accountId, $partition) = Accounts::getAccountId($emailUser, $emailDomain, $storageNode, $app, $pubPass, true);
		return self::createImmigrant($accountId, $partition, $migrationToken, $fromNode);
	}
	private static function checkEmigrant($accountId, $partition, $migrationToken) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$rows = Storage::queryArr("", "SELECT COUNT(*) FROM `emigrants$partitionEsc` WHERE `accountId` = $accountIdEsc AND `migrationToken` = '$migrationTokenEsc'");
		if(!is_array($rows) || count($rows) != 1 || count($rows[0]) != 1 || $rows[0][0] > 1) {
			throw new HttpInternalServerError();
		}
		if($rows[0][0] != 1) {
			throw new HttpForbidden();
		}
	}
	public static function migrate($accountId, $partition, $migrationToken, $group, $keyPath, $needValue, $delete, $limit) {
		self::checkEmigrant($accountId, $partition, $migrationToken);
		switch($group) {
		case 'KV':
			$entries = array();//KeyValue::export($accountId, $partition, $needValue, $delete, $limit);
			return array('KV'=>$entries);
		case 'MSG':
			$messages = array();//Messages::export($accountId, $partition, $needValue, $delete, $limit);
			return array('MSG'=>$messages);
		default:
			$messages = array();//Messages::export($accountId, $partition, $needValue, $delete, $limit);
			if(count($messages) < $limit) {
				$entries = array();//KeyValue::export($accountId, $partition, $needValue, $delete, $limit - count($messages));
			}
			return array('KV'=>$entries, 'MSG'=>$messages);
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
