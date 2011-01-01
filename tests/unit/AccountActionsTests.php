<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'AccountActions.php';
require_once BASE_DIR . 'Accounts.php';//we should really be doing this with a stub in the unit test

class AccountActionsTests extends UnitTests {
	function testRegistration() {
		echo "(register)";
		AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$this->assertEqual($GLOBALS['registrationTokensSent'], array('newco@hotmail.com' => 'e8048d616725752b3188c420d7e03ee5'));
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		echo "(repeat register)";
		try {
			AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub2', 'newcoSub2');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}
		echo "(get account id)";
		$accountIdPart = Accounts::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 110));
		list($accountId, $partition) = $accountIdPart;
		echo "(disappear)";
		AccountActions::disappear($accountId, $partition);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_NONEXISTENT);
		echo "(repeat register after disappear)";
		AccountActions::register('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', 'newcoSub');
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		echo "(confirm)";
		$accountIdPart = Accounts::getAccountId('newco', 'hotmail.com', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(2, 110));
		list($accountId, $partition) = $accountIdPart;
		$registrationToken = $GLOBALS['registrationTokensSent']['newco@hotmail.com'];
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_PENDING);
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

	function runAll() {
		$this->loadFixture('AccountActions');
		echo "testRegistration:\n";$this->testRegistration();echo "\n";
	}
}
