<?php
require_once BASE_DIR . 'Security.php';
require_once BASE_DIR . 'Accounts.php';
require_once BASE_DIR . 'MessageQueues.php';

class UnhostedJSONParser {
	//protocol syntax definition:
	private $fields = array(
		'UJJP/0.2;MessageQueues-0.2' => array(
		     'SEND' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command' => array(
						'user' => true, 'method' => 'SEND', 'keyHash' => true, 'value' => true
					), 'pubSign' => true),
		  'RECEIVE' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command' => array(
						'user' => true, 'method' => 'RECEIVE', 'keyHash' => true, 'delete' => true, 'limit' => true
					), 'password' => true),
		            //
		 'REGISTER' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'method' => 'REGISTER', 'user' => true
					), 'password' => true),
		  'CONFIRM' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'method' => 'CONFIRM', 'user' => true
					), 'registrationToken' => true, 'password' => true),
		'DISAPPEAR' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'method' => 'DISAPPEAR', 'user' => true
					), 'password' => true),
		 'GETSTATE' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'method' => 'GETSTATE', 'user' => true
					), 'password' => true),
		 'EMIGRATE' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
					'method' => 'EMIGRATE', 'user' => true, 'toNode' => true
					), 'password' => true, 'migrationToken' => true),
		'IMMIGRATE' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'method' => 'IMMIGRATE', 'user' => true, 'fromNode' => true
					), 'migrationToken' => true, 'password' => true),
		  'MIGRATE' => array('storageNode' => true, 'app' => true, 'protocol' => 'UJJP/0.2;MessageQueues-0.2', 'command'=>array(
						'user' => true, 'method' => 'MIGRATE', 'delete' => true, 'limit' => true,
						'needValue' => true, 'keyHash' => false, 'toNode' => true,
					), 'migrationToken' => true),
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
				$this->checkSpec(json_decode($params[$fieldName], true), $spec[$fieldName]);
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
		$command = json_decode($params['command'], TRUE);
		if(!isset($command['method'])) {
			throw new HttpBadRequest('command.method not set');
		}
		if(!isset($this->fields[$params['protocol']][$command['method']])) {
			throw new HttpBadRequest("protocol/command.method '".var_export($command['method'], TRUE)."'not recognised");
		}
		$spec = $this->fields[$params['protocol']][$command['method']];
		$this->checkSpec($params, $spec);
		return array($params['protocol'], $command, $command['method']);
	}
	function parse($params) {
		list($protocol, $command, $method) = $this->checkFields($params);
		switch($protocol) { case 'UJJP/0.2;MessageQueues-0.2':
 		switch($method) {
			case 'SEND' : 
				list($accountId, $partition) = Security::getAccountIdWithoutPassword($command['user'], $params['storageNode'], $params['app']);
				return MessageQueues::send($accountId, $partition, $command['keyHash'], $params['command'], $params['pubSign']);
			case 'RECEIVE' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($command['user'], $params['storageNode'], $params['app'], $params['password']);
				return MessageQueues::receive($accountId, $partition, $command['keyHash'], ($command['delete'] == 'true'), $command['limit']);
			case 'REGISTER' : 
				return Accounts::register($command['user'], $params['storageNode'], $params['app'], $params['password']);
			case 'CONFIRM' : 
				//this call is only here to throw exceptions as appropriate:
				list($accountId, $partition) = Security::getAccountIdWithPassword($command['user'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::confirm($accountId, $partition, $params['registrationToken']);
			case 'DISAPPEAR' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($command['user'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::disappear($accountId, $partition);
			case 'GETSTATE' : 
				list($accountId, $partition) = Security::getAccountIdWithPassword($command['user'], $params['storageNode'], $params['app'], $params['password']);
				return Security::getState($accountId, $partition);
			case 'EMIGRATE' :
				list($accountId, $partition) = Security::getAccountIdWithPassword($command['user'], $params['storageNode'], $params['app'], $params['password']);
				return Accounts::emigrate($accountId, $partition, $command['toNode'], $params['migrationToken']);
			case 'IMMIGRATE' :
				return Accounts::immigrate($command['user'], $params['storageNode'], $params['app'], $params['password'], $params['migrationToken'], $command['fromNode']);
			case 'MIGRATE' :
				list($accountId, $partition) = Security::getAccountIdWithMigrationToken($command['user'], $params['storageNode'], $params['app'], $params['migrationToken']);
				if(!isset($command['keyHash'])) {
					$command['keyHash']=null;
				}
				return Accounts::migrate($accountId, $partition, $params['migrationToken'], $command['keyHash'], $command['needValue'], $command['delete'], $command['limit']);
			default:
				//shoudn't get here, because action was checked by checkFields.
				throw new HttpInternalServerError('input checking of command.method failed');
		}
		}
	}
}
