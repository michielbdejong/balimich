<?php

class Storage {
	private static $mysql = null;
	private static $memcache = null;

	private static function connect() {
		if(self::$mysql === null) {
			self::$mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}
		if(self::$memcache === null) {
			if(!self::$memcache = memcache_connect(MC_HOST, MC_PORT)) {
			}
		}
	}

	private static function mcGet($key) {
		$res = self::$memcache->get($key);
		return $res;
	}
	private static function mcSet($key, $value) {
		return self::$memcache->set($key, $value);
	}
	private static function mcDelete($key) {
		self::$memcache->delete($key);
	}

	public static function escape($str) {
		self::connect();
		return self::$mysql->real_escape_string($str);
	}

	public static function numRowsAffected() {
		//return self::$mysql->num_rows_affected;
		echo "TODO: implement numRowsAffected()\n";
		return 1;
	}	
	public static function query($mcKey, $mysqlQuery) {
file_put_contents('/tmp/mich.log', "query $mysqlQuery\n", FILE_APPEND);
		self::connect();
		if($mcKey != null) {
			self::mcDelete($mcKey);
		}
		$resource = self::$mysql->query($mysqlQuery);
		if($resource == false) {
file_put_contents('/tmp/mich.log', 'ise '.self::$mysql->error."\n", FILE_APPEND);
			throw new HttpInternalServerError(self::$mysql->error);
		}
		return $resource;
	}
	public static function queryArr($mcKey, $mysqlQuery) {
		if($mcKey != null) {
			self::connect();
			$mcResult = self::mcGet($mcKey);
			if($mcResult !== false) {
				return $mcResult;
			}
		}
		$result = array();
		$resource = self::query("", $mysqlQuery);
		if($resource == false) {
			throw new HttpInternalServerError(self::$mysql->error);
		}
		while($row = $resource->fetch_row()) {
			$result[] = $row;
		}
file_put_contents('/tmp/mich.log', 'queryArr result: '.var_export($result, true)."\n", FILE_APPEND);
		if($mcKey != null) {
			self::mcSet($mcKey, $result);
		}
		return $result;
	}
	public static function queryVal($mcKey, $mysqlQuery) {
		$result = self::queryArr($mcKey, $mysqlQuery);
		if(is_array($result) && count($result) == 1 && is_array($result[0]) && count($result[0] == 1)) {
	 		return  $result[0][0];
		} else {
			throw new HttpInternalServerError();
		}
	}
}
