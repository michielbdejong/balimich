<?php
require_once TESTS_DIR . 'unit/UnitTests.php';
require_once BASE_DIR . 'Storage.php';

class StorageTests extends UnitTests {
	function testQuery() {
		echo "(insert)";
		Storage::query("someMcKey", "INSERT INTO `testTable` (`foo`) VALUES ('bar')");
		echo ".(delete)";
		Storage::query("someMcKey", "DELETE FROM `testTable` WHERE `foo`='bar'");
		echo ".";
	}
		
	function testQueryArr() {
		echo "(insert)";
		Storage::query("someMcKey", "INSERT INTO `testTable` (`foo`) VALUES ('bar2')");
		echo ".(queryArr select)";
		$result = Storage::queryArr("someMcKey", "SELECT * FROM `testTable`");
		$this->assertEqual($result, array(array("bar2")));
		echo "(delete)";
		Storage::query("someMcKey", "DELETE FROM `testTable` WHERE `foo`='bar2'");
		echo ".";
	}
		
	function testEscape() {
		echo "(escape)";
		$escaped = Storage::escape("asdf' OR ''='");
		$this->assertEqual($escaped, "asdf\' OR \'\'=\'");
	}
	function runAll() {
		$this->loadFixture('Storage');
		echo "testEscape:\n";$this->testEscape();echo "\n";
		echo "testQuery:\n";$this->testQuery();echo "\n";
		echo "testQueryArr:\n";$this->testQueryArr();echo "\n";
	}
}
