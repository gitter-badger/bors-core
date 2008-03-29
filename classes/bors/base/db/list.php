<?php

class base_db_list extends base_object
{
	function storage_engine() { return ''; }
	
	var $_id_field;
	var $_title_field;
	var $_dbh;
	var $_table;

	function __construct($id)
	{
		parent::__construct($id);
	
		$item_class = $this->item_class();
		$item_class = &new $item_class(NULL); //object_load($this->item_class(), NULL);

		if(!$item_class)
			debug_exit("Error: can not class '{$this->item_class()}' init");
		
		if(method_exists($item_class, 'fields'))
			@list($db, $table, $id_field, $title_field) = $item_class->has_smart_field('title');

		if(empty($db))
			$db = $item_class->main_db_storage();

		$this->_dbh = &new DataBase($db);

		if(!empty($title_field))
		{
			if(preg_match('!^(\w+)\|.+$!', $title_field, $m))
				$title_field = $m[1];

			$this->_title_field = $title_field;
			$this->_id_field = $id_field;
			$this->_table = $table;
			return;
		}
		
		if(preg_match('!^(\w+)\((\w+)\)(\|.+)?$!', $item_class->field_title_storage(), $m))
		{
			$this->_title_field = $m[1];
			$this->_id_field = $m[2];
			$this->_table = $item_class->main_table_storage();
		}
		elseif(preg_match('!^(\w+)\.(\w+)\((\w+)\)(\|.+)?$!', $item_class->field_title_storage(), $m))
		{
			$this->_table = $m[1];
			$this->_title_field = $m[2];
			$this->_id_field = $m[3];
		}
		else
			debug_exit("Error: unknown title field format {$this->item_class()}->({$item_class->field_title_storage()})");
	}

	function named_list()
	{
		if(!$this->_dbh)
			$this->__construct();
	
		$list = $this->zero_item() === false ? array() : array(0 => $this->zero_item());

		$where = "";
		if($w = $this->where())
		{
			require_once('inc/mysql.php');
			$where = mysql_where_compile($w);
		}
		
		$join = "";
		if($jj = $this->left_join())
			foreach($jj as $j)
				$join .= "LEFT JOIN {$j} ";

		if($jj = $this->inner_join())
			foreach($jj as $j)
				$join .= "INNER JOIN {$j} ";

		if($this->group())
			$group = "GROUP BY ".$this->group();
		else
			$group = '';

		if($this->name_as_id())
			$id = "`".addslashes($this->_table)."`.`".addslashes($this->_title_field)."` AS `id`, ";
		else
			$id = "`".addslashes($this->_table)."`.`".addslashes($this->_id_field)."` AS `id`, ";

		foreach($this->_dbh->get_array("
				SELECT DISTINCT 
					$id
					`".addslashes($this->_table)."`.`".addslashes($this->_title_field)."` AS `title` 
				FROM `".addslashes($this->_table)."`
					$join
				$where
				$group
				ORDER BY {$this->order()}") as $x)
			$list[$x['id']] = $x['title'];
		
		return $list;
	}
	
	function id_to_name($id)
	{
		$list = $this->named_list();
		return $list[$id];
	}

	function where() { return NULL; }
	function order() { return "title"; }
	function left_join()   { return array(); }
	function inner_join()  { return array(); }
	function zero_item() { return false; }
	function group() { return false; }
	function name_as_id() { return false; }
}