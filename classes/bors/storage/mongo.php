<?php

class bors_storage_mongo extends bors_storage
{
	function load($object)
	{
		$m = new Mongo();
		$db = $m->selectDB('BORS');
		$c = $db->selectCollection('objects');
		$object->data = $c->findOne(array('_id' => $object->internal_uri_ascii()));

		return $object;
	}

	function save($object)
	{
		$m = new Mongo();
		$db = $m->selectDB('BORS');
		$c = $db->createCollection('objects');
		$c->insert(array_merge(array(
			'_id' => $object->internal_uri_ascii(),
			'class_name' => $object->class_name(),
			'object_id' => $object->id()
		), $object->data));
	}

	function find_first($class_name, $where = array())
	{
		if(!is_array($where))
			$where = array('object_id' => $where);

		$where['class_name'] = $class_name;

		$obj = new $class_name(NULL);

		$m = new Mongo();
		$db = $m->selectDB('BORS');
		$c = $db->selectCollection('objects');

		$obj->data = $c->findOne($where);

		return $obj;
	}
}
