<?php
require_once 'TestingConfig.php';
require_once BASE_DIR . 'Http.php';//for HTTP exceptions

echo "\nStorage\n=======\n";
require_once 'StorageTests.php';

$storageTests = new StorageTests();
$storageTests->runAll();

echo "\nSecurity\n========\n";
require_once 'SecurityTests.php';

$accountsTests = new SecurityTests();
$accountsTests->runAll();

echo "\nAccounts\n========\n";
require_once 'AccountsTests.php';

$accountActionsTests = new AccountsTests();
$accountActionsTests->runAll();

echo "\nKeyValue\n========\n";
require_once 'KeyValueTests.php';

$keyValueTests = new KeyValueTests();
$keyValueTests->runAll();

echo "\nUJ\n========\n";
require_once 'UJTests.php';

$ujTests = new UJTests();
$ujTests->runAll();

echo "\nDone.\n=====\n\n";
