<?php
require_once BASE_DIR . 'Storage.php';

class Security {
	const STATE_NONEXISTENT = -1;
	const STATE_PENDING = 0;
	const STATE_LIVE = 1;
	const STATE_GONE = 2;
	const STATE_EMIGRANT = 3;
	const STATE_PENDINGIMMIGRANT = 4;
	const STATE_IMMIGRANT = 5;

	private static function getAccountIdWithClause($user, $storageNode, $app, $passClauseEsc) {
		$userEsc = Storage::escape($user);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$partitionInt = (int)ord(substr($userEsc, 0, 1));
		$result = Storage::queryArr("",
		                               "SELECT `accountId`, `state` FROM `accounts$partitionInt` WHERE `user` = '$userEsc' AND `storageNode` = '$storageNodeEsc' "
		                               ."AND `app` = '$appEsc'$passClauseEsc");
		if(!is_array($result) || count($result) != 1) {
			throw new HttpForbidden('');
		}
		return array((int)$result[0][0], $partitionInt, (int)$result[0][1]);
	}
	private static function checkState($accountIdInt, $partitionInt, $stateInt) {
		if($stateInt == self::STATE_GONE) {
			throw new HttpGone();
		}
		
		if($stateInt == self::STATE_EMIGRANT) {
			$emigrantTo = Storage::queryVal("", "SELECT `toNode` FROM `emigrants$partitionInt` WHERE `accountId` = $accountIdInt");
			throw new HttpRedirect($emigrantTo);
		}
	}
	public static function getAccountIdWithPassword($user, $storageNode, $app, $password) {
		$md5PassEsc = md5($password);
		$passClauseEsc = " AND `md5Pass` = '$md5PassEsc'";
		list($accountIdInt, $partitionInt, $stateInt) = self::getAccountIdWithClause($user, $storageNode, $app, $passClauseEsc);
		self::checkState($accountIdInt, $partitionInt, $stateInt);
		return array($accountIdInt, $partitionInt);
	}
	public static function getAccountIdWithoutPassword($user, $storageNode, $app) {
		$passClauseEsc = "";//" AND `md5SubPass` = '$md5PassEsc'";
		list($accountIdInt, $partitionInt, $stateInt) = self::getAccountIdWithClause($user, $storageNode, $app, $passClauseEsc);
		self::checkState($accountIdInt, $partitionInt, $stateInt);
		return array($accountIdInt, $partitionInt);
	}
	public static function getAccountIdWithMigrationToken($user, $storageNode, $app, $migrationToken) {
		list($accountIdInt, $partitionInt, $stateInt) = self::getAccountIdWithClause($user, $storageNode, $app, '');
		if($stateInt != self::STATE_EMIGRANT) {
			throw new HttpForbidden();
		}
		$migrationTokenEsc = Storage::escape($migrationToken);
		$correctTokenCount = Storage::queryVal("", "SELECT COUNT(*) FROM `emigrants$partitionInt` WHERE `accountId` = $accountIdInt AND `migrationToken` = '$migrationTokenEsc'");
		if($correctTokenCount != '1') {
			throw new HttpForbidden();
		}
		return array($accountIdInt, $partitionInt);
	}
	public static function setState($accountId, $partition, $state) {
		$accountIdInt = (int) $accountId;
		$partitionInt = (int)$partition;
		Storage::query("", "UPDATE `accounts$partitionInt` SET `state` = $state WHERE `accountId` = $accountIdInt");
	}
	public static function getState($accountId, $partition) {
		$accountIdInt = (int) $accountId;
		$partitionInt = (int)$partition;
		$result = Storage::queryArr("", "SELECT `state` from `accounts$partitionInt` WHERE `accountId` = $accountIdInt");
		if(count($result) != 1 || count($result[0]) != 1) {
			return self::STATE_NONEXISTENT;
		}
		return (int)($result[0][0]);
	}
	public static function deleteAccount($accountId, $partition) {
		$accountIdInt = (int) $accountId;
		$partitionInt = (int)$partition;
		$result = Storage::query("", "DELETE FROM `accounts$partitionInt` WHERE `accountId` = $accountIdInt");
	}
	public static function create($user, $storageNode, $app, $pass, $accountState, $registrationToken) {
		$userEsc = Storage::escape($user);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$md5Pass = md5($pass);
		$partition = ord(substr($userEsc, 0, 1));
		$accountStateInt = (int)$accountState;
		$registrationTokenEsc = Storage::escape($registrationToken);
		$existingCount = Storage::queryArr("", "SELECT COUNT(*) FROM `accounts$partition` WHERE "
		    ."`user` = '$userEsc' "
		    ."AND `storageNode` = '$storageNodeEsc' AND `app` = '$appEsc'");
		if(!is_array($existingCount) || ! is_array($existingCount[0]) || $existingCount[0][0] != '0') {
			throw new HttpForbidden('combination of email and app already exists on this unhosted storage node');
		}
		Storage::query("acctmd5PubPass:$userEsc:$storageNodeEsc:$appEsc:$md5Pass",
		                "INSERT INTO `accounts$partition` (`user`, `storageNode`, `app`, `md5Pass`, `state`, `registrationToken`) "
		                                           ."VALUES ('$userEsc', '$storageNodeEsc', '$appEsc', '$md5Pass', $accountStateInt, '$registrationTokenEsc')");
		return 'ok';
	}
	public static function confirmAccount($accountId, $partition, $registrationToken) {
		$accountIdInt = (int)$accountId;
		$partitionInt = (int)$partition;
		$registrationTokenEsc = Storage::escape($registrationToken);
		$pendingStateInt = Security::STATE_PENDING;
		$pendingImmigrantStateInt = Security::STATE_PENDINGIMMIGRANT;
		$liveStateInt = Security::STATE_LIVE;
		Storage::query("", "UPDATE `accounts$partitionInt` SET `state` = $liveStateInt WHERE "
		    ."(`state` = $pendingStateInt OR `state` = $pendingImmigrantStateInt) AND `accountId` = $accountIdInt AND `registrationToken` = '$registrationTokenEsc'");
		return 'ok';
	}
	public static function createEmigrant($accountId, $partition, $toNode, $migrationToken) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$toNodeEsc = Storage::escape($toNode);
		$result = Storage::query("", "INSERT INTO `emigrants$partitionEsc` (`accountId`, `migrationToken`, `toNode`) VALUES ($accountIdEsc, '$migrationTokenEsc', '$toNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		self::setState($accountIdEsc, $partitionEsc, self::STATE_EMIGRANT);
		return 'ok';
	}
	public static function createImmigrant($accountId, $partition, $fromNode, $migrationToken) {
		$accountIdEsc = (int)$accountId;
		$partitionEsc = (int)$partition;
		$migrationTokenEsc = Storage::escape($migrationToken);
		$fromNodeEsc = Storage::escape($fromNode);
		$result = Storage::query("", "INSERT INTO `immigrants$partitionEsc` (`accountId`, `migrationToken`, `fromNode`) VALUES ($accountIdEsc, '$migrationTokenEsc', '$fromNodeEsc')");
		if(!$result) {
			throw new HttpInternalServerError();
		}
		self::setState($accountIdEsc, $partitionEsc, Security::STATE_PENDINGIMMIGRANT);
		return 'ok';
	}
	public static function checkEmigrant($accountId, $partition, $migrationToken) {
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
}
