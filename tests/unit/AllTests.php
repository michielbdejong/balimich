<?php
require_once 'TestingConfig.php';
require_once BASE_DIR . 'Http.php';//for HTTP exceptions

echo "\nStorage\n=======\n";
require_once 'StorageTests.php';

$storageTests = new StorageTests();
$storageTests->runAll();

echo "\nAccounts\n========\n";
require_once 'AccountsTests.php';

$accountsTests = new AccountsTests();
$accountsTests->runAll();

echo "\nAccountActions\n========\n";
require_once 'AccountActionsTests.php';

$accountActionsTests = new AccountActionsTests();
$accountActionsTests->runAll();

echo "\nKeyValue\n========\n";
require_once 'KeyValueTests.php';

$keyValueTests = new KeyValueTests();
$keyValueTests->runAll();

echo "\nMessages\n========\n";
require_once 'MessagesTests.php';

$messagesTests = new MessagesTests();
$messagesTests->runAll();


echo "\nUJ\n========\n";
require_once 'UJTests.php';

$ujTests = new UJTests();
$ujTests->runAll();

echo "\nDone.\n=====\n\n";
