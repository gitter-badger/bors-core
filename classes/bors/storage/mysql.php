<?php

class bors_storage_mysql extends bors_storage implements Iterator
{
	function load($object)
	{
		$select = array();
		$post_functions = array();
		foreach(bors_lib_orm::main_fields($object) as $f)
		{
			if(preg_match('/^\w+$/', $name = $f['name']))
				$x = '`'.$name.'`'; // убирать апострофы нельзя, иначе тупо не работабт поля с некорректными именами
			else
				$x = $name;

			if($f['name'] != ($property = $f['property']))
				$x .= " AS `{$property}`";

			$select[] = $x;

			if(!empty($f['post_function']))
				$post_functions[$f['property']] = $f['post_function'];
		}

		$where = array('`'.$object->id_field().'`=' => $object->id());

		$dummy = array();
		self::__join('inner', $object, $select, $where, $post_functions, $dummy);
		self::__join('left',  $object, $select, $where, $post_functions, $dummy);

		$dbh = new driver_mysql($object->db_name());
		$data = $dbh->select($object->table_name(), join(',', $select), $where);

		if(!$data)
			return $object->set_loaded(false);

		$object->data = $data;

		if(!empty($post_functions))
			self::post_functions_do($object, $post_functions);

		$object->set_loaded(true);

//		print_d($data);

		return true;
	}

	static function post_functions_do(&$object, $post_functions)
	{
		foreach($post_functions as $property => $function)
			$object->set($property, $function($object->$property()), false);
	}

	function load_array($object, $where)
	{
		$by_id  = popval($where, 'by_id');
		$select = popval($where, 'select');
		$set    = popval($where, '*set');

		if(is_null($object))
		{
			$db_name = $where['*db'];
			$table_name = $where['*table'];
			unset($where['*db'], $where['*table']);
			$select = array('*');
			$class_name = 'base_object_db';
			$object = new base_object_db(NULL);
		}
		else
		{
			$db_name = $object->db_name();
			$table_name = $object->table_name();
			$class_name = $object->class_name();
			list($select, $where) = self::__query_data_prepare($object, $where);
		}

		$dbh = new driver_mysql($db_name);

		// формат: array(..., '*set' => 'MAX(create_time) AS max_create_time, ...')
		if($set)
			foreach(preg_split('/,\s*/', $set) as $s)
				$select[] = $s;

		$datas = $dbh->select_array($table_name, join(',', $select), $where, $class_name);
		$objects = array();

		foreach($datas as $data)
		{
			$object->set_id(@$data['id']);
			$object->data = $data;
			$object->set_loaded(true);

			if($by_id)
				$objects[$object->id()] = $object;
			else
				$objects[] = $object;

			$object = new $class_name(NULL);
		}

		return $objects;
	}

	function count($object, $where)
	{
		if(is_null($object))
		{
			$db_name = $where['*db'];
			$table_name = $where['*table'];
			unset($where['*db'], $where['*table']);
			$select = array('*');
			$class_name = 'base_object_db';
			$object = new base_object_db(NULL);
		}
		else
		{
			$db_name = $object->db_name();
			$table_name = $object->table_name();
			$class_name = $object->class_name();
			list($select, $where) = self::__query_data_prepare($object, $where);
		}

		$dbh = new driver_mysql($db_name);
		if(empty($where['group']))
			$count = $dbh->select($table_name, 'COUNT(*)', $where, $class_name);
		else
		{
			$dbh->select_array($table_name, 'COUNT(*)', $where, $class_name);
			$count = intval($dbh->get('SELECT FOUND_ROWS()'));
		}

		return $count;
	}

	static private function __query_data_prepare($object, $where)
	{
		$select = array();
		$post_functions = array();

		$table = $object->table_name();

		foreach(bors_lib_orm::main_fields($object) as $f)
		{
			if(preg_match('/^(\w+)\((\w+)\)$/', $field_name = $f['name'], $m))
				$x = $m[1].'('.$table.'.'.$m[2].')';
			elseif(preg_match('/^\w+\(.+\)$/', $field_name)) // id => CONCAT(keyword,":",keyword_id)
				$x = $field_name;
			else
				$x = $table.'.'.$field_name;

			if($field_name != $f['property'])
			{
//				var_dump($where);
//				echo "{$f['property']} -> {$f['name']}<br/>";
				$x .= " AS `{$f['property']}`";
				if(array_key_exists($f['property'], $where))
				{
					$where[$field_name] = $where[$f['property']];
					unset($where[$f['property']]);
				}
			}

			$select[] = $x;

			if(!empty($f['post_function']))
				$post_functions[$f['property']] = $f['post_function'];
		}

		$dummy = array();
		self::__join('inner', $object, $select, $where, $post_functions, $dummy);
		self::__join('left',  $object, $select, $where, $post_functions, $dummy);

//		$dbh = new driver_mysql($object->db_name());
		return array($select, $where, $post_functions);
	}

	static private function __update_data_prepare($object, $where)
	{
		$_back_functions = array(
			'html_entity_decode' => 'htmlspecialchars',
			'UNIX_TIMESTAMP' => 'FROM_UNIXTIME',
			'aviaport_old_denormalize' => 'aviaport_old_normalize',
			'stripslashes' => 'addslashes',
		);

		$update = array();
		$db_name = $object->db_name();
		$table_name = $object->table_name();

		$skip_joins = false;

		foreach(bors_lib_orm::main_fields($object) as $f)
		{
//			echo "{$f['property']} => {$f['name']}: ".$object->get($f['property'])."<br/>\n";
			$field_name = $f['name'];

//			Сюда сунуть обратное преобразование
//			if(!empty($f['post_function']))
//				$post_functions[$f['property']] = $f['post_function'];

			if(preg_match('/^(\w+)\(([\w`]+)\)$/', $field_name, $m))
			{
				$field_name = $m[2];
				$sql = $_back_functions[$m[1]];
			}
			elseif(preg_match('/^\w+\(.+\)$/', $field_name)) // id => CONCAT(keyword,":",keyword_id)
			{
//				echo "skip $field_name\n";
				$skip_joins = true;
				continue;
			}
			else
				$sql = false;

			if(array_key_exists($f['property'], $object->changed_fields))
			{
				if($sql)
					$update[$db_name][$table_name][$f['name']] = $sql.'('.$object->get($f['property']).')';
				else
					$update[$db_name][$table_name][$f['name']] = $object->get($f['property']);
			}
		}

//		var_dump($update);
//		echo "skip = $skip_joins\n";
		$select = array(); // dummy
		if(!$skip_joins)
		{
			self::__join('inner', $object, $select, $where, $post_functions, $update);
			self::__join('left',  $object, $select, $where, $post_functions, $update);
			if(($idf = $object->id_field()) && empty($update[$db_name][$table_name][$idf]))
				$update[$db_name][$table_name][$idf] = $object->id();
		}


//		$dbh = new driver_mysql($object->db_name());
		return array($update, $where);
	}

	function save($object)
	{
//		var_dump($object->id());
		$where = array($object->id_field() => $object->id());
		list($update, $where) = self::__update_data_prepare($object, $where);

		$update_plain = array();
		foreach($update as $db_name => $tables)
			foreach($tables as $table_name => $fields)
			{
				unset($fields['*id_field']);
				$update_plain = array_merge($update_plain, $fields);
			}

		if(!$update_plain)
			return;

		$dbh = new driver_mysql($object->db_name());
		$dbh->update($object->table_name(), $where, $update_plain);
	}

	private $data;
	private $dbi;
	private $object;
	private $__class_name;

	static function each($class_name, $where)
	{
		$object = new $class_name(NULL);
		list($select, $where) = self::__query_data_prepare($object, $where);
		$db_name = $object->db_name();
		$table_name = $object->table_name();

		$iterator = new bors_storage_mysql();
		$iterator->object = $object;
		$iterator->__class_name = $class_name;
		$iterator->dbi = driver_mysql::factory($db_name)->each($table_name, join(',', $select), $where);
		return $iterator;
	}

    public function key() { } // Not implemented

    public function current() { return $this->object; }

    public function next()
    {
		$this->data = $this->dbi->next();
		return $this->__init_object();
    }

    public function rewind()
    {
		$this->data = $this->dbi->rewind();
		return $this->__init_object();
    }

    public function valid() { return $this->data != false; }

	private function __init_object()
	{
		$data = $this->data;
		$class_name = $this->__class_name;
		$object = new $class_name($data['id']);
//		$object->set_id($data['id']);
		$object->data = $data;
		$object->set_loaded(true);
		return $this->object = $object;
	}

	static private function __join($type, $object, &$select, &$where, &$post_functions, &$update)
	{
		$where['*class_name'] = $object->class_name();

		$main_db = $object->db_name();
		$main_table = $object->table_name();
		$main_id_field = $object->id_field();

		$join = $object->get("{$type}_join_fields");
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

					$update[$db_name][$table_name]['*id_field'] = $id_field;
					foreach($fields as $property => $field)
					{
						$field = bors_lib_orm::field($property, $field);
						$x = "$t.`{$field['name']}`";//FIXME: предусмотреть возможность подключать FUNC(`field`)
						if($field['name'] != $field['property'])
							$x .= " AS `{$field['property']}`";

						$select[] = $x;

						if(!empty($field['post_function']))
							$post_functions[$field['property']] = $field['post_function'];

//						echo "{$field['property']} => {$field['name']}: ".$object->get($field['property'])."<br/>\n";
						if(!empty($object->changed_fields) && array_key_exists($field['property'], $object->changed_fields))
							$update[$db_name][$table_name][$field['name']] = $object->get($field['property']);
					}
				}
			}
		}
	}

	function create($object)
	{
		$where = array();
		list($data, $where) = self::__update_data_prepare($object, $where);

		if(!$data)
			return;

		$main_table = true;

		foreach($data as $db_name => $tables)
		{
			$dbh = new driver_mysql($db_name);
			foreach($tables as $table_name => $fields)
			{
				if(!$main_table)
				{
					$id_field = $fields['*id_field'];
					unset($fields['*id_field']);
					$fields[$id_field] = $new_id;
				}

				debug_hidden_log("inserts", "insert $table_name, ".print_r($fields, true));

				if($object->replace_on_new_instance() || $object->attr('__replace_on_new_instance'))
					$dbh->replace($table_name, $fields);
				elseif($object->ignore_on_new_instance())
					$dbh->insert_ignore($table_name, $fields);
				else
					$dbh->insert($table_name, $fields);

				if($main_table)
				{
					$main_table = false;
					$new_id = $dbh->last_id();
				}
			}
		}

		$object->set_id($new_id);
//		echo "New id=$new_id, {$object->id()}<br/>";
//		exit();
	}

	function delete($object)
	{
		if(method_exists($object, 'on_delete'))
			$object->on_delete();

		$update = array();
		$where  = array();
		$select = array();
		$post_functions = array();
		self::__join('inner', $object, $select, $where, $post_functions, $update);
		self::__join('left',  $object, $select, $where, $post_functions, $update);

		foreach($update as $db_name => $tables)
		{
			$dbh = new driver_mysql($db_name);
			foreach($tables as $table_name => $fields)
				$dbh->delete($table_name, array($fields['*id_field'] => $object->id()));
		}

			$dbh = new driver_mysql($object->db_name());
			$dbh->delete($object->table_name(), array($object->id_field() => $object->id()));
	}

	static function create_table($class_name)
	{
		$map = array(
			'string'	=>	'VARCHAR(255)',
			'text'		=>	'TEXT',
			'int'		=>	'INT',
			'uint'		=>	'INT UNSIGNED',
			'bool'		=>	'TINYINT(1) UNSIGNED',
			'float'		=>	'FLOAT',
			'enum'		=>	'ENUM(%)',
		);

		$db_fields = array();

		$class = new $class_name(NULL);

		$db_name = $class->db_name();
		$table_name = $class->table_name();
		$fields = $class->table_fields();

		$object_fields = array_smart_expand($fields);
		$db_fields = array();
		$primary = false;

		foreach(bors_lib_orm::main_fields($class) as $field)
		{
//			var_dump($field);
			$db_field = '`'.$field['name'].'` '.$map[$field['type']];
			if($field['property'] == 'id')
			{
				$db_field .= ' AUTO_INCREMENT';
				$primary = $field['name'];
			}

			$db_fields[$db_field] = $db_field;
		}

		if(empty($primary))
			return bors_throw(ec("Не найден первичный индекс для ").print_r($object_fields, true));

		$db_fields[] = "PRIMARY KEY (`$primary`)";

		$query = "CREATE TABLE IF NOT EXISTS `$table_name` (".join(', ', array_values($db_fields)).");";

		$db = new driver_mysql($db_name);
		$db->query($query);
//		$db->close();

	}

	static function drop_table($class_name)
	{
		if(!config('can-drop-tables'))
			return bors_throw(ec('Удаление таблиц запрещено'));

		$class = new $class_name(NULL);
		foreach($class->fields_map_db() as $db_name => $tables)
		{
			$db = new driver_mysql($db_name);

			foreach($tables as $table_name => $fields)
			{
				if(preg_match('/^(\w+)\((\w+)\)$/', $table_name, $m))
					$table_name = $m[1];

				$db->query("DROP TABLE IF EXISTS $table_name");
//				$db->close();
			}
		}
	}
}
