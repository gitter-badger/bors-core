<?php

class bors_objects_helper
{
	/*
		Обновление всех кешей, в которых хранится данный объект и всех зависимостей
	*/

	static function memcache_hash_key($object)
	{
		// 	   'bors_v'.config('memcached_tag').'_'.get_class($object).'://'.$object->id();
		return 'bors_v'.config('memcached_tag').'_'.$object->class_name().'://'.$object->id();
	}

	static function update_cached($object)
	{
		// Если объект хранится в memcached ...
		if(($memcache_instance = config('memcached_instance')))
			$memcache_instance->set(self::memcache_hash_key($object), serialize($object), 0, 0);
	}

	/*
		Сброс всех кешей объекта и его зависимостей
	*/

	static function drop_cached($object)
	{
		// Если объект хранится в memcached ...
		if(($memcache_instance = config('memcached_instance')))
			$memcache_instance->delete(self::memcache_hash_key($object));
	}

	static function cache_registers($object)
	{
		foreach($object->cache_parents() as $parent_object)
			cache_group::register($parent_object->internal_uri_ascii(), $object);
	}
}