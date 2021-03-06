<?php

// Автоопределялка параметров из описаний таблиц БД

class bors_object_db extends base_object_db
{
	private static $__parsed_fields = array();

	function _project_name() { return @array_shift(explode('_', $this->class_name())); }
	function _access_name() { return bors_plural(array_pop(explode('_', $this->class_name()))); }

	function _item_name() { return @array_pop(explode('_', $this->class_name())); }
	function _item_name_m() { return bors_plural($this->_item_name()); }

	function _url_engine_def() { return 'url_auto'; }

	function self_class_bors_object_type() { return 'model'; }

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

		if($tab = $this->get('table_name', NULL, true))
			return $tab;

		$class_name = str_replace('_admin_', '_', $this->class_name());
		if(preg_match('/^'.preg_quote($this->project_name(),'/').'_(\w+)$/i', $class_name, $m))
			return $this->attr['table_name'] = bors_plural(blib_grammar::chunk_singular($m[1]));

		return $this->attr['table_name'] = $this->_item_name_m();
	}

	function set_table_name($table_name) { return $this->table_name = $table_name; }

	// Используется сброс кеша из storage при изменении числа полей
	function clear_table_fields_cache() { unset(self::$__parsed_fields[$this->class_name()]); }

	function table_fields()
	{
//		echo "1: {$this->class_name()}<br/>";
		if($fields = @self::$__parsed_fields[$this->class_name()])
			return $fields;

		$dbh = new driver_mysql($this->db_name());
		$info = $dbh->get("SHOW CREATE TABLE ".$this->table_name());
//		print_dd($info);
//		var_dump($this->_access_name());

		$project = $this->_project_name();

		$types = $this->get('field_types', array());

		$fields = array();
		foreach(explode("\n", $info['Create Table']) as $s)
		{
//			echo "=$s=<br/>\n";
			$is_null = !preg_match('/ NOT NULL /', $s);
			$args = array();

			// FOREIGN KEY (`head_id`) REFERENCES `persons` (`id`)
			if(preg_match('/FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`id`\)/', $s, $mm))
			{
				$field_name = $mm[1]; // например, parent_project_id
				$foreign_table = $mm[2];
				$item = blib_grammar::singular($foreign_table);
				$item_class = "{$project}_".$item;
				if(empty($fields[$field_name]['class']))
					$fields[$field_name]['class'] = $item_class;
				else
					$item_class = $fields[$field_name]['class'];
				$fields[$field_name]['have_null'] = "true";
				$auto_class_name = preg_replace('/_[a-z]+$/', '', $mm[1]);
				self::$__auto_objects[$this->class_name()][$auto_class_name] = "$item_class({$field_name})";
				continue;
			}

			// То же самое, но в другую БД
			// FOREIGN KEY (`city_id`) REFERENCES `WWW`.`CityIndex` (`Code`)
			if(preg_match('/FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)`.`(\w+)` \(`\w+`\)/', $s, $mm))
			{
				$field_name = $mm[1]; // например, parent_project_id
				$foreign_table = $mm[2];
				$foreign_db = $mm[3];
				if(empty($fields[$field_name]['class']))
					bors_throw(ec('Не могу автоматически определить имя класса в сторонней БД (')."`{$foreign_db}`.`{$foreign_table}`)".ec(' для класса ').$this->class_name());
				$item_class = $fields[$field_name]['class'];
				$fields[$field_name]['have_null'] = "true";
				$auto_class_name = preg_replace('/_[a-z]+$/', '', $mm[1]);
				self::$__auto_objects[$this->class_name()][$auto_class_name] = "$item_class({$field_name})";
				continue;
			}

			$is_req = false;

			if(preg_match('/^\s+`(\w+)`(.*)$/', $s, $m))
			{
				$field = $m[1];
				$type = trim($m[2]);
				if(preg_match('/^(\w+)/', $type, $mm))
					$type = $mm[1];

				$args = array();

				if(preg_match("/COMMENT '(.+?)'/", $s, $mm))
				{
					$title = $mm[1];
					if(preg_match('/^(.+?)\s*\[\*\]\s*$/', $title, $mm))
					{
						$title = $mm[1];
						$is_req = true;
					}

					if(preg_match('/^(.+?)\s*\(\.\)\s*$/', $title, $mm))
					{
						$title = $mm[1];
						$args['type'] = 'radio';
					}

					$args['title'] = $title;
				}

				// Пример: Пол[common_sex]
				// Добавить возможность использования и классов. Автоопределением, что ли?
				if(preg_match('/^(.+)\[(\w+)\]$/', @$args['title'], $m))
				{
					$args['title'] = trim($m[1]);
					$class_name = $m[2];
					$foo = new $class_name(NULL);
					if(method_exists($foo, 'named_list'))
						$args['named_list'] = $class_name;
					else
						$args['class'] = $class_name;
				}

				if(preg_match('!^(.+) // (.+)$!', @$args['title'], $m))
				{
					$args['title'] = $m[1];
					$args['comment'] = $m[2];
				}

				if(preg_match('!^([\wа-яА-ЯёЁ \-]+): (.+)$!u', @$args['title'], $m))
				{
					$args['form_section'] = $m[1];
					$args['title'] = $m[2];
				}

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

				if(@$args['title'] == 'hidden')
					$args['is_editable'] = false;

				if($type = @$types[$field])
				{
					if(is_array($type))
						$args = array_merge($args, $type);
					else
						$args['type'] = $type;
				}

				if($is_req)
					$args['is_req'] = true;

				$fields[$field] = array_merge($fields[$field], $args);
			}
		}

//		var_dump($fields);
//		var_dump($this->__auto_objects);

		foreach($this->get('table_fields_append', array()) as $field => $name)
		{
			$fields[$field]['name'] = $field;
			$fields[$field]['is_editable'] = false;
		}

		self::$__parsed_fields[$this->class_name()] = $fields;
		return $fields;
	}

	function url_ex($page)
	{
		// http://la2.balancer.ru/forum/index.php?showtopic=524&view=findpost&p=4163
		if($this->url_engine())
			return parent::url_ex($page);

		$site_url = config('main_site_url');
		$class_name = $this->class_name();

		if(preg_match('/_admin_/', $class_name))
		{
			$site_url = config('admin_site_url');
			$class_name = str_replace('_admin_', '_', $class_name);
		}

		if(preg_match('/^'.$this->project_name().'_(\w+)$/i', $class_name, $m))
			return $site_url.'/'.join('/', array_map('bors_plural', explode('_', bors_lower($m[1])))).'/'.$this->id().'/';

		return $site_url.'/'.$this->_item_name_m().'/'.$this->id().'/' . ($page > 1 ? $page.'.html' : '');
	}

	function _admin_url_def()
	{
		$admin = config('admin_site_url');
		//TODO: Костыль для сайтов без вынесенной админки. Придумать лучше.
		if($admin != config('main_site_url'))
			return $this->url();

		$url = $admin.'/'.$this->_item_name_m().'/'.$this->id().'/edit/';
		if(bors_load($url))
			return $url;

		if($this->storage())
			return $admin.'/_bors/admin/edit-smart/?object='.$this->internal_uri_ascii(); 

		return NULL;
	}

	function auto_objects()
	{
		$p = parent::auto_objects();
		$xs = self::$__auto_objects;
		$cn = $this->class_name();
		if(!empty($xs[$cn]))
			return array_merge($p, $xs[$cn]);

		return $p;
	}

	function _view_class_def() { return bors_plural($this->class_name()).'_view'; }
	function _view_def() { return bors_load($this->view_class(), $this->id()); }

	function __toString() { return (string) $this->get('title'); }

	function _project_name_def() { return bors_core_object_defaults::project_name($this); }
	function _section_name_def() { return bors_core_object_defaults::section_name($this); }

	//TODO: беглый костыль. Поднять на уровень выше
	function _have_image_def() { return $this->get('image_id'); }

	function _group_count_def()
	{
		if($counter_class = $this->get('b_counter_class'))
		{
			$link_field = bors_core_object_defaults::item_name($this->class_name()).'_id';
			return bors_count($counter_class, array($link_field => $this->id()));
		}

		return NULL;
	}
}
