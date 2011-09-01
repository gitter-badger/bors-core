<?php

class driver_pdo
{
	protected $connection = NULL;
	protected $database = NULL;
	private $statement = NULL;

	function __construct($database = NULL)
	{
		if(empty($database))
			$database = config('pdo_db_default');

		$this->database = $database;

		$this->_reconnect();
	}

	static function dsn($db_name)
	{
		if($dsn = configh('pdo_access', $db_name, 'dsn'))
			return $dsn;

		return configh('pdo_access', $db_name, 'driver')
			.':dbname='.configh('pdo_access', $db_name, 'db_name', $db_name).
			';host='.configh('pdo_access', $db_name, 'host', '127.0.0.1').';';
	}

	protected function _reconnect()
	{
		debug_timing_start('pdo_connect');

		$this->connection = new PDO(
			self::dsn($this->database),
			configh('pdo_access', $this->database, 'user'),
			configh('pdo_access', $this->database, 'password')
		);
		debug_timing_stop('pdo_connect');
	}

	function connection() { return $this->connection; }

	function query($query)
	{
		debug_timing_start('pdo_query');
		$result = $this->connection->query($query);
		debug_timing_stop('pdo_query');

		$err = $this->connection->errorInfo();
		if($err[0] != "00000")
			return bors_throw("PDO error on query «{$query}»: ".print_r($err, true));

		return $result;
	}

	function exec($query)
	{
		debug_timing_start('pdo_exec');
		$result = $this->connection->exec($query);
		debug_timing_stop('pdo_exec');

		$err = $this->connection->errorInfo();
		if($err[0] != "00000")
			return bors_throw("PDO error on exec «{$query}»:\n".print_r($err, true));

		return $result;
	}

	function prepare($sql, $driver_options = array())
	{
		$this->statement = $this->connection->prepare($sql, $driver_options);
	}

	function execute($args)
	{
		$this->statement->execute($args);
	}

	function fetch()
	{
		debug_timing_start('pdo_fetch');
		$row = $this->row;
		$ics = config('internal_charset');
		$dcs = configh('pdo_access', $this->database, 'charset');

		if($row && $ics != $dcs)
		{
			$ics .= '//IGNORE';
			foreach($row as $k => $v)
				$row[$k] = iconv($dcs, $ics, $v);
		}

		debug_timing_stop('pdo_fetch');
		return $row;
	}

	function get($query)
	{
		$row = NULL;
		foreach($this->connection->query($query) as $row)
			break;

		$err = $this->connection->errorInfo();
		if($err[0] != "00000")
			return bors_throw("PDO error on query «{$query}»: ".print_r($err, true));

		$ics = config('internal_charset');
		$dcs = configh('pdo_access', $this->database, 'charset');

		if($row && $ics != $dcs)
		{
			$ics .= '//IGNORE';
			foreach($row as $k => $v)
				$row[$k] = iconv($dcs, $ics, $v);
		}

		return $row;
	}

	function get_array($query)
	{
		$ics = config('internal_charset');
		$icsi = $ics . '//IGNORE';
		$dcs = configh('pdo_access', $this->database, 'charset');

		$result = array();
		$res = $this->connection->query($query);

		$err = $this->connection->errorInfo();
		if($err[0] != "00000")
			return bors_throw("PDO error on query «{$query}»: ".print_r($err, true));

		while($assoc = $res->fetch(PDO::FETCH_ASSOC))
		{
			if($ics == $dcs)
				$result[] = $assoc;
			else
			{
				$row = array();
				foreach($assoc as $key => $value)
					$row[$key] = iconv($dcs, $icsi, $value);
				$result[] = $row;
			}

		}

		return $result;
	}

	function close() { }

	// Прочитать одну строку или одно значение
	function select($table, $fields, $where)
	{
		$query = 'SELECT '.$fields.' FROM '.$table.' '.$this->args_compile($where);
//		$query = str_replace('`', '"', $query);
//		echo $query."\n";
		return $this->get($query);
	}

	function select_array($table, $fields, $where)
	{
		$query = 'SELECT '.$fields.' FROM '.$table.' '.$this->args_compile($where);
//		echo "select array: $query\n";
//		$query = str_replace('`', '"', $query);
		return $this->get_array($query);
	}

	function args_compile($where)
	{
		return mysql_args_compile($where);
	}

	function make_string_values($array, $with_keys = true)
	{
		$values = array();
		if($with_keys)
		{
			$keys = array();
			foreach($array as $k => $v)
			{
				$this->normkeyval($k, $v);
				$keys[] = $k;
				$values[] = $v;
			}

			return " (".join(",", $keys).") VALUES (".join(",", $values).") ";
		}
		else
		{
			foreach($array as $k => $v)
			{
				$this->normkeyval($k, $v);
				$values[] = $v;
			}

			return " (".join(",", $values).") ";
		}
	}

	function make_string_set($array)
	{
		$set = array();

		foreach($array as $k => $v)
		{
			$this->normkeyval($k, $v);
			$set[] = "$k = $v";
		}
		return " SET ".join(",", $set)." ";
	}

	function normkeyval(&$key, &$value)
	{
		if($key[0] == 'i' && preg_match('!^int (.+)$!', $key, $m))
		{
			$key = ($m[1][0] == '`') ? $m[1] : '`'.$m[1].'`';
			return;
		}

		if(preg_match('/^(\S+) (.+?)$/', $key, $m))
		{
			$type = $m[1];
			$key = $m[2];
		}
		else
		{
			$value = is_null($value) ? "NULL" : $this->connection->quote($value);

			if($key[0] != '`')
				$key = "`$key`";

			return;
		}

		if($value === NULL)
			$value = "NULL";
		else
		{
			switch($type)
			{
				case 'raw':
					break;
				case 'float':
					$value = str_replace(',', '.', floatval($value));
					break;
				default:
					$value = $this->connection->quote($value);
			}
		}

		if($key[0] != '`')
			$key = "`$key`";
	}

	function insert($table, $fields)
	{
// Шит. Нельзя использовать prepare, так как передаваться могут готовые SQL-функции.
//		$keys = array_keys($fields);
//		$query = "INSERT INTO $table (".join(',', $keys).") VALUES (".join(',', array_map(create_function('$name', 'return ":$name";'), $keys)).")";
//		foreach($fields as $name => $value)
//			$this->connection->bindParam(":$name", );
//		$this->prepare($query);
//		$this->execute(array_values($fields));
//		echo "INSERT INTO $table ".$this->make_string_values($fields).PHP_EOL;
		$this->exec("INSERT INTO $table ".$this->make_string_values($fields));
	}

	function update($table, $where, $fields)
	{
		$where['*set'] = $this->make_string_set($fields);
		return $this->query("UPDATE `".addslashes($table)."` ".$this->args_compile($where));
	}

	function last_id() { return $this->connection->lastInsertId(); }
}
