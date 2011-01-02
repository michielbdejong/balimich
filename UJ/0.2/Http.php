<?php
class Http {
	static public function obtainParams() {
		if(!isset($_SERVER['HTTP_HOST'])) {
			throw new HttpBadRequest('no http host set');
		}
		if(!isset($_SERVER['HTTP_REFERER'])) {
			throw new HttpBadRequest('no http referer set');
		}
		$refererParts = explode('/', $_SERVER['HTTP_REFERER']);
		if(count($refererParts) < 3) {
			throw new HttpForbidden('please specify a referer with at least two forward slashes in it');
		}
		$storageNode = substr($_SERVER['HTTP_HOST'], strlen('unhosted.'));
		if($storageNode == false) {
			throw new HttpForbidden('please specify a host that starts with "unhosted."');
		}
		$app = $refererParts[2];
		$_POST["storageNode"] = $storageNode;
		$_POST[ "app"] = $app;
		return $_POST;
	}
}

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
class HttpRedirect extends Exception {
	function getHeader() {
		"HTTP/1.1 302 Moved Permanently";
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
class HttpNotFound extends Exception{
	function getHeader() {
		return "HTTP/1.1 404 Not Found";
	}
}
