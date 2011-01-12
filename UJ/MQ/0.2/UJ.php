<?php
require_once BASE_DIR . 'Security.php';
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'Messages.php';

class UnhostedJSONParser {
	//protocol syntax definition:
	private $fields = array(
		'MessageQueues-0.2' => array(
		      'SEND' => array('keyHash' => true, 'value' => true, 'pubSign' => true, 'subPass' => true),
		   'RECEIVE' => array('keyHash' => true, 'delete' => true, 'limit' => true, 'password' => true),
		             //
		 'REGISTER' => array('password' => true, 'subPass' => true),
		  'CONFIRM' => array('registrationToken' => true, 'password' => true),
		'DISAPPEAR' => array('password' => true),
		 'GETSTATE' => array('password' => true),
		 'EMIGRATE' => array('password' => true, 'toNode' => true, 'migrationToken' => true),
		'IMMIGRATE' => array('fromNode' => true, 'migrationToken' => true, 'password' => true, 'subPass' => true),
		  'MIGRATE' => array('migrationToken' => true, 'delete' => true, 'limit' => true, 'needValue' => true, 'group' => false, 'keyHash' => false),
		            ),
		);
	function checkSpec($params, $spec) {
		//check for missing fields:
		foreach($spec as $fieldName => $fieldObligatory) {
			if($fieldObligatory && !isset($params[$fieldName])) {
				throw new HttpBadRequest('missing field '.$fieldName);
			}
		}
		//check fields present:
		foreach($params as $fieldName => $fieldValue) {
			if (!isset($spec[$fieldName])) {
				throw new HttpBadRequest('unrecognised parameter '.$fieldName);
			}
			if (is_string($spec[$fieldName]) && $params[$fieldName] != $spec[$fieldName]) {
				throw new HttpBadRequest('parameter '.$fieldName.' has value '.$params[$fieldName].' instead of '.$spec[$fieldName]);
			}
			if (is_array($spec[$fieldName])) {//recurse:
				$this->checkSpec($params[$fieldName], $spec[$fieldName]);
			}
		}
	}
	function checkFields($params) {
		if(!isset($params['protocol'])) {
			throw new HttpBadRequest('protocol not set');
		}
		if(!isset($this->fields[$params['protocol']])) {
			throw new HttpBadRequest('protocol not recognised');
		}
		if(!isset($params['command'])) {
			throw new HttpBadRequest('command not set');
		}
		if(!isset($params['command']['method'])) {
			throw new HttpBadRequest('command.method not set');
		}
		if(!isset($this->fields[$params['protocol']][$params['command']['method']])) {
			throw new HttpBadRequest('protocol/command.method not recognised');
		}
		$spec = $this->fields[$params['protocol']][$params['command']['method']];
		$this->checkSpec($params, $spec);
		return array($params['protocol'], $params['command']['method']);
	}
	function parse($params) {
		list($protocol, $method) = $this->checkFields($params);
		switch($protocol) { case 'KeyValue-0.2':
 		switch($method) {
			case 'GET' : 
				list($accountId, $partition) = Security::getAccountIdWithoutPassword($params['command']['user'], $params['storageNode'], $params['app']);
				return KeyValue::get($accountId, $partition, $params['command']['keyHash']);
			case 'SET' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['command']['user'], $params['storageNode'], $params['app'], $params['password']);
				return KeyValue::set($accountId, $partition, $params['command']['keyHash'], $params['command']['value'], $params['pubSign']);
			case 'MSG.SEND' : 
				list($accountId, $partition) = Security::getAccountIdWithoutPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['subPass']);
				return Messages::send($accountId, $partition, $params['keyHash'], $params['value'], $params['pubSign']);
			case 'MSG.RECEIVE' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password']);
				return Messages::receive($accountId, $partition, $params['keyHash'], ($params['delete'] == 'true'), $params['limit']);
			case 'ACCT.REGISTER' : 
				return Accounts::register($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password'], $params['subPass']);
			case 'ACCT.CONFIRM' : 
				//this call is only here to throw exceptions as appropriate:
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::confirm($accountId, $partition, $params['registrationToken']);
			case 'ACCT.DISAPPEAR' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::disappear($accountId, $partition);
			case 'ACCT.GETSTATE' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password']);
				return Security::getState($accountId, $partition);
			case 'ACCT.EMIGRATE' :
				list($accountId, $partition) = Security::getAccountIdWithPassword($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::emigrate($accountId, $partition, $params['toNode'], $params['migrationToken']);
			case 'ACCT.IMMIGRATE' :
				return Accounts::immigrate($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['password'], $params['subPass'], $params['migrationToken'], $params['fromNode']);
			case 'ACCT.MIGRATE' :
				list($accountId, $partition) = Security::getAccountIdWithMigrationToken($params['emailUser'], $params['emailDomain'], $params['storageNode'], $params['app'], $params['migrationToken']);
				if(!isset($params['group'])) {
					$params['group']=null;
				}
				if(!isset($params['keyHash'])) {
					$params['keyHash']=null;
				}
				return Accounts::migrate($accountId, $partition, $params['migrationToken'], $params['group'], $params['keyHash'], $params['needValue'], $params['delete'], $params['limit']);
			default:
				//shoudn't get here, because action was checked by checkFields.
				throw new HttpInternalServerError('action not recognized');
		}
		}
	}
}
