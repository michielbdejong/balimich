<?php
require_once BASE_DIR . 'Storage.php';

class Accounts {
	const STATE_NONEXISTENT = -1;
	const STATE_PENDING = 0;
	const STATE_LIVE = 1;
	const STATE_GONE = 2;
	const STATE_EMIGRANT = 3;
	const STATE_PENDINGIMMIGRANT = 4;
	const STATE_IMMIGRANT = 5;

	public static function getAccountId($emailUser, $emailDomain, $storageNode, $app, $pass, $passIsPub, $checkState = true) {
		$emailUserEsc = Storage::escape($emailUser);
		$emailDomainEsc = Storage::escape($emailDomain);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$partitionInt = (int)ord(substr($emailUserEsc, 0, 1));
		if($passIsPub === 'migrationToken') {//TODO: refactor this so it doesn't use a "trinary boolean" in this ugly way
			$passClauseEsc = '';
		} else if($passIsPub) {
			$md5PassEsc = md5($pass);
			$passClauseEsc = " AND `md5PubPass` = '$md5PassEsc'";
		} else {
			$md5PassEsc = md5($pass);
			$passClauseEsc = " AND `md5SubPass` = '$md5PassEsc'";
		}
		$result = Storage::queryArr("",
		                               "SELECT `accountId`, `state` FROM `accounts$partitionInt` WHERE `emailUser` = '$emailUserEsc' AND `emailDomain` = '$emailDomainEsc' AND `storageNode` = '$storageNodeEsc' "
		                               ."AND `app` = '$appEsc'$passClauseEsc");
		if(!is_array($result) || count($result) != 1) {
			throw new HttpForbidden('');
		}
		$accountIdInt = (int)$result[0][0];
		if($checkState) {		
			if($result[0][1] == self::STATE_GONE) {
				throw new HttpGone();
			}
			
			if($result[0][1] == self::STATE_EMIGRANT) {
				$emigrantTo = Storage::queryVal("", "SELECT `toNode` FROM `emigrants$partitionInt` WHERE `accountId` = $accountIdInt");
				throw new HttpRedirect($emigrantTo);
			}
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
}
