<?php
require_once BASE_DIR . 'Security.php';
require_once BASE_DIR . 'KeyValue.php';//for migration
require_once BASE_DIR . 'Smtp.php';//for migration

class Accounts {
	private static function genRegistrationToken($email) {
		//return 'asdf';//for easily testing loginapp without having to set up real SMTP
		return md5('Repelsteeltke'.$email);
	}
	public static function register($user, $storageNode, $app, $pass, $fromNode = null) {
		if($fromNode === null) {
			$accountState = Security::STATE_PENDING;
		} else {
			$accountState = Security::STATE_PENDINGIMMIGRANT;
		}
		$email = $user;
		$registrationToken = self::genRegistrationToken($email);
		Security::create($user, $storageNode, $app, $pass, $accountState, $registrationToken);
		$emailSenderClass = EMAIL_SENDER_CLASS;
		$emailSender = new $emailSenderClass();
		$emailSender->sendRegistrationToken($email, $registrationToken, $fromNode);
		return 'ok';
	}
	public static function confirm($accountId, $partition, $registrationToken) {
		return Security::confirmAccount($accountId, $partition, $registrationToken);
	}
	public static function disappear($accountId, $partition) {
		switch(Security::getState($accountId, $partition)) {
		case Security::STATE_PENDING:
		case Security::STATE_PENDINGIMMIGRANT:
			Security::deleteAccount($accountId, $partition);
			break;
		default:
			Security::setState($accountId, $partition, Security::STATE_GONE);
		}
		return 'ok';
	}
	public static function emigrate($accountId, $partition, $toNode, $migrationToken) {
		return Security::createEmigrant($accountId, $partition, $toNode, $migrationToken);
	}
	public static function immigrate($user, $storageNode, $app, $pass, $migrationToken, $fromNode) {
		self::register($user, $storageNode, $app, $pass, $fromNode);
		list($accountId, $partition) = Security::getAccountIdWithPassword($user, $storageNode, $app, $pass);
		return Security::createImmigrant($accountId, $partition, $migrationToken, $fromNode);
	}
	public static function migrate($accountId, $partition, $migrationToken, $keyPath, $needValue, $delete, $limit) {
		Security::checkEmigrant($accountId, $partition, $migrationToken);
		$entries = KeyValue::export($accountId, $partition, $keyPath, $needValue, $delete, $limit);
		if($needValue) {
			return array('KV'=>$entries);
		} else {
			return $entries;
		}
	}
	public static function doMigration($user, $storageNode, $app, $keyPath) {
		list($accountId, $partition) = Security::getAccountId($user, $storageNode, $app);
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
		$success = KeyValue::import($accountId, $partition, $objects);
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
