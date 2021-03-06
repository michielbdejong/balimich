<?php

abstract class UnitTests {
	private $mysql;

	//TABLES:
	private function createAccountsTable($partitionInt) {
		return $this->mysql->query("CREATE TABLE `accounts$partitionInt` "
		    ."(`accountId` int unsigned not null auto_increment, `user` varchar(255), "
		    ."`storageNode` varchar(255), `app` varchar(255), "
		    ."`registrationToken` varchar(255), `state` int, `md5Pass` varchar(255), "
		    ."PRIMARY KEY (`accountId`))");
	}
	private function createEntriesTable($partitionInt) {
		return $this->mysql->query("CREATE TABLE IF NOT EXISTS `entries$partitionInt` "
		    ."(`accountId` int, `keyPath` varchar(255), `value` text, `PubSign` varchar(255), "
		    ."PRIMARY KEY (`accountId`, `keyPath`))");
	}
	private function createMessagesTable($partitionInt) {
		return $this->mysql->query("CREATE TABLE IF NOT EXISTS `messages$partitionInt` "
		    ."(`messageId` int unsigned not null auto_increment, `accountId` int, `keyPath` varchar(255), "
		    ."`value` text, `PubSign` varchar(255), "
		    ."PRIMARY KEY (`messageId`))");
	}
	private function createEmigrantsTable($partitionInt) {
		return $this->mysql->query("CREATE TABLE IF NOT EXISTS `emigrants$partitionInt` "
		    ."(`accountId` int, `migrationToken` varchar(255), `toNode` varchar(255),"
		    ."PRIMARY KEY (`accountId`))");
	}
	private function createImmigrantsTable($partitionInt) {
		return $this->mysql->query("CREATE TABLE IF NOT EXISTS `immigrants$partitionInt` "
		    ."(`accountId` int, `migrationToken` varchar(255), `fromNode` varchar(255),"
		    ."PRIMARY KEY (`accountId`))");
	}
	//ROWS:
	private function createAccount($accountIdInt, $partitionInt, $user, $storageNodeEsc, $appEsc, 
	                                                                                       $stateInt, $pass) {
		return $this->mysql->query("INSERT INTO `accounts$partitionInt` "
		    ."(`accountId`, `user`, `storageNode`, `app`, `state`, `md5Pass`) "
		    ."VALUES ($accountIdInt, '$user', '$storageNodeEsc', '$appEsc', $stateInt, '".md5($pass)."')");
	}
	private function createEntry($accountIdInt, $partitionInt, $keyPathEsc, $valueEsc, $PubSignEsc) {
		return $this->mysql->query("INSERT INTO `entries$partitionInt` "
		    ."(`accountId`, `keyPath`, `value`, `PubSign`) "
		    ."VALUES ($accountIdInt, '$keyPathEsc', '$valueEsc', '$PubSignEsc')");
	}
	private function createMessage($accountIdInt, $partitionInt, $keyPathEsc, $valueEsc, $PubSignEsc) {
		return $this->mysql->query("INSERT INTO `messages$partitionInt` "
		    ."(`accountId`, `keyPath`, `value`, `PubSign`) "
		    ."VALUES ($accountIdInt, '$keyPathEsc', '$valueEsc', '$PubSignEsc')");
	}


	function loadFixture($fixtureName) {
		echo 'loading fixture: '.$fixtureName."\n";
		$this->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
		$this->mysql->query('DROP DATABASE `'.DB_NAME.'`');
		memcache_connect(MC_HOST, MC_PORT)->flush();
		$this->mysql->query('CREATE DATABASE `'.DB_NAME.'`');
		$this->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		switch($fixtureName) {
		case 'Storage':
			if(!$this->mysql->query('CREATE TABLE `testTable` (`foo` varchar(255))')) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		case 'Security':
			if(!(	$this->createAccountsTable(103) 
			     && $this->createAccountsTable(109) 
			     && $this->createAccountsTable(110) 
			     && $this->createAccountsTable(112)
			     && $this->createAccount(4, 109, 'mich@hotmail.com', 'mlsn.org', 'testApp.org', 1, 'michPub')
			     && $this->createAccount(5, 103, 'goon@hotmail.com', 'mlsn.org', 'testApp.org', 2, 'goonPub')
			     )) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		case 'Accounts':
			if(!(	$this->createAccountsTable(103) 
			     && $this->createAccountsTable(105) 
			     && $this->createAccountsTable(110) 
			     && $this->createEmigrantsTable(103)
			     && $this->createImmigrantsTable(105)
			     && $this->createEntriesTable(103)
			     && $this->createMessagesTable(103)
			     && $this->createAccount(15, 103, 'gobabygogo@hotmail.com', 'mlsn.org', 'testApp.org', 2, 'gobabygogoPub')
			     && $this->createEntry(15, 103, 'foo', 'bar', 'yours truly')
			     && $this->createMessage(15, 103, 'foo', 'msg1', 'yours truly')
			     && $this->createMessage(15, 103, 'foo', 'msg2', 'yours truly')
			     )) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		case 'KeyValue':
			if(!(   $this->createEntriesTable(123)
			     && $this->createEntriesTable(24)
			     && $this->createEntry(8, 123, 'abcd/efg', 'hello there', 'yours truly')
			     )) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		case 'MessageQueues':
			if(!(   $this->createMessagesTable(123)
			     && $this->createMessagesTable(24)
			     && $this->createMessage(8, 123, 'abcd/efg', 'cmd SEND - bla - etc', 'msg yours truly')
			     )) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		case 'UJ':
			if(!(   $this->createAccountsTable(109)
			     && $this->createEntriesTable(109)
			     && $this->createMessagesTable(109)
			     && $this->createAccountsTable(112)
			     && $this->createEmigrantsTable(109)
			     && $this->createImmigrantsTable(109)//the immigrant will be a different accountId, but it all lives mixed in same db
			     && $this->createAccount(4, 109, 'mich@hotmail.com', 'mlsn.org', 'testApp.org', 1, 'michPass')
			     )) {
				throw new Exception('Fixture problem: '.$this->mysql->error);
			}
			break;
		default:
			throw new Exception("fixture not recognised.");
		}
	}
	function isEqual($a, $b) {
		if(is_array($a) && is_array($b)) {
			foreach($a as $k=>$v) {
				if(!isset($b[$k]) || !$this->isEqual($a[$k], $b[$k])) {
					return false;
				}
			}
			foreach($b as $k=>$v) {
				if(!isset($a[$k])) {
					return false;
				}
			}
			return true;
		} else {
			return ($a === $b);
		}
	}
	function assertEqual($a, $b) {
		if(!$this->isEqual($a, $b)) {
			die("\nF: (".var_export($a, TRUE).") != (".var_export($b, TRUE).")\n");
		} else {
			echo ".";
		}
	}
	function assertDontReachHere($str) {
		echo die("\nF: (not supposed to reach this point: $str)\n");
	}
}
