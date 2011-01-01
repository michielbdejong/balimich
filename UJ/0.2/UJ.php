<?php
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'AccountsActions.php';
require_once BASE_DIR . 'KeyValue.php';
require_once BASE_DIR . 'Messages.php';

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
		 'ACCT.EMIGRATE' => array('pubPass' => true, 'toNode' => true, 'migrationToken' => true),
		'ACCT.IMMIGRATE' => array('fromNode' => true, 'migrationToken' => true, 'pubPass' => true, 'subPass' => true),
		  'ACCT.MIGRATE' => array('migrationToken' => true, 'delete' => true, 'limit' => true, 'needValue' => true, 'group' => false, 'keyPath' => false),
		                 ),
		);
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
	function parse($params) {
		$action = $this->checkFields($params);
		//switch(protocol) { case 'UJ/0.2':
		switch($action) {
			case 'KV.GET' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['subPass'], false);
				return KeyValue::get($accountId, $partition, $params['keyPath']);
			case 'KV.SET' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return KeyValue::set($accountId, $partition, $params['keyPath'], $params['value'], $params['PubSign']);
			case 'MSG.SEND' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['subPass'], false);
				return Messages::send($accountId, $partition, $params['keyPath'], $params['value'], $params['PubSign']);
			case 'MSG.RECEIVE' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return Messages::receive($accountId, $partition, $params['keyPath'], ($params['delete'] == 'true'), $params['limit']);
			case 'ACCT.CREATE' : 
				return Accounts::create($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['creationToken'], $params['pubPass'], $params['subPass']);
			case 'ACCT.GIVEPOPSHAKE' : 
				//this call is only here to throw exceptions as appropriate:
				Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return Accounts::givePopShake($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['toApp'], $params['creationToken'], $params['app']);
			case 'ACCT.DISAPPEAR' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return Accounts::disappear($accountId, $partition);
			case 'ACCT.GETSTATE' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return Accounts::getState($accountId, $partition);
			default:
				//shoudn't get here, because action was checked by checkFields.
				throw new HttpInternalServerError();
		}
		//}
	}
}
