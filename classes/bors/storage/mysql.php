<?php

class bors_storage_mysql extends bors_storage implements Iterator
{
	static function load($object)
	{
		$select = array();
		$post_functions = array();
		foreach(bors_lib_orm::main_fields($object) as $f)
		{
			$x = $f['name'];
			if($f['name'] != $f['property'])
				$x .= " AS `{$f['property']}`";

			$select[] = $x;

			if(!empty($f['post_function']))
				$post_functions[$f['property']] = $f['post_function'];
		}

		$where = array('`'.$object->id_field().'`=' => $object->id());

		self::__join('left', $object, $select, $where, $post_functions);

		$dbh = new driver_mysql($object->db_name());
		config_set('debug_mysql_queries_log', 'false');
		$data = $dbh->select($object->table_name(), join(',', $select), $where);

		$object->data = $data;

		if(!empty($post_functions))
			self::post_functions_do($object, $post_functions);

		$object->set_loaded(true);

//		print_d($data);

		return true;
	}

	static function load_array($object, $where)
	{
		$select = array();
		$post_functions = array();
		foreach(bors_lib_orm::main_fields($object) as $f)
		{
			$x = $f['name'];
			if($f['name'] != $f['property'])
				$x .= ' AS '.$f['property'];

			$select[] = $x;

			if(!empty($f['post_function']))
				$post_functions[$f['property']] = $f['post_function'];
		}

		$dbh = new driver_mysql($object->db_name());

		self::__join('left', $object, $select, $where, $post_functions);

		$datas = $dbh->select_array($object->table_name(), join(',', $select), $where);
		$objects = array();
		$class_name = $object->class_name();
		foreach($datas as $data)
		{
			$object->set_id($data['id']);
			$object->data = $data;
			$object->set_loaded(true);
			$objects[] = $object;
			$object = new $class_name(NULL);
		}

		return $objects;
	}

	function save($object)
	{
//		print_d($object->changed_fields);
	}

    public function __construct($array)
    {
        if (is_array($array)) {
            $this->var = $array;
        }
    }

    public function rewind() {
        echo "rewinding\n";
        reset($this->var);
    }

    public function current() {
        $var = current($this->var);
        echo "current: $var\n";
        return $var;
    }

    public function key() {
        $var = key($this->var);
        echo "key: $var\n";
        return $var;
    }

    public function next() {
        $var = next($this->var);
        echo "next: $var\n";
        return $var;
    }

    public function valid() {
        $var = $this->current() !== false;
        echo "valid: {$var}\n";
        return $var;
    }

	static private function __join($type, $object, &$select, &$where, &$post_functions)
	{
		$main_db = $object->db_name();
		$main_table = $object->table_name();
		$main_id_field = $object->id_field();

		$join = $object->{$type.'_join_fields'}();
		if($join)
		{
			foreach($join as $db_name => $tables)
			{
				foreach($tables as $table_name => $fields)
				{
					if(!preg_match('/^(\w+)\((\w+)\)$/', $table_name, $m))
						throw new Exception('Unknown field format '.$table_name.' for class '.$object->class_name());

					$id_field = $m[2];
					$table_name = $m[1];

					$t = '';
					if($db_name != $main_db)
						$t = "`$db_name`.";

					$t .= "`$table_name`";

					$j = "$t ON `$main_table`.`$main_id_field` = $t.`$id_field`";
					$where[$type.'_join'][] = $j;

					foreach($fields as $property => $field)
					{
						$field = bors_lib_orm::field($property, $field);
						$x = "$t.`{$field['name']}`";//FIXME: предусмотреть возможность подключать FUNC(`field`)
						if($field['name'] != $field['property'])
							$x .= " AS `{$field['property']}`";

						$select[] = $x;

						if(!empty($field['post_function']))
							$post_functions[$field['property']] = $field['post_function'];
					}

				}
			}
		}
	}
}