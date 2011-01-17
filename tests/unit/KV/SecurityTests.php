<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Security.php';

class SecurityTests extends UnitTests {
	function testGetAccountId() {
		echo "(michSub OK)";
		$michSubIdPart = Security::getAccountIdWithoutPassword('mich@hotmail.com', 'mlsn.org', 'testApp.org');
		$this->assertEqual($michSubIdPart, array(4, 109));
		
		echo "(michPub OK)";
		$michPubIdPart = Security::getAccountIdWithPassword('mich@hotmail.com', 'mlsn.org', 'testApp.org', 'michPub');
		$this->assertEqual($michPubIdPart, array(4, 109));

		echo "(michPub Wrong)";
		try {	
			$michPubWrong = Security::getAccountIdWithPassword('mich@hotmail.com', 'mlsn.org', 'testApp.org', 'asdf');
			$this->assertDontReachHere('mich pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Sub Wrong)";
		try {	
			$noneSubWrong = Security::getAccountIdWithoutPassword('none@hotmail.com', 'mlsn.org', 'testApp.org');
			$this->assertDontReachHere('none sub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(None Pub Wrong)";
		try {	
			$nonePubWrong = Security::getAccountIdWithPassword('none@hotmail.com', 'mlsn.org', 'testApp.org', 'asdf');
			$this->assertDontReachHere('none pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}

		echo "(Goon Sub OK)";
		try {	
			$goonSub = Security::getAccountIdWithoutPassword('goon@hotmail.com', 'mlsn.org', 'testApp.org');
			$this->assertDontReachHere('goon sub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Pub OK)";
		try {	
			$goonPub = Security::getAccountIdWithPassword('goon@hotmail.com', 'mlsn.org', 'testApp.org', 'goonPub');
			$this->assertDontReachHere('goon pub ok');
		} catch (HttpGone $e) {
			echo ".";
		}

		echo "(Goon Pub Wrong)";
		try {	
			$goonPubWrong = Security::getAccountIdWithPassword('goon@hotmail.com', 'mlsn.org', 'testApp.org', 'asdf');
			$this->assertDontReachHere('goon pub wrong');
		} catch (HttpForbidden $e) {
			echo ".";
		}
	}

	function runAll() {
		$this->loadFixture('Security');
		echo "testGetAccountId:\n";$this->testGetAccountId();echo "\n";
	}
}
