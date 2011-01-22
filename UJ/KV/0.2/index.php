<?php
ini_set('display_errors', 1);
$_POST = json_decode(file_get_contents('php://input'), true);
file_put_contents('/tmp/mich.log', "\n\nHOST:".$_SERVER['HTTP_HOST']." REFERER:".$_SERVER['HTTP_REFERER']." POST:".var_export($_POST, true)."\n", FILE_APPEND);
require_once 'config.php';

require_once BASE_DIR . 'Http.php';
require_once BASE_DIR . 'UJ.php';

function HttpRespond($response) {
file_put_contents('/tmp/mich.log', "\nRESPONSE HEADER CODE:".$response->getHeader()." RESPONSE BODY: ".$response->getMessage()."\n", FILE_APPEND);
	//header($response->getHeader()); - haven't worked out yet how to catch the HttpRequest exceptions these cause!
	header('Access-Control-Max-Age: 86400');
	header('Content-Type: text/html');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	header('Access-Control-Max-Age: 86400');
	echo $response->getHeader();
	echo $response->getMessage();
}
try {
	$params = Http::obtainParams();
	$uj = new UnhostedJSONParser();
	$response = $uj->parse($params);
	HttpRespond(new HttpOk($response));
} catch (HttpServiceUnavailable $e) {
	HttpRespond($e);
} catch (HttpNotFound $e) {
	HttpRespond($e);
} catch (HttpBadRequest $e) {
	HttpRespond($e);
} catch (HttpForbidden $e) {
	HttpRespond($e);
} catch (HttpGone $e) {
	HttpRespond($e);
}
