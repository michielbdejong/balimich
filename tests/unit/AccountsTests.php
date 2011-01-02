<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'Security.php';//we should really be doing this with a stub in the unit test

class AccountsTests extends UnitTests {
	function testRegistration() {
		echo "(register)";
		Accounts::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$this->assertEqual($GLOBALS['registrationTokensSent'], array('newco@hotmail.com' => 'e8048d616725752b3188c420d7e03ee5'));

		echo "(repeat register)";
		try {
			Accounts::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub2', 'newcoSub2');
			$this->assertDontReachHere('repeat register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(immigrate after)";
		try {
			Accounts::immigrate('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub3', 'newcoSub3', 'moving', 'elsewhere.org');
			$this->assertDontReachHere('immigrate after register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Security::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 110));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDING);

		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_NONEXISTENT);

		echo "(repeat register after disappear)";
		Accounts::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$accountIdPart = Security::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(2, 110));

		echo "(get state)";
		list($accountId, $partition) = $accountIdPart;
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDING);

		echo "(confirm)";
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		Accounts::confirm($accountId, $partition, $registrationToken);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_LIVE);

		echo "(repeat register after confirm)";
		try {
			Accounts::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testImmigration() {
		echo "(immigrate)";
		Accounts::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub', 'imcoSub', 'moving2', 'elsewhere2.org');
		$this->assertEqual($GLOBALS['registrationTokensSent']['imco@hotmail.com'], '9c9b3639cf7ba20c25c91147690cdd1b');

		echo "(repeat immigrate)";
		try {
			Accounts::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub2', 'imcoSub2', 'moving3', 'elsewhere3.org');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(register after immigrate)";
		try {
			Accounts::register('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub3', 'imcoSub3');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Security::getAccountId('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 105));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_PENDINGIMMIGRANT);

		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		$this->assertEqual(Security::getState($accountId, $partition), Security::STATE_NONEXISTENT);

		echo "(repeat immigrate after disappear)";
		Accounts::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub4', 'imcoSub4', 'moving4', 'elsewhere4.org');
		$accountIdPart = Security::getAccountId('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub4', true);
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
			Accounts::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub5', 'imcoSub5', 'moving5', 'elsewhere5.org');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testEmigration() {
		echo "(emigrate)";
		Accounts::emigrate(15, 103, 'faraway.org', 'transgress');
		$this->assertEqual(Security::getState(15, 103), Security::STATE_EMIGRANT);

		echo "(test gone)";
		try {
			Security::getAccountId('gobabygogo', 'hotmail.com', 'mlsn.org', 'testApp.org', 'gobabygogoSub', false);
			$this->assertDontReachHere('test gone');
		} catch (HttpRedirect $e) {
			$this->assertEqual($e->getMessage(), "faraway.org");
		}
	}

	function runAll() {
		$this->loadFixture('Accounts');
		echo "testRegistration:\n";$this->testRegistration();echo "\n";
		echo "testImmigration:\n";$this->testImmigration();echo "\n";
		echo "testEmigration:\n";$this->testEmigration();echo "\n";
	}
}
