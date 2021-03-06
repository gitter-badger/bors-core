<?php

class base_page_db extends bors_page
{
	function can_be_empty() { return false; }
	function can_cached() { return true; } //TODO: пока не разберусь, откуда глюки сохранения memcache

	function new_instance() { bors_object_new_instance_db($this); }

	function uri2id($id) { return $id; }

	function _url_engine_def() { return 'url_auto'; }

	function __construct($id)
	{
		$id = $this->uri2id($id);

		parent::__construct($id);
//		if(config('strict_auto_fields_check'))
//			bors_db_fields_init($this);
	}

	function template_data_fill()
	{
		parent::template_data_fill();

		if(!($qlist = $this->_global_queries()))
			return;

		foreach($qlist as $qname => $q)
		{
			if(isset($GLOBALS['cms']['templates']['data'][$qname]))
				continue;

			$cache = NULL;
			if(preg_match("!^(.+)\|(\d+)$!s", $q, $m))
			{
				$q		= $m[1];
				$cache	= $m[2];
			}

			$GLOBALS['cms']['templates']['data'][$qname] = $this->db()->get_array($q, false, $cache);
		}
	}

	function db_driver() { return 'driver_mysql'; }

	function storage_engine() { return config('storage.default.class_name', 'bors_storage_mysql'); }

	function fields_first() { return NULL; }

	function select($field, $where_map) { return $this->db()->select($this->table_name(), $field, $where_map); }
	function select_array($field, $where_map) { return $this->db()->select_array($this->table_name(), $field, $where_map); }

	function _global_queries() { return array(); }

	function fields()
	{
		if($this->storage_engine() != 'storage_db_mysql_smart')
		{
			return array(
				$this->db_name() => array(
					$this->table_name() => $this->table_fields()
				)
			);
		}

		bors_use('natural/bors_plural');

		return array(
			$this->db_name() => array(
				$this->table_name() => $this->table_fields()
			)
		);
	}

	function _db_name_def() { return config('main_bors_db'); }
	function _table_name_def() { return bors_plural($this->class_name()); }

	function main_id_field()
	{
		$f = $this->fields();
		$f = $f[$this->db_name()];
		$f = $f[$this->table_name()];
		if($fid = @$f['id'])
			return $fid;
		if($f[0] == 'id')
			return 'id';

		return NULL;
	}

	function delete()
	{
		if(method_exists($this, 'on_delete_pre'))
			if($this->on_delete_pre() === true)
				return true;

		$tab = $this->table_name();
		if(!$tab)
			debug_exit("Try to delete empty main table in class ".__FILE__.":".__LINE__);


		$id_field = $this->main_id_field();
		if(!$id_field)
			debug_exit("Try to delete empty id field in class ".__FILE__.":".__LINE__);

		bors_remove_cross_to($this->class_name(), $this->id());

		$this->storage()->delete($this);

		if(method_exists($this, 'on_delete_post'))
			if($this->on_delete_post() === true)
				return true;

		return parent::delete();
	}

	function compiled_source() { return bors_lcml::lcml($this->source(), array('container' => $this)); }
	static function objects_array($where) { return objects_array($where); }
	static function objects_first($where) { return objects_first($where); }

//	function table_fields() { return $this->fields_map(); }
}
