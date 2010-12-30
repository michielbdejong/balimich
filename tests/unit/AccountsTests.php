<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Accounts.php';

class AccountsTests extends UnitTests {
	function testGetAccountId() {
		echo "(mich Sub OK)";
		$michSubIdPart = Accounts::getAccountId('mich', 'mlsn.org', 'testApp.org', 'michSub', false);
		$this->assertEqual($michSubIdPart, array(4, 109));
		
		echo "(mich Pub OK)";
		$michPubIdPart = Accounts::getAccountId('mich', 'mlsn.org', 'testApp.org', 'michPub', true);
		$this->assertEqual($michPubIdPart, array(4, 109));

		echo "(mich Sub Wrong)";
		try {	
			$michSubWrong = Accounts::getAccountId('mich', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('mich sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(mich Pub Wrong)";
		try {	
			$michPubWrong = Accounts::getAccountId('mich', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('mich pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Sub Wrong)";
		try {	
			$noneSubWrong = Accounts::getAccountId('none', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('none sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Pub Wrong)";
		try {	
			$nonePubWrong = Accounts::getAccountId('none', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('none pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(Goon Sub OK)";
		try {	
			$goonSub = Accounts::getAccountId('goon', 'mlsn.org', 'testApp.org', 'goonSub', false);
			$this->assertDontReachHere('goon sub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Pub OK)";
		try {	
			$goonPub = Accounts::getAccountId('goon', 'mlsn.org', 'testApp.org', 'goonPub', true);
			$this->assertDontReachHere('goon pub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Sub Wrong)";
		try {	
			$goonSubWrong = Accounts::getAccountId('goon', 'mlsn.org', 'testApp.org', 'asdf', false);
			$this->assertDontReachHere('goon sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(Goon Pub Wrong)";
		try {	
			$goonPubWrong = Accounts::getAccountId('goon', 'mlsn.org', 'testApp.org', 'asdf', true);
			$this->assertDontReachHere('goon pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testCreate() {
		echo "(create)";
		Accounts::create('newco', 'mlsn.org', 'testApp.org', 'asti fasti', 'captcha_7322', 'newcoPub', 'newcoSub');
		echo ".(steal email)";
		try {
			Accounts::create('newco', 'mlsn.org', 'otherApp.org', 'fasti basti', 'captcha_7323', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('steal email');
		} catch (HttpForbidden $e) {
			echo ".";
		}
		echo "(repeat create)";
		try {
			Accounts::create('newco', 'mlsn.org', 'testApp.org', 'asti fasti', 'captcha_7322', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('repeat create');
		} catch (HttpForbidden $e) {
			echo ".";
		}
		echo "(get account id)";
		$accountIdPart = Accounts::getAccountId('newco', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 110));
		list($accountId, $partition) = $accountIdPart;
		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		$this->assertEqual(Accounts::getState($accountId, $partition), Accounts::STATE_GONE);
		echo "(repeat create after disappear)";
		try {
			Accounts::create('newco', 'mlsn.org', 'testApp.org', 'asti fasti', 'captcha_7322', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('repeat create after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
		echo "(steal email after disappear)";
		try {
			Accounts::create('newco', 'mlsn.org', 'otherApp.org', 'fasti basti', 'captcha_7323', 'newcoPub', 'newcoSub');
			$this->assertDontReachHere('steal email after disappear');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function testPopShake() {
		echo "(give pop shake)";
		Accounts::givePopShake('popco', 'mlsn.org', 'testApp.org', 'enjoy the other app', 'originApp.org');
		echo ".(create)";
		Accounts::create('popco', 'mlsn.org', 'testApp.org', 'enjoy the other app', 'originApp.org', 'newcoPub', 'newcoSub');
		echo ".(get account id)";
		$accountIdPart = Accounts::getAccountId('popco', 'mlsn.org', 'testApp.org', 'newcoPub', true);
		$this->assertEqual($accountIdPart, array(1, 112));
		list($accountId, $partition) = $accountIdPart;
		echo "(disappear)";
		Accounts::disappear($accountId, $partition);
		echo ".(get account id after disappear)";
		try {
			$accountIdPart = Accounts::getAccountId('popco', 'mlsn.org', 'testApp.org', 'newcoPub', true);
			$this->assertDontReachHere('get account id after disappear');
		} catch (HttpGone $e) {
			echo ".";
		}
	}
	function runAll() {
		$this->loadFixture('Accounts');
		echo "testGetAccountId:\n";$this->testGetAccountId();echo "\n";
		echo "testCreate:\n";$this->testCreate();echo "\n";
		echo "testPopShake:\n";$this->testPopShake();echo "\n";
	}
}
