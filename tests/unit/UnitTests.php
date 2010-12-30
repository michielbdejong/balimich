<?php

abstract class UnitTests {
	function loadFixture($fixtureName) {
		echo 'loading fixture: '.$fixtureName."\n";
		$mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
		$mysql->query('DROP DATABASE `'.DB_NAME.'`');
		$mysql->query('CREATE DATABASE `'.DB_NAME.'`');
		$mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		switch($fixtureName) {
		case 'Storage':
			if(!$mysql->query('CREATE TABLE `testTable` (`foo` varchar(255))')) {
				throw new Exception('Fixture problem: '.$mysql->error);
			}
			break;
		case 'Accounts':
			$md5MichSub = md5('michSub');
			$md5MichPub = md5('michPub');
			$md5GoonSub = md5('goonSub');
			$md5GoonPub = md5('goonPub');
			if(!(	($mysql->query('CREATE TABLE `accounts103` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//103 = 'g' of 'goon'
				&& ($mysql->query('CREATE TABLE `accounts109` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//109 = 'm' of 'mich'
				&& ($mysql->query('CREATE TABLE `accounts110` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//110 = 'n' of 'none', 'newco'
				&& ($mysql->query('CREATE TABLE `accounts112` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//112 = 'p' of 'popco'
				&& ($mysql->query("INSERT INTO `accounts109` (`accountId`, `user`, `node`, `app`, `state`, `md5Pubpass`, `md5SubPass`) VALUES (4, 'mich', 'mlsn.org', 'testApp.org', 1, '$md5MichPub', '$md5MichSub')"))
				&& ($mysql->query("INSERT INTO `accounts103` (`accountId`, `user`, `node`, `app`, `state`, `md5Pubpass`, `md5SubPass`) VALUES (5, 'goon', 'mlsn.org', 'testApp.org', 2, '$md5GoonPub', '$md5GoonSub')"))
				&& ($mysql->query("CREATE TABLE `creationTokens` (`user` varchar(255), `node` varchar(255), `app` varchar(255), `token` varchar(255), `tokenOrigin` varchar(255))"))
				&& ($mysql->query("INSERT INTO `creationTokens` (`user`, `node`, `app`, `token`, `tokenOrigin`) VALUES ('', 'mlsn.org', 'testApp.org', 'asti fasti', 'captcha_7322')"))
				&& ($mysql->query("INSERT INTO `creationTokens` (`user`, `node`, `app`, `token`, `tokenOrigin`) VALUES ('', 'mlsn.org', 'otherApp.org', 'fasti basti', 'captcha_7323')")))) {
				throw new Exception('Fixture problem: '.$mysql->error);
			}
			break;
		case 'KeyValue':
			if(!(($mysql->query("CREATE TABLE IF NOT EXISTS `entries123` (`accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`accountId`, `keyPath`))"))
				&& ($mysql->query("CREATE TABLE IF NOT EXISTS `entries24` (`accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`accountId`, `keyPath`))"))
				&& ($mysql->query("INSERT INTO `entries123` (`accountId`, `keyPath`, `value`, `PubSign02`) VALUES (8, 'abcd/efg', 'hello there', 'yours truly')")))) {
				throw new Exception('Fixture problem: '.$mysql->error);
			}
			break;
		case 'Messages':
			if(!(($mysql->query("CREATE TABLE IF NOT EXISTS `messages123` (`messageId` int unsigned not null auto_increment, `accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`messageId`))"))
				&& ($mysql->query("CREATE TABLE IF NOT EXISTS `messages24` (`messageId` int unsigned not null auto_increment, `accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`messageId`))"))
				&& ($mysql->query("INSERT INTO `messages123` (`accountId`, `keyPath`, `value`, `PubSign02`) VALUES (8, 'abcd/efg', 'msg hello there', 'msg yours truly')")))) {
				throw new Exception('Fixture problem: '.$mysql->error);
			}
			break;
		case 'UJ':
			$md5MichSub = md5('michSub');
			$md5MichPub = md5('michPub');
			if(!(($mysql->query('CREATE TABLE `accounts109` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//109 = 'm' of 'mich'
				&& ($mysql->query('CREATE TABLE `accounts112` (`accountId` int unsigned not null auto_increment, `user` varchar(255), `node` varchar(255), `app` varchar(255), `state` int, `md5PubPass` varchar(255), `md5SubPass` varchar(255), PRIMARY KEY (`accountId`))'))//112 = 'p' of 'pich'
				&& ($mysql->query("INSERT INTO `accounts109` (`accountId`, `user`, `node`, `app`, `state`, `md5Pubpass`, `md5SubPass`) VALUES (4, 'mich', 'mlsn.org', 'testApp.org', 1, '$md5MichPub', '$md5MichSub')"))
				&& ($mysql->query("CREATE TABLE IF NOT EXISTS `entries109` (`accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`accountId`, `keyPath`))"))
				&& ($mysql->query("CREATE TABLE IF NOT EXISTS `messages109` (`messageId` int unsigned not null auto_increment, `accountId` int, `keyPath` varchar(255), `value` text, `PubSign02` varchar(255), PRIMARY KEY (`messageId`))"))
				&& ($mysql->query("CREATE TABLE `creationTokens` (`user` varchar(255), `node` varchar(255), `app` varchar(255), `token` varchar(255), `tokenOrigin` varchar(255))"))
				&& ($mysql->query("INSERT INTO `creationTokens` (`user`, `node`, `app`, `token`, `tokenOrigin`) VALUES ('', 'mlsn.org', 'testApp.org', 'Welcome stranger', 'captcha_7328')"))
				)) {
				throw new Exception('Fixture problem: '.$mysql->error);
			}
			break;
		default:
			echo "fixture not recognised.\n";
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
			echo "F: (".var_export($a, TRUE).") != (".var_export($b, TRUE).")\n";
		} else {
			echo ".";
		}
	}
	function assertDontReachHere($str) {
		echo "F: (not supposed to reach this point: $str)\n";
	}
}
