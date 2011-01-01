<?php
require_once BASE_DIR . 'Storage.php';

class Accounts {
	const STATE_PENDING = 0;
	const STATE_LIVE = 1;
	const STATE_GONE = 2;
	const STATE_EMIGRANT = 3;
	const STATE_PENDINGIMMIGRANT = 4;
	const STATE_IMMIGRANT = 5;

	public static function getAccountId($emailUser, $emailDomain, $storageNode, $app, $pass, $passIsPub) {
		$emailUserEsc = Storage::escape($emailUser);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$md5Pass = md5($pass);
		$partition = ord(substr($emailUserEsc, 0, 1));
		if($passIsPub) {
			$passField = 'md5PubPass';
		} else {
			$passField = 'md5SubPass';
		}
		$result = Storage::queryArr("acct$passField:$emailUserEsc:$emailDomainEsc:$storageNodeEsc:$appEsc:$md5Pass",
		                               "SELECT `accountId`, `state` FROM `accounts$partition` WHERE `emailUser` = '$emailUserEsc' AND `emailDomain` = '$emailDomainEsc' AND `storageNode` = '$storageNodeEsc' "
		                               ."AND `app` = '$appEsc' AND `$passField` = '$md5Pass'");
		if(!is_array($result) || count($result) != 1) {
			throw new HttpForbidden('');
		}
			
		if($result[0][1] == self::STATE_GONE) {
			throw new HttpGone();
		}
		return array((int)$result[0][0], $partition);
	}
	public static function setState($accountId, $partition, $state) {
		$accountIdInt = (int) $accountId;
		$partitionInt = (int)$partition;
		Storage::query("", "UPDATE `accounts$partitionInt` SET `state` = $state WHERE `accountId` = $accountIdInt");
	}
	function getState($accountId, $partition) {
		$accountIdInt = (int) $accountId;
		$partitionInt = (int)$partition;
		$result = Storage::queryArr("", "SELECT `state` from `accounts$partitionInt` WHERE `accountId` = $accountIdInt");
		if(count($result) != 1 || count($result[0]) != 1) {
			throw new HttpInternalServerError();
		}
		return (int)($result[0][0]);
	}
}
