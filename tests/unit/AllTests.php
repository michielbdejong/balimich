<?php
define('TESTS_DIR', '/var/www/beta/unhosted.balimich.org/tests/');
define('BASE_DIR', '/var/www/beta/unhosted.balimich.org/UJ/0.2/');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mlsnTest');

class HttpServiceUnavailable extends Exception{};
class HttpBadRequest extends Exception{};
class HttpNotFound extends Exception{};
class HttpGone extends Exception{};
class HttpForbidden extends Exception{};
class HttpInternalServerError extends Exception{};

echo "\nStorage\n=======\n";
require_once 'StorageTests.php';

$storageTests = new StorageTests();
$storageTests->runAll();

echo "\nAccounts\n========\n";
require_once 'AccountsTests.php';

$accountsTests = new AccountsTests();
$accountsTests->runAll();

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