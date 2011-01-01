<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Accounts.php';

class AccountsTests extends UnitTests {
	function testGetAccountId() {
		echo "(michSub OK)";
		$michSubIdPart = Accounts::getAccountId('mich', 'hotmail.com', 'mlsn.org', 'testApp.org', 'michSub', false);
		$this->assertEqual($michSubIdPart, array(4, 109));
		
		echo "(michPub OK)";
		$michPubIdPart = Accounts::getAccountId('mich', 'hotmail.com', 'mlsn.org', 'testApp.org', 'michPub', true);
		$this->assertEqual($michPubIdPart, array(4, 109));

		echo "(michSub Wrong)";
		try {	
			$michSubWrong = Accounts::getAccountId('mich', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('mich sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(michPub Wrong)";
		try {	
			$michPubWrong = Accounts::getAccountId('mich', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('mich pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Sub Wrong)";
		try {	
			$noneSubWrong = Accounts::getAccountId('none', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('none sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Pub Wrong)";
		try {	
			$nonePubWrong = Accounts::getAccountId('none', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('none pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(Goon Sub OK)";
		try {	
			$goonSub = Accounts::getAccountId('goon', 'hotmail.com', 'mlsn.org', 'testApp.org', 'goonSub', false);
			$this->assertDontReachHere('goon sub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Pub OK)";
		try {	
			$goonPub = Accounts::getAccountId('goon', 'hotmail.com', 'mlsn.org', 'testApp.org', 'goonPub', true);
			$this->assertDontReachHere('goon pub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Sub Wrong)";
		try {	
			$goonSubWrong = Accounts::getAccountId('goon', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('goon sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(Goon Pub Wrong)";
		try {	
			$goonPubWrong = Accounts::getAccountId('goon', 'hotmail.com', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('goon pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function runAll() {
		$this->loadFixture('Accounts');
		echo "testGetAccountId:\n";$this->testGetAccountId();echo "\n";
	}
}
