<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Messages.php';

class MessagesTests extends UnitTests {
	function testReceive() {
		echo "(receive 1 dont delete)";
		$value = Messages::receive(8, 123, 'abcd/efg', false, 1);
		$this->assertEqual($value, '[{"value":"msg hello there","PubSign":"msg yours truly"}]');

		echo "(receive 0 delete)";
		$value = Messages::receive(8, 123, 'abcd/efg', true, 0);
		$this->assertEqual($value, '[]');

		echo "(receive 5 dont delete)";
		$value = Messages::receive(8, 123, 'abcd/efg', false, 5);
		$this->assertEqual($value, '[{"value":"msg hello there","PubSign":"msg yours truly"}]');

		echo "(receive 3 delete)";
		$value = Messages::receive(8, 123, 'abcd/efg', true, 3);
		$this->assertEqual($value, '[{"value":"msg hello there","PubSign":"msg yours truly"}]');

		echo "(receive 1 after delete)";
		$value = Messages::receive(8, 123, 'abcd/efg', false, 1);
		$this->assertEqual($value, '[]');
	}
	function testSend() {
		echo "(checking mailbox is empty)";
		$value = Messages::receive(12, 24, 'abcd/', false, 1);
		$this->assertEqual($value, '[]');

		echo "(sending)";
		Messages::send(12, 24, 'abcd/', 'hiya1', 'cheers1');
		echo ".(receive 5 dont delete)";
		$value = Messages::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"value":"hiya1","PubSign":"cheers1"}]');

		echo "(sending)";
		Messages::send(12, 24, 'abcd/', 'hiya2', 'cheers2');
		echo ".(receive 5 dont delete)";
		$value = Messages::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"value":"hiya1","PubSign":"cheers1"},{"value":"hiya2","PubSign":"cheers2"}]');

		echo "(sending)";
		Messages::send(12, 24, 'abcd/', 'hiya3', 'cheers3');
		echo ".(receive 5 dont delete)";
		$value = Messages::receive(12, 24,'abcd/', false, 5);
		$this->assertEqual($value, '[{"value":"hiya1","PubSign":"cheers1"},{"value":"hiya2","PubSign":"cheers2"},{"value":"hiya3","PubSign":"cheers3"}]');

		echo "(receive 2 delete)";
		$value = Messages::receive(12, 24, 'abcd/', true, 2);
		$this->assertEqual($value, '[{"value":"hiya1","PubSign":"cheers1"},{"value":"hiya2","PubSign":"cheers2"}]');

		echo "(receive 5 dont delete)";
		$value = Messages::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"value":"hiya3","PubSign":"cheers3"}]');
	}
	function runAll() {
		$this->loadFixture('Messages');
		echo "testReceive:\n";$this->testReceive();echo "\n";
		echo "testSend:\n";$this->testSend();echo "\n";
	}
}
