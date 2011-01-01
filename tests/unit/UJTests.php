<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'UJ.php';
require_once BASE_DIR . 'Http.php';

class UJTests extends UnitTests {//TODO: this really tests the combination of UJ.php and Http.php - should split this up
	private function testParams($storageNode, $app, $postParams) {
		if($storageNode !== null) {
			$_SERVER['HTTP_HOST'] = $storageNode;
		}
		if($app !== null) {
			$_SERVER['HTTP_REFERER'] = $app;
		}
		$_POST = array();	
		foreach($postParams as $k=>$v) {
			$_POST[$k]=$v;
		}
		$uj = new UnhostedJSONParser();
		return $uj->parse(Http::obtainParams());
	}
	private function testProtocolViolations() {
	}
	private function testEachCommandOnce() {
		echo "(kv.get unset key)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/what/ever/file.html',
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'KV.GET', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub'));
		$this->assertEqual($result, json_encode(array('value'=>null, 'PubSign'=>'')));

		echo "(kv.set)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'KV.SET', 
				'keyPath'=>'abcd/efg', 'value'=>'hello there',
				'PubSign'=>'yours truly', 'pubPass'=>'michPub'));
		$this->assertEqual($result, '');

		echo "(kv.get)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'KV.GET', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub'));
		$this->assertEqual($result, '{"value":"hello there","PubSign":"yours truly"}');

		echo "(msg.receive empty)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'MSG.RECEIVE', 'keyPath'=>'abcd/efg', 'pubPass'=>'michPub', 'delete'=>'true', 'limit'=>'10'));
		$this->assertEqual($result, '[]');

		echo "(msg.send)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'MSG.SEND', 'keyPath'=>'abcd/efg', 'subPass'=>'michSub', 'value'=>'hiya', 'PubSign'=>'me'));
		$this->assertEqual($result, '');

		echo "(msg.receive)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'MSG.RECEIVE', 'keyPath'=>'abcd/efg', 'pubPass'=>'michPub', 'delete'=>'true', 'limit'=>'10'));
		$this->assertEqual($result, '[{"value":"hiya","PubSign":"me"}]');

		echo "(acct.register)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'pich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.REGISTER', 'pubPass'=>'pichPub', 'subPass'=>'pichSub'));
		$this->assertEqual($result, 'ok');

		echo "(acct.confirm)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'pich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.CONFIRM', 'registrationToken'=>'4025e26727941e4f83398f4aef035b36', 'pubPass'=>'pichPub'));
		$this->assertEqual($result, 'ok');

		echo "(acct.disappear)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'pich', 'emailDomain'=>'hotmail.com', 'protocol'=>'UJ/0.2', 'action'=>'ACCT.DISAPPEAR', 'pubPass'=>'pichPub'));
		$this->assertEqual($result, 'ok');
	}
	function runAll() {
		$this->loadFixture('UJ');
		

		echo "testProtocolViolations:\n";$this->testProtocolViolations();echo "\n";
		echo "testEachCommand:\n";$this->testEachCommandOnce();echo "\n";
	}
}
