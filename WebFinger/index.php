<?php
header('Access-Control-Max-Age: 86400');
header('Content-Type: text/html');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

$uriParts = explode('@', $_GET['uri']);
if (true) {//($_GET['uri'] == 'acct:mich@balimich.org') {
	echo "<?xml version='1.0' encoding='UTF-8'?>\n"
		."<XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'>\n"
		."<Subject>".$_GET['uri']."</Subject>\n"
		."<Link rel='http://unhosted.org/spec/UJ/KV/0.2'\n"
		."\thref='http://unhosted.{$uriParts[1]}/UJ/0.2/' />\n"
		."<Link rel='http://unhosted.org/spec/UJ/MSG/0.2'\n"
		."\thref='http://unhosted.{$uriParts[1]}/UJ/0.2/' />\n"
		."</XRD>\n";
}
