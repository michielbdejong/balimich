<?php
file_put_contents('/tmp/mich.log', "\n\nHOST:".$_SERVER['HTTP_HOST']." REFERER:".$_SERVER['HTTP_REFERER']." POST:".var_export($_POST, true)."\n", FILE_APPEND);
require_once 'config.php';

class HttpOk {//could extend Exception here but that would be inaccurate
	private $msg;
	public function __construct($msg) {
		$this->msg = $msg;
	}
	public function getMessage() {
		return $this->msg;
	}	
	public function getHeader() {
return '';//		return "HTTP/1.1 200 OK";
	}
}
class HttpServiceUnavailable extends Exception {
	function getHeader() {
		return "HTTP/1.1 513 Service Unavailable";
	}
}
class HttpBadRequest extends Exception {
	function getHeader() {
		"HTTP/1.1 400 Bad Request";
	}
}
class HttpGone extends Exception{ 
	function getHeader() {
		return "HTTP/1.1 410 Gone";
	}
}
class HttpForbidden extends Exception{
	function getHeader() {
		return "HTTP/1.1 402 Forbidden";
	}
}
class HttpInternalServerError extends Exception{
	function getHeader() {
		return "HTTP/1.1 500 Internal Server Error";
	}
}

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
if(isset($_GET['captchaFor'])) {
	if(!isset($_SERVER['HTTP_REFERER'])) {
		$app = 'no_referer';
	} else {
		$refererParts = explode('/', $_SERVER['HTTP_REFERER']);
		$app = $refererParts[2];
	}
	$email = $_GET['captchaFor'];
	list($user, $node) = explode('@', $email);
	header('Content-Type: image/jpeg');
	echo file_get_contents(Accounts::giveCaptchaFor($user, $node, $app));
	die();
}
try {
	$uj = new UnhostedJSONParser();
	$response = $uj->parse();
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
