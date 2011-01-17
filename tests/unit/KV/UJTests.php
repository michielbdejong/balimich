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
			array('protocol' => 'KeyValue-0.2',
				'command'=>array(
					'user'=>'mich@hotmail.com',
					'method'=>'GET',
					'keyHash'=>'abcd/efg'
				)
			));
		$this->assertEqual($result, json_encode(array('value'=>null, 'PubSign'=>'')));

		echo "(kv.set)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol' => 'KeyValue-0.2',
				'command' => array(
					'user'=>'mich@hotmail.com',
					'method'=>'SET', 
					'keyHash'=>'abcd/efg',
					'value'=>'hello there',
				),
				'pubSign' => 'yours truly',
				'password' => 'michPass'));
		$this->assertEqual($result, 'ok');

		echo "(kv.get)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol' => 'KeyValue-0.2',
				'command' => array(
					'user' => 'mich@hotmail.com',
					'method' => 'GET',
					'keyHash'=>'abcd/efg'
				)
			));
		$this->assertEqual($result, '{"value":"hello there","PubSign":"yours truly"}');

		echo "(acct.register)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'KeyValue-0.2', 
				'command' => array(
					'user' => 'pich@hotmail.com',
					'method' => 'REGISTER',
				),
				'password'=>'pichPass'
				));
		$this->assertEqual($result, 'ok');

		echo "(acct.confirm)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'KeyValue-0.2',
				'command' => array(
					'method' => 'CONFIRM',
					'registrationToken'=>'4025e26727941e4f83398f4aef035b36',
				),
				'password'=>'pichPass'));
		$this->assertEqual($result, 'ok');

		echo "(acct.disappear)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'pich', 'emailDomain'=>'hotmail.com', 'protocol'=>'KeyValue-0.2', 'action'=>'ACCT.DISAPPEAR', 'pubPass'=>'pichPub'));
		$this->assertEqual($result, 'ok');

		echo "(acct.emigrate)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'KeyValue-0.2', 'action'=>'ACCT.EMIGRATE', 'pubPass'=>'michPub', 'migrationToken'=>'here we go', 'toNode'=>'balimich.org'));
		$this->assertEqual($result, 'ok');

		echo "(acct.immigrate)";
		$result = $this->testParams('unhosted.balimich.org', 'http://testApp.org/', 
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'KeyValue-0.2', 'action'=>'ACCT.IMMIGRATE', 'pubPass'=>'michPubNew', 'subPass'=>'michSubNew', 'migrationToken'=>'here we go', 'fromNode'=>'mlsn.org'));
		$this->assertEqual($result, 'ok');

		echo "(acct.migrate)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/',
			array('emailUser'=>'mich', 'emailDomain'=>'hotmail.com', 'protocol'=>'KeyValue-0.2', 'action'=>'ACCT.MIGRATE', 'migrationToken'=>'here we go', 'delete'=>'false', 'limit'=>'3', 'needValue'=>'true'));
		$this->assertEqual($result, array('KV'=>array(), 'MSG'=>array()));
	}
	function runAll() {
		$this->loadFixture('UJ');
		

		echo "testProtocolViolations:\n";$this->testProtocolViolations();echo "\n";
		echo "testEachCommand:\n";$this->testEachCommandOnce();echo "\n";
	}
}
