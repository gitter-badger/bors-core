<?php

class common_keyword_bind extends base_page_db
{
	function storage_engine() { return 'bors_storage_mysql'; }

	function db_name(){ return config('main_bors_db'); }
	function table_name(){ return 'bors_keywords_index'; }

    function table_fields()
	{
		return array(
			'id',
			'keyword_id',
			'target_class_name',
			'target_class_id',
			'target_object_id',
			'target_create_time',
			'target_modify_time',
			'target_owner_id',
			'target_forum_id',
			'target_container_class_name',
			'target_container_class_id',
			'target_container_object_id',
			'sort_order',
			'link_type_id',
			'was_auto',
			'is_description_object',
		);
	}

	static function add($object, $was_auto = false, $append=NULL)
	{
		$db = new driver_mysql(config('main_bors_db'));

		$where = array(
			'target_class_id IN' => array($object->class_id(), $object->extends_class_id()),
			'target_object_id' => $object->id(),
		);

		if($was_auto) // Если это автоматическое добавление, то чистим тоже только автоматические.
			$where['was_auto'] = true;
		else
		{
			debug_hidden_log('__keywords_add',
				"{$object->debug_title()}: append=$append;"
				." keyword_string={$object->get('keywords_string')};"
				." where=".print_r($where, true)
			);
		}

		if(!$append) // Чистим только если это не регистрация отдельного слова
		{
//			debug_hidden_log('__keywords_delete_auto', "{$object->debug_title()}: auto=$was_auto, append=$append, where=".print_r($where, true));
			$db->delete('bors_keywords_index', $where);
		}

		$container = object_property($object, 'container');
		if($container)
		{
			$target_container_class_name = $container->extends_class_name();
			$target_container_class_id = $container->extends_class_id();
			$target_container_object_id = $container->id();
		}
		else
		{
			$target_container_class_name = $object->extends_class_name();
			$target_container_class_id = $object->extends_class_id();
			$target_container_object_id = $object->id();
		}

		if($append)
			$keyword_string = $append;
		else
			$keyword_string = $object->get('keywords_string');

		if(!$keyword_string)
			return;

		foreach(explode(',', $keyword_string) as $keyword)
		{
			$key = common_keyword::loader($keyword);

			$key->set_modify_time(time(), true);
			$key->set_targets_count(1 + $key->targets_count(), true);

			$new_bind = object_new_instance(__CLASS__, array(
				'keyword_id' => $key->id(),
				'target_class_id' => $object->extends_class_id(),
				'target_class_name' => $object->extends_class_name(),
				'target_object_id' => $object->id(),
				'target_create_time' => $object->create_time(),
				'target_modify_time' => $object->modify_time(),
				'target_owner_id' => $object->owner_id(),
				'target_forum_id' => object_property($object, 'forum_id'),
				'was_auto' => $was_auto,
				'target_container_class_name' => $target_container_class_name,
				'target_container_class_id' => $target_container_class_id,
				'target_container_object_id' => $target_container_object_id,
				'replace_on_new_instance' => true,
			));
		}
	}

	function ignore_on_new_instance() { return true; }

	function auto_objects()
	{
		return array_merge(parent::auto_objects(), array(
			'keyword' => 'common_keyword(keyword_id)',
			'target_forum' => 'balancer_board_forum(target_forum_id)',
		));
	}

	function auto_targets()
	{
		return array_merge(parent::auto_targets(), array(
			'target' => 'target_class_id(target_object_id)',
			'container' => 'target_container_class_name(target_container_object_id)',
		));
	}

	function container_or_target()
	{
		if($container = $this->container())
			return $container;

		return $this->target();
	}

//	function object() { return object_load($this->target_class_id(), $this->target_object_id()); }

	static function remove($object)
	{
		$db = new driver_mysql(config('main_bors_db'));

		$where = array(
			'target_class_id IN' => array($object->class_id(), $object->extends_class_id()),
			'target_object_id' => $object->id(),
		);

		debug_hidden_log('__keywords_delete', 'where='.print_r($where, true));
		$db->delete('bors_keywords_index', $where);
	}
}
