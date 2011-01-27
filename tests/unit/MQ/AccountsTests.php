<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'Security.php';//we should really be doing this with a stub in the unit test

class AccountsTests extends UnitTests {
	function testRegistration() {
		echo "(register)";
		Accounts::register('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass');
		$this->assertEqual($GLOBALS['registrationTokensSent'], array('newco@hotmail.com' => 'e8048d616725752b3188c420d7e03ee5'));
		//$this->assertEqual($GLOBALS['registrationTokensSent'], array('newco@hotmail.com' => 'asdf'));

		echo "(repeat register)";
		try {
			Accounts::register('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass2');
			$this->assertDontReachHere('repeat register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(immigrate after)";
		try {
			Accounts::immigrate('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass3', 'moving', 'elsewhere.org');
			$this->assertDontReachHere('immigrate after register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Security::getAccountIdWithPassword('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass');
		$this->assertEqual($accountIdPart, array(1, 110));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDING);

		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_NONEXISTENT);

		echo "(repeat register after disappear)";
		Accounts::register('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass');
		$accountIdPart = Security::getAccountIdWithPassword('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass');
		$this->assertEqual($accountIdPart, array(2, 110));

		echo "(get state)";
		list($accountId, $partition) = $accountIdPart;
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDING);

		echo "(confirm)";
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		//$registrationToken = 'e8048d616725752b3188c420d7e03ee5';
		Accounts::confirm($accountId, $partition, $registrationToken);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_LIVE);

		echo "(repeat register after confirm)";
		try {
			Accounts::register('newco@hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPass');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testImmigration() {
		echo "(immigrate)";
		Accounts::immigrate('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass', 'moving2', 'elsewhere2.org');
		$this->assertEqual($GLOBALS['registrationTokensSent']['imco@hotmail.com'], '9c9b3639cf7ba20c25c91147690cdd1b');

		echo "(repeat immigrate)";
		try {
			Accounts::immigrate('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass2', 'moving3', 'elsewhere3.org');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(register after immigrate)";
		try {
			Accounts::register('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass3');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Security::getAccountIdWithPassword('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass');
		$this->assertEqual($accountIdPart, array(1, 105));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDINGIMMIGRANT);

		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_NONEXISTENT);

		echo "(repeat immigrate after disappear)";
		Accounts::immigrate('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass4', 'moving4', 'elsewhere4.org');
		$accountIdPart = Security::getAccountIdWithPassword('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass4');
		$this->assertEqual($accountIdPart, array(2, 105));

		echo "(get state)";
		list($accountId, $partition) = $accountIdPart;
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDINGIMMIGRANT);

		echo "(confirm)";
		$registrationToken = $GLOBALS['registrationTokensSent']['imco@hotmail.com'];
		Accounts::confirm($accountId, $partition, $registrationToken);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_LIVE);

		echo "(repeat immigrate after confirm)";
		try {
			Accounts::immigrate('imco@hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPass5', 'moving5', 'elsewhere5.org');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testEmigration() {
		echo "(emigrate)";
		Accounts::emigrate(15, 103, 'faraway.org', 'transgress');
		$this->assertEqual(Security::getState(15, 103), Security::STATE_EMIGRANT);

		echo "(test gone sub)";
		try {
			Security::getAccountIdWithoutPassword('gobabygogo@hotmail.com', 'mlsn.org', 'testApp.org');
			$this->assertDontReachHere('test gone');
		} catch (HttpRedirect $e) {
			$this->assertEqual($e->getMessage(), "faraway.org");
		}

		echo "(test gone pub)";
		try {
			Security::getAccountIdWithoutPassword('gobabygogo@hotmail.com', 'mlsn.org', 'testApp.org');
			$this->assertDontReachHere('test gone');
		} catch (HttpRedirect $e) {
			$this->assertEqual($e->getMessage(), "faraway.org");
		}

		echo "(test migrate foo msg)";
		$response = Accounts::migrate(15, 103, 'transgress', 'foo', true, false, 3);
		$this->assertEqual($response, 
			array('foo'=> 
				array(
					array('value'=>'msg1', 'PubSign'=>'yours truly'), 
					array('value'=>'msg2', 'PubSign'=>'yours truly')
				)
			));

		echo "(test migrate any msg)";
		$response = Accounts::migrate(15, 103, 'transgress', '', true, false, 3);
		$this->assertEqual($response, 
			array('foo'=> 
				array(
					array('value'=>'msg1', 'PubSign'=>'yours truly'), 
					array('value'=>'msg2', 'PubSign'=>'yours truly')
				)
			));

		echo "(test migrate foo msg del)";
		$response = Accounts::migrate(15, 103, 'transgress', 'foo', false, true, 3);
		$this->assertEqual($response, 'ok');

		echo "(test migrate foo msg 404)";
		try {
			Accounts::migrate(15, 103, 'transgress', 'foo', true, false, 3);
			$this->assertDontReachHere('test migrate foo msg 404');
		} catch (HttpNotFound $e) {
			echo ".";
		}

		echo "(test migrate foo msg forbidden 404)";
		try {
			Accounts::migrate(15, 103, 'trafff', 'foo', true, false, 3);
			$this->assertDontReachHere('test migrate foo msg forbidden 404');
		} catch (HttpForbidden $e) {
			echo ".";
		}

	}

	function runAll() {
		$this->loadFixture('Accounts');
		echo "testRegistration:\n";$this->testRegistration();echo "\n";
		echo "testImmigration:\n";$this->testImmigration();echo "\n";
		echo "testEmigration:\n";$this->testEmigration();echo "\n";
	}
}
