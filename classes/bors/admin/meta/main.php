<?php

class bors_admin_meta_main extends bors_admin_paginated
{
	function _config_class_def() { return config('admin_config_class'); }
	function _access_name_def() { return bors_lib_object::get_static($this->main_admin_class(), 'access_name'); }

	function _title_def() { return ec('Управление ').bors_lib_object::get_foo($this->main_class(), 'class_title_tpm'); }
	function _nav_name_def() { return bors_lib_object::get_foo($this->main_class(), 'class_title_m'); }

	function _is_admin_list_def() { return true; }

	function _new_object_url_def() { return $this->url().'new/'; }

	function _model_class_def()
	{
		return NULL;
	}

	function _main_class_def()
	{
		if($c = $this->model_class())
			return $c;

		$class_name = str_replace('_admin_', '_', $this->class_name());
		$class_name = str_replace('_main', '', $class_name);
		return blib_grammar::singular($class_name);
	}

	function _main_admin_class_def()
	{
		$class_name = str_replace('_main', '', $this->class_name());
		$admin_class_name = blib_grammar::singular($class_name);
		if(class_include($admin_class_name))
			return $admin_class_name;

		return $this->main_class();
	}

	function body_data()
	{
		$foo = bors_foo($this->main_class());

		$new_link_title = false;
		if(!$this->get('skip_auto_admin_new'))
			if(!$foo->get('skip_auto_admin_new'))
				$new_link_title = ec('Добавить ').$foo->class_title_vp();

		$fields = $this->get('item_fields');

		if(!$fields)
			$fields = $foo->item_list_admin_fields();

		$parsed_fields = array();
		$sortable = array();
		foreach($fields as $p => $t)
		{
			if(is_numeric($p))
			{
				$p = $t;
				$x = bors_lib_orm::parse_property($this->main_class(), $p);
				$t = defval($x, 'title', $p);
				if(!empty($x['admin_sortable']))
					$sortable[] = $p;
			}

			$parsed_fields[$p] = $t;
		}

		$this->set_attr('_sortable_append', $sortable);

		$data = array();

        if($this->get('use_bootstrap'))
        {
            $data['tcfg'] = bors_load('balancer_board_themes_bootstrap', NULL);
            $data['pagination'] = $this->pages_links_list(array(
                'div_css' => 'pagination pagination-centered pagination-small',
                'li_current_css' => 'active',
                'li_skip_css' => 'disabled',
                'skip_title' => true,
            ));
			$data['bootstrap'] = true;
        }
        else
        {
            $data['tcfg'] = bors_load('balancer_board_themes_default', NULL);
            $data['pagination'] = $this->pages_links_nul();
			$data['bootstrap'] = false;
        }

		return array_merge(parent::body_data(), $data, array(
			'query' => bors()->request()->data('q'),
			'new_link_title' => $new_link_title,
			'item_fields' => $parsed_fields,
			'admin_search_url' => $this->page() > 1 ? false : $this->get('admin_search_url'),
		));
	}

	function _order_def()
	{
		if($current_sort = bors()->request()->data_parse('signed_names', 'sort'))
			return $current_sort;

		return parent::_order_def();
	}
}
