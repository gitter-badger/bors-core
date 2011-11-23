<?php

// Автоопределялка параметров из описаний таблиц БД

class bors_object_db extends base_object_db
{
	private static $__auto_objects = array();
	private static $__parsed_fields = array();

	function _project_name() { return array_shift(explode('_', $this->class_name())); }
	function _access_name() { return bors_plural(array_pop(explode('_', $this->class_name()))); }

	function _item_name() { return array_pop(explode('_', $this->class_name())); }
	function _item_name_m() { return bors_plural($this->_item_name()); }

	function fields()
	{
		return array($this->db_name() => array($this->table_name() => $this->table_fields()));
	}

	private $table_name = NULL;
	function table_name()
	{
		if(!empty($this->attr['table_name']))
			return $this->attr['table_name'];

		if(!empty($this->data['table_name']))
			return $this->data['table_name'];

		if($this->table_name)
			return $this->table_name;

		return $this->_item_name_m();
	}

	function set_table_name($table_name) { return $this->table_name = $table_name; }

	function table_fields()
	{
		if($fields = @self::$__parsed_fields[$this->class_name()])
			return $fields;

//		echo "1: {$this->class_name()}<br/>";
		$dbh = new driver_mysql($this->db_name());
		$info = $dbh->get("SHOW CREATE TABLE ".$this->table_name());
//		var_dump($info);
//		var_dump($this->_access_name());

		$project = $this->_project_name();

		$fields = array();
		foreach(explode("\n", $info['Create Table']) as $s)
		{
			$is_null = !preg_match('/ NOT NULL /', $s);
			$args = array();

			// FOREIGN KEY (`head_id`) REFERENCES `persons` (`id`)
			if(preg_match('/FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`id`\)/', $s, $mm))
			{
				$field_name = $mm[1]; // например, parent_project_id
				$foreign_table = $mm[2];
				$item = bors_unplural($foreign_table);
				$item_class = "{$project}_".$item;
				$fields[$field_name]['class'] = $item_class;
				$fields[$field_name]['have_null'] = "true";
				$auto_class_name = preg_replace('/_[a-z]+$/', '', $mm[1]);
				self::$__auto_objects[$this->class_name()][$auto_class_name] = "$item_class({$field_name})";
				continue;
			}

			if(preg_match('/^\s+`(\w+)`(.*)$/', $s, $m))
			{
				$field = $m[1];
				$type = trim($m[2]);
				if(preg_match('/^(\w+)/', $type, $mm))
					$type = $mm[1];

				$args = array();

				if(preg_match("/COMMENT '(.+?)'/", $s, $mm))
					$args['title'] = $mm[1];

				switch($type)
				{
					case 'timestamp':
					case 'date':
						$args['name'] = "UNIX_TIMESTAMP(`$field`)";
						if($is_null)
							$args['can_drop'] = true;
						break;
					case 'text':
						$args['type'] = 'bbcode';
						break;
				}

				if(empty($fields[$field]))
					$fields[$field] = array();

				if(in_array($field, array('id', 'create_time', 'modify_time', 'owner_id', 'last_editor_id')))
					$args['is_editable'] = false;

				$fields[$field] = array_merge($fields[$field], $args);
			}
		}

//		var_dump($fields);
//		var_dump($this->__auto_objects);

		self::$__parsed_fields[$this->class_name()] = $fields;
		return $fields;
	}

	function url() { return config('main_site_url').'/'.$this->_item_name_m().'/'.$this->id().'/'; }
	function admin_url() { return config('admin_site_url').'/'.$this->_item_name_m().'/'.$this->id().'/'; }

	function auto_objects()
	{
//		echo "2: {$this->class_name()}<br/>";
//		var_dump(self::$__auto_objects);
//		var_dump(array_merge(parent::auto_objects(), $this->__auto_objects));
		$p = parent::auto_objects();
		$xs = self::$__auto_objects;
		$cn = $this->class_name();
		$x = @$xs[$cn];
//		var_dump($x);
		if($x)
			return array_merge($p, $x);

		return $p;
	}

	function __toString() { return $this->title(); }
}