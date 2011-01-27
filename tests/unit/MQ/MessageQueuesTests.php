<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'MessageQueues.php';

class MessageQueuesTests extends UnitTests {
	function testReceive() {
		echo "(receive 1 dont delete)";
		$value = MessageQueues::receive(8, 123, 'abcd/efg', false, 1);
		$this->assertEqual($value, '[{"cmd":"cmd SEND - bla - etc","pubSign":"msg yours truly"}]');

		echo "(receive 0 delete)";
		$value = MessageQueues::receive(8, 123, 'abcd/efg', true, 0);
		$this->assertEqual($value, '[]');

		echo "(receive 5 dont delete)";
		$value = MessageQueues::receive(8, 123, 'abcd/efg', false, 5);
		$this->assertEqual($value, '[{"cmd":"cmd SEND - bla - etc","pubSign":"msg yours truly"}]');

		echo "(receive 3 delete)";
		$value = MessageQueues::receive(8, 123, 'abcd/efg', true, 3);
		$this->assertEqual($value, '[{"cmd":"cmd SEND - bla - etc","pubSign":"msg yours truly"}]');

		echo "(receive 1 after delete)";
		$value = MessageQueues::receive(8, 123, 'abcd/efg', false, 1);
		$this->assertEqual($value, '[]');
	}
	function testSend() {
		echo "(checking mailbox is empty)";
		$value = MessageQueues::receive(12, 24, 'abcd/', false, 1);
		$this->assertEqual($value, '[]');

		echo "(sending)";
		MessageQueues::send(12, 24, 'abcd/', 'hiya1', 'cheers1');
		echo ".(receive 5 dont delete)";
		$value = MessageQueues::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"cmd":"hiya1","pubSign":"cheers1"}]');

		echo "(sending)";
		MessageQueues::send(12, 24, 'abcd/', 'hiya2', 'cheers2');
		echo ".(receive 5 dont delete)";
		$value = MessageQueues::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"cmd":"hiya1","pubSign":"cheers1"},{"cmd":"hiya2","pubSign":"cheers2"}]');

		echo "(sending)";
		MessageQueues::send(12, 24, 'abcd/', 'hiya3', 'cheers3');
		echo ".(receive 5 dont delete)";
		$value = MessageQueues::receive(12, 24,'abcd/', false, 5);
		$this->assertEqual($value, '[{"cmd":"hiya1","pubSign":"cheers1"},{"cmd":"hiya2","pubSign":"cheers2"},{"cmd":"hiya3","pubSign":"cheers3"}]');

		echo "(receive 2 delete)";
		$value = MessageQueues::receive(12, 24, 'abcd/', true, 2);
		$this->assertEqual($value, '[{"cmd":"hiya1","pubSign":"cheers1"},{"cmd":"hiya2","pubSign":"cheers2"}]');

		echo "(receive 5 dont delete)";
		$value = MessageQueues::receive(12, 24, 'abcd/', false, 5);
		$this->assertEqual($value, '[{"cmd":"hiya3","pubSign":"cheers3"}]');
	}
	function runAll() {
		$this->loadFixture('MessageQueues');
		echo "testReceive:\n";$this->testReceive();echo "\n";
		echo "testSend:\n";$this->testSend();echo "\n";
	}
}
