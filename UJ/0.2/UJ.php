<?php
require_once BASE_DIR . 'KeyValue.php';
require_once BASE_DIR . 'Messages.php';
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'Migration.php';

class UnhostedJSONParser {
	//protocol syntax definition:
	private $fields = array(
		'UJ/0.2' => array(
		        'KV.GET' => array('keyPath' => true, 'subPass' => true),
	                'KV.SET' => array('keyPath' => true, 'value' => true, 'PubSign' => true, 'pubPass' => true),
		                 //
		      'MSG.SEND' => array('keyPath' => true, 'value' => true, 'PubSign' => true, 'subPass' => true),
		   'MSG.RECEIVE' => array('keyPath' => true, 'delete' => true, 'limit' => true, 'pubPass' => true),
		                 //
		 'ACCT.REGISTER' => array('pubPass' => true, 'subPass' => true),
		  'ACCT.CONFIRM' => array('registrationToken' => true, 'pubPass' => true),
		'ACCT.DISAPPEAR' => array('pubPass' => true),
		 'ACCT.GETSTATE' => array('pubPass' => true),
		                 //
		 'MIGR.EMIGRATE' => array('pubPass' => true, 'toNode' => true, 'migrationToken' => true),
		'MIGR.IMMIGRATE' => array('fromNode' => true, 'migrationToken' => true, 'pubPass' => true, 'subPass' => true),
		  'MIGR.MIGRATE' => array('migrationToken' => true, 'delete' => true, 'limit' => true, 'needValue' => true, 'group' => false, 'keyPath' => false),
		                 ),
		);
	function obtainParams() {
		if(!isset($_SERVER['HTTP_HOST'])) {
			throw new HttpBadRequest('no http host set');
		}
		if(!isset($_SERVER['HTTP_REFERER'])) {
			throw new HttpBadRequest('no http referer set');
		}
		$refererParts = explode('/', $_SERVER['HTTP_REFERER']);
		$node = substr($_SERVER['HTTP_HOST'], strlen('unhosted.'));
		$app = $refererParts[2];
		return array ("POST" => $_POST, "node" => $node, "app" => $app);
	}
	function checkFields($POST) {
		if(!isset($POST['protocol'])) {
			throw new HttpBadRequest('protocol not set');
		}
		if(!isset($this->fields[$POST['protocol']])) {
			throw new HttpBadRequest('protocol not recognised');
		}
		if(!isset($POST['action'])) {
			throw new HttpBadRequest('action not set');
		}
		if(!isset($this->fields[$POST['protocol']][$POST['action']])) {
			throw new HttpBadRequest('action not recognised');
		}
		foreach($POST as $fieldName => $fieldValue) {
			if($fieldName != 'protocol' && $fieldName != 'action' && $fieldName != 'emailUser' && $fieldName != 'emailDomain') {
				if(!isset($this->fields[$POST['protocol']][$POST['action']][$fieldName])) {
					throw new HttpBadRequest('unrecognised field '.$fieldName);
				}
			}
		}
		foreach($this->fields[$POST['protocol']][$POST['action']] as $fieldName => $fieldValue) {
			if(!isset($POST[$fieldName])) {
				throw new HttpBadRequest('missing field '.$fieldName);
			}
		}
		return $POST['action'];
	}
	function parse() {
		$params = $this->obtainParams();
		$action = $this->checkFields($params['POST']);
		//switch(protocol) { case 'UJ/0.2':
		switch($action) {
			case 'KV.GET' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['subPass'], false);
				return KeyValue::get($accountId, $partition, $params['POST']['keyPath']);
			case 'KV.SET' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['pubPass'], true);
				return KeyValue::set($accountId, $partition, $params['POST']['keyPath'], $params['POST']['value'], $params['POST']['PubSign']);
			case 'MSG.SEND' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['subPass'], false);
				return Messages::send($accountId, $partition, $params['POST']['keyPath'], $params['POST']['value'], $params['POST']['PubSign']);
			case 'MSG.RECEIVE' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['pubPass'], true);
				return Messages::receive($accountId, $partition, $params['POST']['keyPath'], ($params['POST']['delete'] == 'true'), $params['POST']['limit']);
			case 'ACCT.CREATE' : 
				return Accounts::create($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['creationToken'], $params['POST']['pubPass'], $params['POST']['subPass']);
			case 'ACCT.GIVEPOPSHAKE' : 
				//this call is only here to throw exceptions as appropriate:
				Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['pubPass'], true);
				return Accounts::givePopShake($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['POST']['toApp'], $params['POST']['creationToken'], $params['app']);
			case 'ACCT.DISAPPEAR' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['pubPass'], true);
				return Accounts::disappear($accountId, $partition);
			case 'ACCT.GETSTATE' : 
				list($accountId, $partition) = Accounts::getAccountId($params['POST']['emailUser'], $params['POST']['emailDomain'], $params['node'], $params['app'], $params['POST']['pubPass'], true);
				return Accounts::getState($accountId, $partition);
			default:
				//shoudn't get here, because action was checked by checkFields.
				throw new HttpInternalServerError();
		}
		//}
	}
}
