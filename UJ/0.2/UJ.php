<?php
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'AccountActions.php';
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
	function checkFields($params) {
		if(!isset($params['protocol'])) {
			throw new HttpBadRequest('protocol not set');
		}
		if(!isset($this->fields[$params['protocol']])) {
			throw new HttpBadRequest('protocol not recognised');
		}
		if(!isset($params['action'])) {
			throw new HttpBadRequest('action not set');
		}
		if(!isset($this->fields[$params['protocol']][$params['action']])) {
			throw new HttpBadRequest('action not recognised');
		}
		foreach($params as $fieldName => $fieldObligatory) {
			if(in_array($fieldName, array('protocol', 'action', 'emailUser', 'emailDomain', 'storageNode', 'app'))) {
				//default parameter - OK
			} else if (isset($this->fields[$params['protocol']][$params['action']][$fieldName])) {
				//action-specific parameter -OK
			} else {
				throw new HttpBadRequest('unrecognised parameter '.$fieldName);
			}
		}
		foreach($this->fields[$params['protocol']][$params['action']] as $fieldName => $fieldObligatory) {
			if($fieldObligatory && !isset($params[$fieldName])) {
				throw new HttpBadRequest('missing field '.$fieldName);
			}
		}
		return $params['action'];
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
			case 'ACCT.REGISTER' : 
				return AccountActions::register($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], $params['subPass']);
			case 'ACCT.CONFIRM' : 
				//this call is only here to throw exceptions as appropriate:
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return AccountActions::confirm($accountId, $partition, $params['registrationToken']);
			case 'ACCT.DISAPPEAR' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return AccountActions::disappear($accountId, $partition);
			case 'ACCT.GETSTATE' : 
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return Accounts::getState($accountId, $partition);
			case 'ACCT.EMIGRATE' :
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], true);
				return AccountActions::emigrate($accountId, $partition, $params['toNode'], $params['migrationToken']);
			case 'ACCT.IMMIGRATE' :
				return AccountActions::immigrate($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['pubPass'], $params['subPass'], $params['migrationToken'], $params['fromNode']);
			case 'ACCT.MIGRATE' :
				list($accountId, $partition) = Accounts::getAccountId($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['migrationToken'], 'migrationToken', false);
				if(!isset($params['group'])) {
					$params['group']=null;
				}
				if(!isset($params['keyPath'])) {
					$params['keyPath']=null;
				}
				return AccountActions::migrate($accountId, $partition, $params['migrationToken'], $params['group'], $params['keyPath'], $params['needValue'], $params['delete'], $params['limit']);
			default:
				//shoudn't get here, because action was checked by checkFields.
				throw new HttpInternalServerError('action not recognized');
		}
		//}
	}
}
