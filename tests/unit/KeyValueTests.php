<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'KeyValue.php';

class KeyValueTests extends UnitTests {
	function testGet() {
		echo "(get existing key)";
		$value = KeyValue::get(8, 123, 'abcd/efg');
		$this->assertEqual($value, '{"value":"hello there","PubSign":"yours truly"}');
		
		echo "(get missing key)";
		try {
			$value = KeyValue::get(8, 123, 'abcd/efgh');
			$this->assertDontReachHere('get missing key');
		} catch (HttpNotFound $e) {
			echo ".";
		}

		echo "(get key for non-account)";
		try {
			$value = KeyValue::get(12, 24, 'abcd/efg');
			$this->assertDontReachHere('get key for non-account');
		} catch (HttpNotFound $e) {
			echo ".";
		}
	}
	function testSet() {
		echo "(setting)";
		KeyValue::set(8, 123, 'abcd/', 'something new', 'same person');

		echo ".(get existing key)";
		$value = KeyValue::get(8, 123, 'abcd/efg');
		$this->assertEqual($value, '{"value":"hello there","PubSign":"yours truly"}');
		
		echo "(get newly set key)";
		$value = KeyValue::get(8, 123, 'abcd/');
		$this->assertEqual($value, '{"value":"something new","PubSign":"same person"}');
		
		echo "(setting again)";
		KeyValue::set(8, 123, 'abcd/', 'something else', 'still me');

		echo ".(getting changed key)";
		$value = KeyValue::get(8, 123, 'abcd/');
		$this->assertEqual($value, '{"value":"something else","PubSign":"still me"}');
	}
	function runAll() {
		$this->loadFixture('KeyValue');
		echo "testGet:\n";$this->testGet();echo "\n";
		echo "testSet:\n";$this->testSet();echo "\n";
	}
}
