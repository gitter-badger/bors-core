<?php

class cache_smart extends cache_base
{
	private $dbh;
	private $create_time;
	private $expire_time;

	function get($type, $key, $uri='', $default=NULL)
	{
		$this->init($type, $key, $uri);

		if(config('cache_disabled'))
			return $this->last = $default;

		debug_count_inc('smart_cache_gets_total');

		if($x = global_key('cache', $this->last_hmd))
			return $this->last = $x;

		if($memcache = config('memcached_instance'))
		{
			if($x = @$memcache->get('phpmv3'.$this->last_hmd))
			{
				debug_count_inc('smart_cache_gets_memcached_hits');
				return $this->last = $x;
			}
		}

		$dbh = new driver_mysql(config('cache_database'));
		$row = $dbh->select('cache', '*', array('raw hmd' => $this->last_hmd));
		$dbh->close(); $dbh = NULL;
		$this->last = $row['value'] ? @unserialize($row['value']) : $row['value'];

		$now = time();

		if($row['expire_time'] <= $now)
			$this->last = NULL;
		else
		{
			$this->create_time = $row['create_time'];
			$this->expire_time = $row['expire_time'];
		}

		$new_count = intval($row['count']) + 1;
		$rate = $row['saved_time'] * $new_count / (max($now - $row['create_time'], 1));

		if($this->last && $row['saved_time'] > 0.5)
		{
			@$GLOBALS['bors_stat_smart_cache_gets_db_hits']++;
			$dbh = new driver_mysql(config('cache_database'));
			$dbh->update('cache', array('hmd'=>$this->last_hmd), array (
				'int access_time' => $now, 
				'int count' => $new_count,
				'float rate' => $rate,
			));
			$dbh->close(); 
			$dbh = NULL;

			if($memcache = config('memcached_instance'))
			{
				$memcache->set('phpmv3'.$this->last_hmd, $this->last, MEMCACHE_COMPRESSED, $this->expire_time - time()+1);
				debug_count_inc('smart_cache_gets_memcached_updates');
			}
		}

		return ($this->last ? $this->last : $default);
	}

	function set($value, $time_to_expire = 86400, $infinite = false)
	{
//		if(config('is_debug')) echo "set($value, $time_to_expire, $infinite)<br/>";

//		echo "cd = ".config('cache_disabled')."<br/>";
		if(config('cache_disabled'))
			return $this->last = $value;

		set_global_key('cache', $this->last_hmd, $value);
		// Если время хранения отрицательное - используется только memcached, при его наличии.
		if($memcache = config('memcached_instance'))
		{
			$memcache->set('phpmv3'.$this->last_hmd, $value, MEMCACHE_COMPRESSED, abs($time_to_expire));
			debug_count_inc('smart_cache_gets_memcached_stores');

			if($time_to_expire < 0)
				return $this->last = $value;
		}

		$time_to_expire = abs($time_to_expire);

		$do_time = microtime(true) - $this->start_time;

//TODO: сделать настройку отключения. А то мусорит в логах
//		if($do_time < 0.01 && $time_to_expire > 0)
//			debug_hidden_log('cache-not-needed', $do_time);

		if($time_to_expire > 0 && $do_time > 0.01)
		{
			$dbh = new driver_mysql(config('cache_database'));
    		$dbh->replace('cache', array(
				'int hmd'	=> $this->last_hmd,
				'int type'	=> $this->last_type,
				'int key'	=> $this->last_key,
				'int uri'	=> $this->last_uri,
				'value'	=> serialize($value),
				'int access_time' => 0,
				'int create_time' => $infinite ? -1 : time(),
				'int expire_time' => time() + intval($time_to_expire),
				'int count' => 1,
				'float saved_time' => $do_time,
				'float rate' => 0,
			));
			$dbh->close(); $dbh = NULL;
		}

		return $this->last = $value;
	}
}
