<?php

// Обёртка для унифицированного использования PEAR::Log(http://pear.php.net/package/Log/)

require_once('Log.php');

class bors_log_pear extends base_object_db
{
	static function error($message, $type = 'COMMON') { self::logger($type)->log($message, PEAR_LOG_ERR); }
	static function warning($message, $type = 'COMMON') { self::logger($type)->log($message, PEAR_LOG_WARNING); }
	static function notice($message, $type = 'COMMON') { self::logger($type)->log($message, PEAR_LOG_NOTICE); }
	static function info($message, $type = 'COMMON') { self::logger($type)->log($message, PEAR_LOG_INFO); }
	static function debug($message, $type = 'COMMON') { self::logger($type)->log($message, PEAR_LOG_DEBUG); }

	function logger($ident) //TODO: тщательно проверить взаимодействие с base_object::logger()!
	{
		@list($handler, $name, $conf, $max_level) = config('pear.log', array('console'));
//		var_dump(array($handler, $name, $conf));
		if(!$handler)
			$handler = 'console';
		if(!$name)
			$name = '';
		if(!$ident)
			$ident = '';
		if(!$conf)
			$conf = array();
		if(!$max_level)
			$max_level = PEAR_LOG_DEBUG;

		return Log::singleton($handler, $name, $ident, $conf, $max_level);
	}

	function storage_engine() { return 'bors_storage_mysql'; }
//	function db_name() { return 'WWW'; }
	function table_name() { return 'pear_log'; }
	function table_fields()
	{
		return array(
			'id',
			'logtime',
			'ident',
			'priority',
			'message',
		);
	}
}
