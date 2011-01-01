<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'AccountActions.php';
require_once BASE_DIR . 'Accounts.php';//we should really be doing this with a stub in the unit test

class AccountActionsTests extends UnitTests {
	function testRegistration() {
		echo "(register)";
		AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$this->assertEqual($GLOBALS['registrationTokensSent'], array('newco@hotmail.com' => 'e8048d616725752b3188c420d7e03ee5'));

		echo "(repeat register)";
		try {
			AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub2', 'newcoSub2');
			$this->assertDontReachHere('repeat register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(immigrate after)";
		try {
			AccountActions::immigrate('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub3', 'newcoSub3', 'moving', 'elsewhere.org');
			$this->assertDontReachHere('immigrate after register');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Accounts::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 110));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_PENDING);

		echo "(disappear)";
		AccountActions::disappear($accountId, $partition);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_NONEXISTENT);

		echo "(repeat register after disappear)";
		AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$accountIdPart = Accounts::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(2, 110));

		echo "(get state)";
		list($accountId, $partition) = $accountIdPart;
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_PENDING);

		echo "(confirm)";
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		AccountActions::confirm($accountId, $partition, $registrationToken);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_LIVE);

		echo "(repeat register after confirm)";
		try {
			AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testImmigration() {
		echo "(immigrate)";
		AccountActions::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub', 'imcoSub', 'moving2', 'elsewhere2.org');
		$this->assertEqual($GLOBALS['registrationTokensSent']['imco@hotmail.com'], '9c9b3639cf7ba20c25c91147690cdd1b');

		echo "(repeat immigrate)";
		try {
			AccountActions::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub2', 'imcoSub2', 'moving3', 'elsewhere3.org');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(register after immigrate)";
		try {
			AccountActions::register('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub3', 'imcoSub3');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(get account id)";
		$accountIdPart = Accounts::getAccountId('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 105));
		list($accountId, $partition) = $accountIdPart;

		echo "(get state)";
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_PENDINGIMMIGRANT);

		echo "(disappear)";
		AccountActions::disappear($accountId, $partition);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_NONEXISTENT);

		echo "(repeat immigrate after disappear)";
		AccountActions::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub4', 'imcoSub4', 'moving4', 'elsewhere4.org');
		$accountIdPart = Accounts::getAccountId('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub4', true);
		$this->assertEqual($accountIdPart, array(2, 105));

		echo "(get state)";
		list($accountId, $partition) = $accountIdPart;
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_PENDINGIMMIGRANT);

		echo "(confirm)";
		$registrationToken = $GLOBALS['registrationTokensSent']['imco@hotmail.com'];
		AccountActions::confirm($accountId, $partition, $registrationToken);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_LIVE);

		echo "(repeat immigrate after confirm)";
		try {
			AccountActions::immigrate('imco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'imcoPub5', 'imcoSub5', 'moving5', 'elsewhere5.org');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function runAll() {
		$this->loadFixture('AccountActions');
		echo "testRegistration:\n";$this->testRegistration();echo "\n";
		echo "testImmigration:\n";$this->testImmigration();echo "\n";
	}
}
