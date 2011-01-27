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
			array('protocol' => 'UJJP/0.2;KeyValue-0.2',
				'command'=>json_encode(array(
					'user'=>'mich@hotmail.com',
					'method'=>'GET',
					'keyHash'=>'abcd/efg'
				))
			));
		$this->assertEqual($result, json_encode(array('cmd'=>null, 'pubSign'=>null)));

		echo "(kv.set)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol' => 'UJJP/0.2;KeyValue-0.2',
				'command' => json_encode(array(
					'user'=>'mich@hotmail.com',
					'method'=>'SET', 
					'keyHash'=>'abcd/efg',
					'value'=>'hello there',
				)),
				'pubSign' => 'yours truly',
				'password' => 'michPass'));
		$this->assertEqual($result, 'ok');

		echo "(kv.get)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol' => 'UJJP/0.2;KeyValue-0.2',
				'command' => json_encode(array(
					'user' => 'mich@hotmail.com',
					'method' => 'GET',
					'keyHash'=>'abcd/efg'
				))
			));
		$this->assertEqual($result, '{"cmd":"{\\"user\\":\\"mich@hotmail.com\\",\\"method\\":\\"SET\\",\\"keyHash\\":\\"abcd\\\\\\/efg\\",\\"value\\":\\"hello there\\"}","pubSign":"yours truly"}');

		echo "(acct.register)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2', 
				'command' => json_encode(array(
					'user' => 'pich@hotmail.com',
					'method' => 'REGISTER',
				)),
				'password'=>'pichPass'
				));
		$this->assertEqual($result, 'ok');

		echo "(acct.confirm)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2',
				'command' => json_encode(array(
					'user' => 'pich@hotmail.com',
					'method' => 'CONFIRM',
				)),
				'registrationToken'=>'4025e26727941e4f83398f4aef035b36',
				'password'=>'pichPass'));
		$this->assertEqual($result, 'ok');

		echo "(acct.disappear)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2', 
				'command' => json_encode(array(
					'user' => 'pich@hotmail.com',
					'method' => 'DISAPPEAR',
				)),
				'password'=>'pichPass'
				));
		$this->assertEqual($result, 'ok');

		echo "(acct.emigrate)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2', 
				'command' => json_encode(array(
					'user' => 'mich@hotmail.com',
					'method' => 'EMIGRATE',
					'toNode' => 'balimich.org',
				)),
				'password'=>'michPass',
				'migrationToken'=>'here we go',
				));
		$this->assertEqual($result, 'ok');

		echo "(acct.immigrate)";
		$result = $this->testParams('unhosted.balimich.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2', 
				'command' => json_encode(array(
					'user' => 'mich@hotmail.com',
					'method' => 'IMMIGRATE',
					'fromNode' => 'mlsn.org',
				)),
				'password'=>'michPassNew',
				'migrationToken'=>'here we go',
				));
		$this->assertEqual($result, 'ok');

		echo "(acct.migrate)";
		$result = $this->testParams('unhosted.mlsn.org', 'http://testApp.org/', 
			array('protocol'=>'UJJP/0.2;KeyValue-0.2', 
				'command' => json_encode(array(
					'user' => 'mich@hotmail.com',
					'method' => 'MIGRATE',
					'toNode' => 'balimich.org',
					'delete' => false,
					'limit' => 3,
					'needValue' => true,
				)),
				'migrationToken'=>'here we go',
				));
		$this->assertEqual($result,   array (
			'abcd/efg' => array (
				'cmd' => '{"user":"mich@hotmail.com","method":"SET","keyHash":"abcd\\/efg","value":"hello there"}',
				'pubSign' => 'yours truly',
			)));
	}
	function runAll() {
		$this->loadFixture('UJ');
		echo "testProtocolViolations:\n";$this->testProtocolViolations();echo "\n";
		echo "testEachCommand:\n";$this->testEachCommandOnce();echo "\n";
	}
}
