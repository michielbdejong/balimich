<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'KeyValue.php';

class KeyValueTests extends UnitTests {
	function testGet() {
		echo "(get existing key)";
		$value = KeyValue::get(8, 123, 'abcd/efg');
		$this->assertEqual($value, '{"cmd":"hello there","pubSign":"yours truly"}');
		
		echo "(get missing key)";
		$value = KeyValue::get(8, 123, 'abcd/efgh');
		$this->assertEqual($value, json_encode(array('cmd'=>null, 'pubSign'=>null)));

		echo "(get key for non-account)";//you couldn't do this over UJ because getAccountId would fail.
		$value = KeyValue::get(12, 24, 'abcd/efg');
		$this->assertEqual($value, json_encode(array('cmd'=>null, 'pubSign'=>null)));
	}
	function testSet() {
		echo "(setting)";
		KeyValue::set(8, 123, 'abcd/', 'something new', 'same person');

		echo ".(get existing key)";
		$value = KeyValue::get(8, 123, 'abcd/efg');
		$this->assertEqual($value, '{"cmd":"hello there","pubSign":"yours truly"}');
		
		echo "(get newly set key)";
		$value = KeyValue::get(8, 123, 'abcd/');
		$this->assertEqual($value, '{"cmd":"something new","pubSign":"same person"}');
		
		echo "(setting again)";
		KeyValue::set(8, 123, 'abcd/', 'something else', 'still me');

		echo ".(getting changed key)";
		$value = KeyValue::get(8, 123, 'abcd/');
		$this->assertEqual($value, '{"cmd":"something else","pubSign":"still me"}');
	}
	function runAll() {
		$this->loadFixture('KeyValue');
		echo "testGet:\n";$this->testGet();echo "\n";
		echo "testSet:\n";$this->testSet();echo "\n";
	}
}
