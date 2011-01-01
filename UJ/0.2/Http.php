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
