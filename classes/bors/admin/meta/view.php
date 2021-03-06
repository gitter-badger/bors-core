<?php

class bors_admin_meta_view extends bors_admin_page
{
	function _config_class_def() { return config('admin_config_class'); }

	function _title_def() { return $this->target()->title(); }
	function _nav_name_def() { return $this->target()->nav_name(); }

	function can_be_empty() { return false; }
	function is_loaded() { return (bool) $this->model(); }

	function pre_show()
	{
		if(!$this->target())
			return bors_throw("Can't load editor");

		return parent::pre_show();
	}

	function target()
	{
		if(!class_exists($this->model_admin_class()))
			return NULL;

		return bors_load($this->model_admin_class(), $this->id());
	}

	function _main_class_def() { return $this->model_class(); }

	function _model_class_def()
	{
		$class_name = str_replace('_admin_', '_', $this->class_name());
		$class_name = str_replace('_edit', '', $class_name);
		$class_name = preg_replace('/_view$/', '', $class_name);
		return blib_grammar::singular($class_name);
	}

	function _main_admin_class_def() { return $this->model_admin_class(); }

	function _model_admin_class_def()
	{
		$project_class = preg_quote($this->project()->class_name(), '/');
		$admin_class_name = str_replace("/^({$project_class})_(.+)$/", "$1_admin_$2", $this->model_class());

		if(class_exists($admin_class_name))
			return $admin_class_name;

//		$admin_class_name = str_replace('_edit', '', $this->class_name());
//		$admin_class_name = preg_replace('/_view$/', '', $admin_class_name);
//		$admin_class_name = blib_grammar::singular($admin_class_name);

		return $this->model_class();
	}

	function _item_name_def()
	{
		return preg_replace('/^.+_(.+?)$/', '$1', $this->model_class());
	}

	function auto_objects()
	{
		return array_merge(parent::auto_objects(), array(
			'model' => $this->model_class().'(id)',
			$this->item_name() => $this->model_class().'(id)',
		));
	}

	function auto_targets()
	{
		return array_merge(parent::auto_targets(), array(
			'admin_target' => 'model_admin_class(id)',
		));
	}

	function body_data()
	{
		if($this->id())
		{
			$data = object_property($this->target(), 'data', array());
			if(!is_array($data))
				$data = array();
		}
		else
		{
			$data = array();
			if($ref = bors()->request()->referer())
				$this->set_attr('go_new_url', $ref);

			if(preg_match('!/(\w+s)/(\d+)/?$!', $ref, $m)
				|| preg_match('!/(\w+s)/(\d+)/\w+/?$!', $ref, $m)
			)
			{
				set_session_var("form_value_".blib_grammar::singular($m[1]).'_id', $m[2]);
			}
		}

		$target = $this->id() ? $this->target() : NULL;
		$admin_target = $this->id() ? $this->admin_target() : NULL;

		return array_merge(
			$data,
			parent::body_data(),
			array(
				$this->item_name() => $target,
				'admin_'.$this->item_name() => $admin_target,
				'target' => $target,
				'admin_target' => $admin_target,
				'model' => $this->model(),
			)
		);
	}

	// Куда переходим после сохранения нового объекта
	// По умолчанию — на редактирование этого же объекта
	function _go_new_url_def() { return 'newpage_admin'; }
	// Куда переходим после сохранения изменённого старого объекта
	// По умолчанию — на страницу-родителя
	function _go_edit_url_def() { return 'admin_parent'; }

	function submit_button_title() { return $this->id() ? ec('Сохранить') : ec('Добавить'); }

	function owner_id() { return object_property($this->target(), 'owner_id'); }

//	function parents() { return $this->admin_target() ? $this->admin_target()->parents() : parent::parents(); }
	function admin_parent_url() { return $this->admin_target() ? $this->admin_target()->admin_url() : parent::admin_parent_url(); }

	// Нельзя так: возможна ситуация, когда объект читать можно, а вот редактировать — нет. Тогда он будет показан!
//	function access() { return $this->admin_target() ? $this->admin_target()->access() : parent::access(); }
}
