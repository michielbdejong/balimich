<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'UJ.php';

class UJTests extends UnitTests {
	private function testParams($node, $app, $postParams) {
		if($node !== null) {
			$_SERVER['HTTP_HOST'] = $node;
		}
		if($app !== null) {
			$_SERVER['HTTP_REFERER'] = $app;
		}
		$_POST = array();	
		foreach($postParams as $k=>$v) {
			$_POST[$k]=$v;
		}
		$uj = new UnhostedJSONParser();
		return $uj->parse();
	}
	private function testProtocolViolations() {
	}
	private function testEachCommandOnce() {
		echo "(kv.get 404)";
		try {
			$result = $this->testParams('mlsn.org', 'testApp.org', 
				array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'KV.GET', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub'));
			$this->assertDontReachHere('should have given 404');
		} catch (HttpNotFound $e) {
			echo ".";
		}

		echo "(kv.set)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'KV.SET', 'keyPath'=>'abcd/efg', 'value'=>'hello there',
				'PubSign'=>'yours truly', 'pubPass'=>'michPub'));
		$this->assertEqual($result, '');

		echo "(kv.get)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'KV.GET', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub'));
		$this->assertEqual($result, '{"value":"hello there","PubSign":"yours truly"}');

		echo "(msg.receive empty)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'MSG.RECEIVE', 'keyPath'=>'abcd/efg', 'pubPass'=>'michPub', 'delete'=>'true', 'limit'=>'10'));
		$this->assertEqual($result, '[]');

		echo "(msg.send)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'MSG.SEND', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub', 'value'=>'hiya', 'PubSign'=>'me'));
		$this->assertEqual($result, '');

		echo "(msg.receive)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'mich', 'protocol'=>'UJ/0.2', 'action'=>'MSG.RECEIVE', 'keyPath'=>'abcd/efg', 'pubPass'=>'michPub', 'delete'=>'true', 'limit'=>'10'));
		$this->assertEqual($result, '[{"value":"hiya","PubSign":"me"}]');

		echo "(acct.create)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'pich', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.CREATE', 'creationToken'=>'Welcome stranger', 'tokenOrigin'=>'captcha_7328', 'pubPass'=>'pichPub', 'subPass'=>'pichSub'));
		$this->assertEqual($result, '');

		echo "(acct.givePopShake)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'pich', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.GIVEPOPSHAKE', 'creationToken'=>'Welcome there too Pich', 'pubPass'=>'pichPub', 'toApp'=>'otherAppy.org'));
		$this->assertEqual($result, '');

		echo "(acct.create)";
		$result = $this->testParams('mlsn.org', 'otherAppy.org', 
			array('user'=>'pich', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.CREATE', 'creationToken'=>'Welcome there too Pich', 'tokenOrigin'=>'testApp.org', 'pubPass'=>'pichPub', 'subPass'=>'pichSub'));
		$this->assertEqual($result, '');

		echo "(acct.disappear)";
		$result = $this->testParams('mlsn.org', 'testApp.org', 
			array('user'=>'pich', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.DISAPPEAR', 'pubPass'=>'pichPub'));
		$this->assertEqual($result, '');
	}
	function runAll() {
		$this->loadFixture('UJ');
		

		echo "testProtocolViolations:\n";$this->testProtocolViolations();echo "\n";
		echo "testEachCommand:\n";$this->testEachCommandOnce();echo "\n";
	}
}
