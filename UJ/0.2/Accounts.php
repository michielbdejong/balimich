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
	
	public static function create($emailUser, $emailDomain, $storageNode, $app, $creationToken, $pubPass, $subPass) {
		$emailUserEsc = Storage::escape($emailUser);
		$emailDomainEsc = Storage::escape($emailDomain);
		$storageNodeEsc = Storage::escape($storageNode);
		$appEsc = Storage::escape($app);
		$creationTokenEsc = Storage::escape($creationToken);
		$md5PubPass = md5($pubPass);
		$md5SubPass = md5($subPass);
		$partition = ord(substr($emailUserEsc, 0, 1));
		//check creationToken:
		$result = Storage::queryArr("", "SELECT `emailUser` FROM `creationTokens$partition` WHERE `token` = '$creationTokenEsc' AND `emailUser` = '$emailUserEsc' "
		                             ."AND `storageNode` = '$storageNodeEsc' AND `app` = '$appEsc'");
		if(count($result) == 0) {
			throw new HttpForbidden();//this storage storageNode doesn't allow you to register without a captcha
		}
		//check for existing accounts for this email but different app:
		$creationStateEsc = self::STATE_LIVE;
		$existingAccountsThisUser = Storage::queryArr("", "SELECT `app` FROM `accounts$partition` WHERE `emailUser` = '$emailUserEsc'");
		if(count($existingAccountsThisUser) != 0) {
			$creationStateEsc = self::STATE_PENDING;
		}
		Storage::query("acctmd5PubPass:$emailUserEsc:$emailDomainEsc:$storageNodeEsc:$appEsc:$md5PubPass",
		                "INSERT INTO `accounts$partition` (`emailUser`, `emailDomain`, `storageNode`, `app`, `md5PubPass`, `md5SubPass`, `state`) "
		                                           ."VALUES ('$emailUserEsc', '$emailDomainEsc', '$storageNodeEsc', '$appEsc', '$md5PubPass', '$md5SubPass', $creationStateEsc)");
		if($creationStateEsc == self::STATE_PENDING) {
			return 'pendingPopShake';
		} else {
			return 'ok';
		}
	}
	public static function disappear($accountId, $partition) {
		self::setState($accountId, $partition, self::STATE_GONE);
		return '';
	}
	public static function setState($accountId, $partition, $state) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int)$partition;
		Storage::query("", "UPDATE `accounts$partitionEsc` SET `state` = $state WHERE `accountId` = $accountIdEsc");
	}
	function getState($accountId, $partition) {
		$accountIdEsc = (int) $accountId;
		$partitionEsc = (int)$partition;
		$result = Storage::queryArr("", "SELECT `state` from `accounts$partitionEsc` WHERE `accountId` = $accountIdEsc");
		if(count($result) != 1 || count($result[0]) != 1) {
			throw new HttpInternalServerError();
		}
		return (int)($result[0][0]);
	}
}
